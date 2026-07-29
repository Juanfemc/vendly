<?php

namespace App\Services;

use App\Models\Store;
use App\Services\Concerns\ConfiguresOpenAiHttp;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AiImageService
{
    use ConfiguresOpenAiHttp;

    public function generate(Store $store, string $type, array $context): array
    {
        $apiKey = (string) config('services.openai.key');

        if ($apiKey === '') {
            throw new RuntimeException('Configura OPENAI_API_KEY para generar imagenes con IA.');
        }

        $prompt = $this->prompt($store, $type, $context);
        $model = (string) config('services.openai.image_model', 'gpt-image-1');

        if ($this->isEnhancementType($type)) {
            return $this->editExistingImage($store, $type, $context, $prompt, $model, $apiKey);
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->withOptions($this->openAiHttpOptions())
                ->timeout(60)
                ->post('https://api.openai.com/v1/images/generations', [
                    'model' => $model,
                    'prompt' => $prompt,
                    'n' => 1,
                    'size' => $this->size($type),
                    'quality' => config('services.openai.image_quality', 'low'),
                    'output_format' => 'webp',
                ])
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $message = $exception->response?->json('error.message') ?: 'No se pudo generar la imagen con IA.';
            throw new RuntimeException($message);
        }

        $image = (string) ($response['data'][0]['b64_json'] ?? '');

        if ($image === '') {
            throw new RuntimeException('La IA no devolvio una imagen. Intenta de nuevo.');
        }

        $bytes = base64_decode($image, true);

        if ($bytes === false) {
            throw new RuntimeException('La imagen generada no se pudo procesar.');
        }

        $path = $this->path($type, $store);
        Storage::disk('public')->put($path, $bytes);

        return [
            'content' => [
                'image_path' => $path,
                'image_url' => asset('storage/' . $path),
                'prompt' => $prompt,
            ],
            'raw' => $response,
        ];
    }

    private function editExistingImage(Store $store, string $type, array $context, string $prompt, string $model, string $apiKey): array
    {
        $sourcePath = $this->sourceImagePath($type, $context);

        if (! $this->sourcePathIsAllowed($sourcePath, $store)) {
            throw new RuntimeException('No encontramos una imagen valida para mejorar.');
        }

        $bytes = Storage::disk('public')->get($sourcePath);

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->withOptions($this->openAiHttpOptions())
                ->timeout(90)
                ->attach('image', $bytes, basename($sourcePath))
                ->post('https://api.openai.com/v1/images/edits', [
                    'model' => $model,
                    'prompt' => $prompt,
                    'n' => 1,
                    'size' => $this->size($type),
                    'quality' => config('services.openai.image_quality', 'low'),
                    'output_format' => 'webp',
                ])
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $message = $exception->response?->json('error.message') ?: 'No se pudo mejorar la imagen con IA.';
            throw new RuntimeException($message);
        }

        $image = (string) ($response['data'][0]['b64_json'] ?? '');

        if ($image === '') {
            throw new RuntimeException('La IA no devolvio una imagen mejorada. Intenta de nuevo.');
        }

        $generatedBytes = base64_decode($image, true);

        if ($generatedBytes === false) {
            throw new RuntimeException('La imagen mejorada no se pudo procesar.');
        }

        $path = $this->path($type, $store);
        Storage::disk('public')->put($path, $generatedBytes);

        return [
            'content' => [
                'image_path' => $path,
                'image_url' => asset('storage/' . $path),
                'prompt' => $prompt,
                'source_image_path' => $sourcePath,
            ],
            'raw' => $response,
        ];
    }

    private function prompt(Store $store, string $type, array $context): string
    {
        $business = $store->businessTypeLabel();

        if ($type === AiContentService::STORE_COVER_IMAGE) {
            return implode(' ', [
                'Crea una portada horizontal profesional para una tienda online colombiana.',
                'Estilo ecommerce moderno, limpio, realista, con buena iluminacion y espacio visual para texto.',
                'No incluyas texto, logos, marcas registradas, personas reconocibles ni marcas de agua.',
                "Nombre de tienda: {$store->name}. Tipo de negocio: {$business}.",
                'Descripcion de tienda: ' . ((string) ($store->shop_copy ?: 'Catalogo online profesional.')),
            ]);
        }

        if ($type === AiContentService::STORE_COVER_IMAGE_ENHANCE) {
            return implode(' ', [
                'Mejora esta portada de tienda online sin cambiar su producto principal ni su identidad visual.',
                'Optimiza iluminacion, nitidez, composicion horizontal, contraste y acabado profesional ecommerce.',
                'Manten una apariencia natural, limpia y confiable. No agregues texto, marcas de agua ni logos nuevos.',
                "Nombre de tienda: {$store->name}. Tipo de negocio: {$business}.",
            ]);
        }

        if ($type === AiContentService::STORE_LOGO_IMAGE) {
            return implode(' ', [
                'Crea un logo cuadrado profesional para una tienda online colombiana.',
                'Estilo moderno, simple, memorable y usable como avatar de tienda.',
                'Fondo limpio, alto contraste, simbolo principal claro, sin mockups ni fotografias.',
                'Puedes usar iniciales o un simbolo abstracto relacionado con el negocio.',
                'No incluyas marcas registradas, personas, sombras exageradas ni marcas de agua.',
                "Nombre de tienda: {$store->name}. Tipo de negocio: {$business}.",
                'Descripcion de tienda: ' . ((string) ($store->shop_copy ?: 'Catalogo online profesional.')),
                'Color principal: ' . ((string) ($store->brand_color ?: '#ff6b00')) . '.',
            ]);
        }

        if ($type === AiContentService::STORE_LOGO_IMAGE_ENHANCE) {
            return implode(' ', [
                'Mejora este logo para que funcione mejor como avatar cuadrado de una tienda online.',
                'Hazlo mas nitido, limpio, centrado y legible, manteniendo la esencia del logo original.',
                'No agregues texto nuevo, marcas de agua, mockups, fotografias ni elementos distractores.',
                "Nombre de tienda: {$store->name}. Tipo de negocio: {$business}.",
            ]);
        }

        $product = $context['producto'] ?? [];

        if ($type === AiContentService::PRODUCT_IMAGE_ENHANCE) {
            return implode(' ', [
                'Mejora esta foto de producto para catalogo ecommerce sin cambiar el producto.',
                'Producto centrado, mas nitido, mejor iluminado, con fondo limpio y sombra suave natural.',
                'Manten colores y forma reales. No agregues texto, logos, marcas de agua, personas ni objetos que no existan.',
                'Nombre: ' . ((string) ($product['nombre'] ?? 'Producto')),
                'Categoria: ' . ((string) ($product['categoria'] ?? 'General')),
            ]);
        }

        return implode(' ', [
            'Crea una imagen cuadrada tipo ecommerce para producto.',
            'Producto centrado, fondo claro, sombra suave, acabado profesional de catalogo.',
            'No incluyas texto, logos, marcas registradas, personas reconocibles ni marcas de agua.',
            'Nombre: ' . ((string) ($product['nombre'] ?? 'Producto')),
            'Categoria: ' . ((string) ($product['categoria'] ?? 'General')),
            'Material o detalle: ' . ((string) ($product['material'] ?? '')),
            'Descripcion: ' . Str::limit((string) ($product['descripcion_actual'] ?? ''), 500),
        ]);
    }

    private function size(string $type): string
    {
        return in_array($type, [
            AiContentService::STORE_COVER_IMAGE,
            AiContentService::STORE_COVER_IMAGE_ENHANCE,
        ], true) ? '1536x1024' : '1024x1024';
    }

    private function path(string $type, Store $store): string
    {
        $directory = in_array($type, [
            AiContentService::STORE_COVER_IMAGE,
            AiContentService::STORE_LOGO_IMAGE,
            AiContentService::STORE_COVER_IMAGE_ENHANCE,
            AiContentService::STORE_LOGO_IMAGE_ENHANCE,
        ], true)
            ? 'stores/ai'
            : 'products/ai';

        return $directory . '/' . $store->id . '-' . Str::uuid() . '.webp';
    }

    private function isEnhancementType(string $type): bool
    {
        return in_array($type, [
            AiContentService::STORE_COVER_IMAGE_ENHANCE,
            AiContentService::STORE_LOGO_IMAGE_ENHANCE,
            AiContentService::PRODUCT_IMAGE_ENHANCE,
        ], true);
    }

    private function sourceImagePath(string $type, array $context): string
    {
        if ($type === AiContentService::STORE_COVER_IMAGE_ENHANCE) {
            return (string) ($context['store_cover_image'] ?? '');
        }

        if ($type === AiContentService::STORE_LOGO_IMAGE_ENHANCE) {
            return (string) ($context['store_logo_image'] ?? '');
        }

        return (string) (($context['producto'] ?? [])['imagen'] ?? '');
    }

    private function sourcePathIsAllowed(string $path, Store $store): bool
    {
        $path = str_replace('\\', '/', trim($path));

        if ($path === '' || str_contains($path, '..')) {
            return false;
        }

        $allowedPrefixes = [
            'stores/',
            'stores/ai/' . $store->id . '-',
            'products/',
            'products/ai/' . $store->id . '-',
        ];

        return collect($allowedPrefixes)->contains(fn (string $prefix) => str_starts_with($path, $prefix))
            && Storage::disk('public')->exists($path);
    }
}
