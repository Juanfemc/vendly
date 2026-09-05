<?php

namespace App\Services;

use App\Models\AiGeneration;
use App\Models\Store;
use App\Services\Concerns\ConfiguresOpenAiHttp;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PdfProductOcrImportService
{
    use ConfiguresOpenAiHttp;

    public const MAX_PRODUCTS = 80;

    public function extractRows(Store $store, UploadedFile $pdf, ?int $timeoutSeconds = null, ?int $userId = null): array
    {
        $apiKey = (string) config('services.openai.key');

        if ($apiKey === '') {
            throw new RuntimeException('Configura OPENAI_API_KEY para usar OCR con IA.');
        }

        $bytes = $pdf->getContent();

        if ($bytes === false || $bytes === '') {
            throw new RuntimeException('No se pudo leer el PDF.');
        }

        $model = (string) config('services.openai.ocr_model', config('services.openai.model', 'gpt-4.1-mini'));
        $timeout = $timeoutSeconds !== null
            ? max(10, min($timeoutSeconds, 300))
            : max(10, min((int) config('services.openai.ocr_timeout', 45), 55));
        $prompt = $this->prompt($store);
        $generation = AiGeneration::create([
            'store_id' => $store->id,
            'user_id' => $userId ?? auth()->id(),
            'type' => AiContentService::PRODUCT_IMPORT_OCR,
            'prompt' => 'OCR + IA desde PDF interno admin',
            'context' => [
                'filename' => $pdf->getClientOriginalName(),
                'size' => $pdf->getSize(),
            ],
            'status' => 'processing',
            'provider' => 'openai',
            'model' => $model,
        ]);

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->withOptions($this->openAiHttpOptions())
                ->connectTimeout(10)
                ->timeout($timeout)
                ->post('https://api.openai.com/v1/responses', [
                    'model' => $model,
                    'instructions' => $this->instructions(),
                    'input' => [[
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'input_text',
                                'text' => $prompt,
                            ],
                            [
                                'type' => 'input_file',
                                'filename' => $this->filename($pdf),
                                'file_data' => 'data:application/pdf;base64,' . base64_encode($bytes),
                                'detail' => 'high',
                            ],
                        ],
                    ]],
                    'max_output_tokens' => 5000,
                ])
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $message = $exception->response?->json('error.message') ?: 'No se pudo procesar el PDF con IA.';
            $generation->update([
                'status' => 'failed',
                'error' => $message,
            ]);

            throw new RuntimeException($message);
        } catch (ConnectionException $exception) {
            $message = 'El OCR con IA tardó demasiado o no pudo conectarse. Intenta con un PDF más liviano o dividido en partes.';
            $generation->update([
                'status' => 'failed',
                'error' => $message,
            ]);

            throw new RuntimeException($message, previous: $exception);
        }

        try {
            $products = $this->productsFromResponse($response);
        } catch (RuntimeException $exception) {
            $generation->update([
                'status' => 'failed',
                'response' => $response,
                'error' => $exception->getMessage(),
                'input_tokens' => $response['usage']['input_tokens'] ?? null,
                'output_tokens' => $response['usage']['output_tokens'] ?? null,
            ]);

            throw $exception;
        }

        if ($products === []) {
            $generation->update([
                'status' => 'failed',
                'response' => $response,
                'error' => 'La IA no encontró productos claros en el PDF.',
                'input_tokens' => $response['usage']['input_tokens'] ?? null,
                'output_tokens' => $response['usage']['output_tokens'] ?? null,
            ]);

            throw new RuntimeException('La IA no encontró productos claros en el PDF.');
        }

        $generation->update([
            'status' => 'completed',
            'response' => [
                'products_count' => count($products),
            ],
            'input_tokens' => $response['usage']['input_tokens'] ?? null,
            'output_tokens' => $response['usage']['output_tokens'] ?? null,
        ]);

        return $this->rowsFromProducts($products);
    }

    private function instructions(): string
    {
        return implode(' ', [
            'Eres un extractor OCR de catalogos de ecommerce para Vendly.',
            'Lee PDFs escaneados o desordenados y convierte solo productos reales en JSON valido.',
            'Ignora banners, textos legales, indices, instrucciones, decoracion, redes sociales y datos de contacto.',
            'No inventes precios, stock, categorias, materiales ni descripciones.',
            'Devuelve solo JSON valido, sin markdown ni explicaciones.',
        ]);
    }

    private function prompt(Store $store): string
    {
        $categories = $store->allowsCategories()
            ? collect($store->productCategoryOptions())->values()->take(80)->all()
            : [];

        return 'Extrae productos del PDF para esta tienda: ' . json_encode([
            'tienda' => [
                'nombre' => $store->name,
                'tipo' => $store->businessTypeLabel(),
                'categorias_existentes' => $categories,
            ],
            'reglas' => [
                'maximo_productos' => self::MAX_PRODUCTS,
                'usar_solo_texto_visible_en_pdf' => true,
                'precio_numero_sin_simbolos' => true,
                'categoria' => 'Usa una categoria existente solo si coincide claramente. Si no, deja null.',
                'stock_quantity' => 'Entero si aparece claramente; si no aparece, null.',
                'features' => 'Texto corto con caracteristicas reales separadas por saltos de linea si aparecen.',
                'description' => 'Descripcion breve solo si aparece o se deduce directamente del texto del producto.',
            ],
            'formato' => [
                'products' => [[
                    'name' => 'Nombre del producto. Obligatorio.',
                    'price' => 'Numero. Obligatorio si aparece precio claro.',
                    'description' => 'Texto o null.',
                    'features' => 'Texto con saltos de linea o null.',
                    'category' => 'Texto o null.',
                    'material' => 'Texto o null.',
                    'stock_quantity' => 'Entero o null.',
                    'confidence' => 'Numero de 0 a 1.',
                ]],
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    private function productsFromResponse(array $response): array
    {
        $text = $this->responseText($response);

        if ($text === '') {
            throw new RuntimeException('La IA no devolvio contenido.');
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            $decoded = $this->decodeJsonFragment($text);
        }

        $products = is_array($decoded) ? ($decoded['products'] ?? []) : [];

        return collect(is_array($products) ? $products : [])
            ->filter(fn ($product) => is_array($product))
            ->take(self::MAX_PRODUCTS)
            ->values()
            ->all();
    }

    private function rowsFromProducts(array $products): array
    {
        $rows = [[
            'nombre',
            'precio',
            'descripcion',
            'caracteristicas',
            'categoria',
            'material',
            'stock',
        ]];

        foreach ($products as $product) {
            $rows[] = [
                $this->cleanText($product['name'] ?? null),
                $this->cleanMoney($product['price'] ?? null),
                $this->cleanText($product['description'] ?? null),
                $this->cleanMultiline($product['features'] ?? null),
                $this->cleanText($product['category'] ?? null),
                $this->cleanText($product['material'] ?? null),
                $this->cleanInteger($product['stock_quantity'] ?? null),
            ];
        }

        return $rows;
    }

    private function responseText(array $response): string
    {
        if (isset($response['output_text'])) {
            return trim((string) $response['output_text']);
        }

        foreach (($response['output'] ?? []) as $output) {
            foreach (($output['content'] ?? []) as $content) {
                if (($content['type'] ?? null) === 'output_text' && isset($content['text'])) {
                    return trim((string) $content['text']);
                }
            }
        }

        return '';
    }

    private function filename(UploadedFile $pdf): string
    {
        $name = Str::of($pdf->getClientOriginalName() ?: 'catalogo.pdf')
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9_.-]+/', '-')
            ->trim('-')
            ->toString();

        return $name !== '' && str_ends_with(strtolower($name), '.pdf') ? $name : 'catalogo.pdf';
    }

    private function cleanText(mixed $value): string
    {
        if (is_array($value)) {
            $value = implode(' ', array_filter($value, fn ($item) => is_scalar($item)));
        }

        return Str::limit(trim(preg_replace('/\s+/u', ' ', (string) $value) ?? ''), 500, '');
    }

    private function cleanMultiline(mixed $value): string
    {
        if (is_array($value)) {
            $value = implode("\n", array_filter($value, fn ($item) => is_scalar($item)));
        }

        $text = str_replace(["\r\n", "\r"], "\n", (string) $value);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return Str::limit(trim($text), 1200, '');
    }

    private function decodeJsonFragment(string $text): ?array
    {
        $text = trim($text);

        foreach ([['{', '}'], ['[', ']']] as [$start, $end]) {
            $startPosition = strpos($text, $start);
            $endPosition = strrpos($text, $end);

            if ($startPosition === false || $endPosition === false || $endPosition <= $startPosition) {
                continue;
            }

            $decoded = json_decode(substr($text, $startPosition, $endPosition - $startPosition + 1), true);

            if (is_array($decoded)) {
                return $start === '[' ? ['products' => $decoded] : $decoded;
            }
        }

        return null;
    }

    private function cleanMoney(mixed $value): string
    {
        if (is_numeric($value)) {
            return (string) round((float) $value, 2);
        }

        return $this->cleanText($value);
    }

    private function cleanInteger(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_numeric($value)) {
            return (string) max(0, (int) $value);
        }

        return $this->cleanText($value);
    }
}
