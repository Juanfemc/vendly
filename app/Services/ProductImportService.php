<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Store;
use App\Support\ProductText;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class ProductImportService
{
    public const SESSION_KEY = 'product_import_preview';
    public const MAX_ROWS = 300;
    public const MAX_IMAGE_BYTES = 8_388_608;

    private const REQUIRED_COLUMNS = ['nombre', 'precio'];
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    private const COLUMN_ALIASES = [
        'nombre' => ['nombre', 'producto', 'name', 'product_name'],
        'precio' => ['precio', 'price', 'valor'],
        'descripcion' => ['descripcion', 'description', 'detalle'],
        'caracteristicas' => ['caracteristicas', 'features', 'ficha', 'detalles'],
        'categoria' => ['categoria', 'category'],
        'material' => ['material'],
        'stock' => ['stock', 'inventario', 'stock_quantity'],
        'precio_antes' => ['precio_antes', 'precio antes', 'precio anterior', 'offer_original_price'],
        'etiquetas' => ['etiquetas', 'badges', 'labels', 'tags'],
        'agotado' => ['agotado', 'sold_out', 'is_sold_out'],
        'imagen_url' => ['imagen_url', 'image_url', 'url_imagen', 'url imagen'],
        'imagen' => ['imagen', 'image', 'archivo_imagen', 'archivo imagen'],
    ];

    public function __construct(private ProductContentService $productContentService)
    {
    }

    public function templateRows(): array
    {
        return [
            ['nombre', 'precio', 'descripcion', 'caracteristicas', 'categoria', 'material', 'stock', 'precio_antes', 'etiquetas', 'agotado', 'imagen_url', 'imagen'],
            ['Camiseta basica', '58000', 'Camiseta comoda para uso diario.', 'Algodon suave, cuello redondo', 'Camisetas', 'Algodon', '10', '', 'Nuevo,Mas vendido', 'no', 'https://tusitio.com/camiseta.jpg', ''],
            ['Gorra azul', '35000', 'Gorra ajustable.', 'Tela liviana, broche ajustable', 'Accesorios', 'Algodon', '6', '', '', 'no', '', 'gorra-azul.jpg'],
        ];
    }

    public function preview(Store $store, UploadedFile $file, ?UploadedFile $imagesZip = null): array
    {
        $rows = $this->readRows($file);

        if (count($rows) < 2) {
            throw new RuntimeException('El archivo debe tener encabezados y al menos un producto.');
        }

        $header = $this->normalizeHeader(array_shift($rows));
        $missingColumns = collect(self::REQUIRED_COLUMNS)
            ->reject(fn (string $column) => in_array($column, $header, true))
            ->values()
            ->all();

        if ($missingColumns !== []) {
            throw new RuntimeException('Faltan columnas obligatorias: ' . implode(', ', $missingColumns) . '.');
        }

        $rows = array_slice($rows, 0, self::MAX_ROWS);
        $currentCount = $store->products()->count();
        $limit = $store->productLimit();
        $availableSlots = $limit === null ? null : max(0, $limit - $currentCount);
        $zipPath = null;

        try {
            $zipPath = $imagesZip ? $this->storeImagesZip($imagesZip) : null;
            $zipFiles = $zipPath ? $this->zipImageIndex($zipPath) : [];

            $items = collect($rows)
                ->map(fn (array $row, int $index) => $this->previewRow($store, $header, $row, $index + 2, $zipFiles))
                ->filter(fn (array $item) => $item['is_empty'] === false)
                ->values();

            if ($items->isEmpty()) {
                throw new RuntimeException('No encontramos productos para importar.');
            }

            if ($availableSlots !== null && $items->count() > $availableSlots) {
                $items = $items->map(function (array $item, int $index) use ($availableSlots) {
                    if ($index >= $availableSlots) {
                        $item['errors'][] = 'Supera el limite de productos de tu plan.';
                        $item['valid'] = false;
                    }

                    return $item;
                });
            }

            return [
                'store_id' => $store->id,
                'store_name' => $store->name,
                'zip_path' => $zipPath,
                'zip_image_count' => count($zipFiles),
                'rows' => $items->all(),
                'summary' => [
                    'total' => $items->count(),
                    'valid' => $items->where('valid', true)->count(),
                    'errors' => $items->where('valid', false)->count(),
                    'limit' => $limit,
                    'available_slots' => $availableSlots,
                ],
            ];
        } catch (Throwable $exception) {
            $this->deletePreviewFiles(['zip_path' => $zipPath]);

            throw $exception;
        }
    }

    public function import(Store $store, array $preview, int $userId): int
    {
        if ((int) ($preview['store_id'] ?? 0) !== (int) $store->id) {
            throw new RuntimeException('La previsualizacion ya no pertenece a esta tienda.');
        }

        $rows = collect($preview['rows'] ?? []);

        if ($rows->isEmpty()) {
            throw new RuntimeException('No hay productos listos para importar.');
        }

        if ($rows->contains(fn (array $row) => ! ($row['valid'] ?? false))) {
            throw new RuntimeException('Corrige los errores antes de importar.');
        }

        $limit = $store->productLimit();

        if ($limit !== null && ($store->products()->count() + $rows->count()) > $limit) {
            throw new RuntimeException('La importacion supera el limite de productos de tu plan.');
        }

        $storedImages = [];

        try {
            return DB::transaction(function () use ($store, $rows, $userId, $preview, &$storedImages) {
                $created = 0;

                foreach ($rows as $row) {
                    $data = $row['data'];
                    $imageSource = $data['image_source'] ?? ['type' => 'none', 'value' => ''];
                    unset($data['image_source']);
                    $image = $this->storeImportedImage($imageSource, $preview['zip_path'] ?? null);

                    if ($image) {
                        $storedImages[] = $image;
                    }

                    Product::create(array_merge($data, [
                        'slug' => Product::uniqueSlugFor((int) $store->id, $data['name']),
                        'features' => $this->productContentService->cleanRichText($data['features'] ?? null),
                        'sizes' => [],
                        'colors' => [],
                        'image' => $image,
                        'images' => [],
                        'user_id' => $store->user_id ?: $userId,
                        'store_id' => $store->id,
                    ]));

                    $created++;
                }

                return $created;
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($storedImages);

            throw $exception;
        }
    }

    public function deletePreviewFiles(?array $preview): void
    {
        $zipPath = (string) ($preview['zip_path'] ?? '');

        if ($zipPath !== '' && str_starts_with($zipPath, 'imports/product-images/')) {
            Storage::disk('local')->delete($zipPath);
        }
    }

    private function previewRow(Store $store, array $header, array $row, int $line, array $zipFiles): array
    {
        $raw = $this->rowToAssoc($header, $row);
        $imageSource = $this->imageSource($raw);
        $data = [
            'name' => $this->cleanText($raw['nombre'] ?? ''),
            'price' => $this->parseMoney($raw['precio'] ?? ''),
            'description' => ProductText::plain($raw['descripcion'] ?? null) ?: null,
            'features' => $this->cleanText($raw['caracteristicas'] ?? '') ?: null,
            'category' => $this->cleanText($raw['categoria'] ?? '') ?: null,
            'material' => $this->cleanText($raw['material'] ?? '') ?: null,
            'stock_quantity' => $this->parseInteger($raw['stock'] ?? ''),
            'is_sold_out' => $this->parseBoolean($raw['agotado'] ?? ''),
            'has_offer' => false,
            'offer_original_price' => null,
            'custom_badges' => [],
            'image_source' => $imageSource,
        ];
        $isEmpty = collect($raw)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty();
        $errors = [];

        if ($data['name'] === '') {
            $errors[] = 'El nombre es obligatorio.';
        }

        if ($data['price'] === null) {
            $errors[] = 'El precio es obligatorio y debe ser numerico.';
        }

        if (trim((string) ($raw['stock'] ?? '')) !== '' && $data['stock_quantity'] === null) {
            $errors[] = 'El stock debe ser un número entero.';
        }

        if ($data['category'] && ! $store->allowsCategories()) {
            $data['category'] = null;
        } elseif ($data['category'] && ! $store->categories()->where('name', $data['category'])->exists()) {
            $errors[] = 'La categoría no existe en esta tienda.';
        }

        if (! Product::supportsInventoryColumns() || $store->isReservationStore()) {
            $data['stock_quantity'] = null;
            $data['is_sold_out'] = false;
        }

        $offerOriginalPrice = $this->parseMoney($raw['precio_antes'] ?? '');
        if (trim((string) ($raw['precio_antes'] ?? '')) !== '' && $offerOriginalPrice === null) {
            $errors[] = 'El precio antes debe ser numerico.';
        }

        if ($offerOriginalPrice !== null && $store->allowsOfferBadges() && Product::supportsOfferColumn()) {
            if ($data['price'] !== null && $offerOriginalPrice > $data['price']) {
                $data['has_offer'] = true;
                $data['offer_original_price'] = Product::supportsOfferPricingColumn() ? $offerOriginalPrice : null;
            } else {
                $errors[] = 'El precio antes debe ser mayor al precio actual.';
            }
        }

        if ($store->allowsCustomProductBadges() && Product::supportsCustomBadgesColumn()) {
            $data['custom_badges'] = $this->parseBadges($raw['etiquetas'] ?? '');
        }

        if ($imageSource['type'] === 'url' && ! filter_var($imageSource['value'], FILTER_VALIDATE_URL)) {
            $errors[] = 'La URL de imagen no es valida.';
        }

        if ($imageSource['type'] === 'zip') {
            if ($zipFiles === []) {
                $errors[] = 'Sube un ZIP de imagenes o usa imagen_url.';
            } elseif (! isset($zipFiles[$this->imageFullLookupKey($imageSource['value'])])
                && ! isset($zipFiles[$this->imageBaseLookupKey($imageSource['value'])])) {
                $errors[] = 'No encontramos esa imagen en el ZIP.';
            }
        }

        return [
            'line' => $line,
            'raw' => $raw,
            'data' => $data,
            'errors' => $errors,
            'valid' => $errors === [] && ! $isEmpty,
            'is_empty' => $isEmpty,
        ];
    }

    private function readRows(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'csv', 'txt' => $this->readCsv($file),
            'xlsx' => $this->readXlsx($file),
            default => throw new RuntimeException('Sube un archivo CSV o XLSX.'),
        };
    }

    private function readCsv(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $handle = $path ? fopen($path, 'rb') : false;

        if (! $handle) {
            throw new RuntimeException('No se pudo leer el archivo CSV.');
        }

        $firstLine = fgets($handle) ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = array_map(fn ($value) => trim((string) $value), $row);
        }

        fclose($handle);

        return $rows;
    }

    private function readXlsx(UploadedFile $file): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('El servidor no tiene habilitado ZipArchive para leer XLSX. Usa CSV.');
        }

        $zip = new ZipArchive();

        if ($zip->open($file->getRealPath()) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo XLSX.');
        }

        $sharedStrings = $this->xlsxSharedStrings($zip);
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (! $sheet) {
            throw new RuntimeException('El XLSX debe tener una primera hoja con productos.');
        }

        $xml = simplexml_load_string($sheet);

        if (! $xml) {
            throw new RuntimeException('No se pudo leer la hoja del XLSX.');
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $cell) {
                $reference = (string) $cell['r'];
                $columnIndex = $this->xlsxColumnIndex($reference);
                $cells[$columnIndex] = $this->xlsxCellValue($cell, $sharedStrings);
            }

            if ($cells !== []) {
                $max = max(array_keys($cells));
                $rows[] = array_map(fn ($index) => $cells[$index] ?? '', range(0, $max));
            }
        }

        return $rows;
    }

    private function xlsxSharedStrings(ZipArchive $zip): array
    {
        $content = $zip->getFromName('xl/sharedStrings.xml');

        if (! $content) {
            return [];
        }

        $xml = simplexml_load_string($content);

        if (! $xml) {
            return [];
        }

        $strings = [];
        foreach ($xml->si as $item) {
            $parts = [];
            if (isset($item->t)) {
                $parts[] = (string) $item->t;
            }

            foreach ($item->r ?? [] as $run) {
                $parts[] = (string) $run->t;
            }

            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    private function xlsxCellValue(\SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];
        $value = (string) ($cell->v ?? '');

        if ($type === 's') {
            return (string) ($sharedStrings[(int) $value] ?? '');
        }

        if ($type === 'inlineStr') {
            return trim((string) ($cell->is->t ?? ''));
        }

        return trim($value);
    }

    private function xlsxColumnIndex(string $reference): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($reference)) ?: 'A';
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return max(0, $index - 1);
    }

    private function normalizeHeader(array $header): array
    {
        return collect($header)
            ->map(fn ($value) => $this->canonicalColumn((string) $value))
            ->all();
    }

    private function canonicalColumn(string $value): string
    {
        $value = Str::of($value)
            ->lower()
            ->ascii()
            ->replace(['-', '_'], ' ')
            ->squish()
            ->toString();

        foreach (self::COLUMN_ALIASES as $column => $aliases) {
            if (in_array($value, $aliases, true)) {
                return $column;
            }
        }

        return str_replace(' ', '_', $value);
    }

    private function rowToAssoc(array $header, array $row): array
    {
        $assoc = [];

        foreach ($header as $index => $column) {
            if ($column === '') {
                continue;
            }

            $assoc[$column] = trim((string) ($row[$index] ?? ''));
        }

        return $assoc;
    }

    private function cleanText(?string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $value) ?? '');
    }

    private function parseMoney(?string $value): ?float
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^\d,.\-]/', '', $value) ?? '';
        $hasComma = str_contains($value, ',');
        $hasDot = str_contains($value, '.');

        if ($hasComma && $hasDot) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif ($hasComma) {
            $value = str_replace(',', '.', $value);
        } elseif (substr_count($value, '.') > 1 || preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
        }

        return is_numeric($value) && (float) $value >= 0 ? round((float) $value, 2) : null;
    }

    private function parseInteger(?string $value): ?int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return ctype_digit($value) ? min((int) $value, 999999) : null;
    }

    private function parseBoolean(?string $value): bool
    {
        return in_array(Str::of((string) $value)->lower()->ascii()->trim()->toString(), ['1', 'si', 's', 'yes', 'true', 'agotado'], true);
    }

    private function parseBadges(?string $value): array
    {
        return collect(explode(',', (string) $value))
            ->map(fn ($badge) => $this->cleanText($badge))
            ->filter()
            ->unique()
            ->take(3)
            ->values()
            ->all();
    }

    private function imageSource(array $raw): array
    {
        $url = trim((string) ($raw['imagen_url'] ?? ''));
        $file = trim((string) ($raw['imagen'] ?? ''));

        if ($url !== '') {
            return ['type' => 'url', 'value' => $url];
        }

        if ($file !== '') {
            return ['type' => 'zip', 'value' => ltrim(str_replace('\\', '/', $file), '/')];
        }

        return ['type' => 'none', 'value' => ''];
    }

    private function storeImagesZip(UploadedFile $zip): string
    {
        return $zip->storeAs('imports/product-images', Str::uuid() . '.zip', 'local');
    }

    private function zipImageIndex(string $zipPath): array
    {
        $zip = new ZipArchive();

        if ($zip->open(Storage::disk('local')->path($zipPath)) !== true) {
            throw new RuntimeException('No se pudo abrir el ZIP de imagenes.');
        }

        $files = [];
        $baseNames = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = str_replace('\\', '/', (string) $zip->getNameIndex($index));

            if ($this->isSupportedImageName($name)) {
                $files[$this->imageFullLookupKey($name)] = $name;
                $baseNames[$this->imageBaseLookupKey($name)][] = $name;
            }
        }

        foreach ($baseNames as $key => $matches) {
            if (count($matches) === 1) {
                $files[$key] = $matches[0];
            }
        }

        $zip->close();

        return $files;
    }

    private function storeImportedImage(array $source, ?string $zipPath): ?string
    {
        return match ($source['type'] ?? 'none') {
            'url' => $this->storeImageFromUrl((string) $source['value']),
            'zip' => $this->storeImageFromZip((string) $source['value'], (string) $zipPath),
            default => null,
        };
    }

    private function storeImageFromUrl(string $url): string
    {
        $endpoint = $this->publicImageUrlEndpoint($url);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'vendly-product-image-');

        if (! $temporaryPath) {
            throw new RuntimeException('No se pudo preparar la descarga de una imagen.');
        }

        try {
            $response = Http::timeout(20)
                ->connectTimeout(8)
                ->withOptions([
                    'allow_redirects' => false,
                    'sink' => $temporaryPath,
                    'curl' => [
                        CURLOPT_RESOLVE => [
                            $this->curlResolveEntry(
                                $endpoint['host'],
                                $endpoint['port'],
                                $endpoint['address']
                            ),
                        ],
                    ],
                    'progress' => function (
                        $downloadTotal,
                        $downloadedBytes,
                        $uploadTotal,
                        $uploadedBytes
                    ) {
                        if ($downloadedBytes > self::MAX_IMAGE_BYTES) {
                            throw new RuntimeException('Una imagen supera el limite de 8MB.');
                        }
                    },
                ])
                ->get($url);

            if (! $response->successful()) {
                throw new RuntimeException('No se pudo descargar una imagen desde URL.');
            }

            $contentLength = (int) ($response->header('Content-Length') ?: 0);

            if ($contentLength > self::MAX_IMAGE_BYTES) {
                throw new RuntimeException('Una imagen supera el limite de 8MB.');
            }

            $bytes = file_get_contents($temporaryPath);

            if ($bytes === false) {
                throw new RuntimeException('No se pudo leer una imagen descargada.');
            }

            if (strlen($bytes) > self::MAX_IMAGE_BYTES) {
                throw new RuntimeException('Una imagen supera el limite de 8MB.');
            }

            $extension = $this->imageExtensionFromBytes($bytes)
                ?: $this->imageExtensionFromContentType((string) $response->header('Content-Type'));

            if (! $extension) {
                throw new RuntimeException('Una imagen URL no tiene formato JPG, PNG o WebP.');
            }

            return $this->storeImageBytes($bytes, $extension);
        } finally {
            @unlink($temporaryPath);
        }
    }

    private function storeImageFromZip(string $imageName, string $zipPath): string
    {
        if ($zipPath === '' || ! Storage::disk('local')->exists($zipPath)) {
            throw new RuntimeException('No encontramos el ZIP de imagenes para completar la importacion.');
        }

        $imageIndex = $this->zipImageIndex($zipPath);
        $entryName = $imageIndex[$this->imageFullLookupKey($imageName)]
            ?? $imageIndex[$this->imageBaseLookupKey($imageName)]
            ?? null;

        if (! $entryName) {
            throw new RuntimeException('No encontramos una imagen del ZIP al importar.');
        }

        $zip = new ZipArchive();

        if ($zip->open(Storage::disk('local')->path($zipPath)) !== true) {
            throw new RuntimeException('No se pudo abrir el ZIP de imagenes.');
        }

        $stats = $zip->statName($entryName);

        if (($stats['size'] ?? 0) > self::MAX_IMAGE_BYTES) {
            $zip->close();

            throw new RuntimeException('Una imagen supera el limite de 8MB.');
        }

        $bytes = $zip->getFromName($entryName);
        $zip->close();

        if ($bytes === false) {
            throw new RuntimeException('No se pudo leer una imagen del ZIP.');
        }

        if (strlen($bytes) > self::MAX_IMAGE_BYTES) {
            throw new RuntimeException('Una imagen supera el limite de 8MB.');
        }

        $extension = $this->imageExtensionFromBytes($bytes)
            ?: $this->imageExtensionFromName($entryName);

        if (! $extension) {
            throw new RuntimeException('Una imagen del ZIP no tiene formato JPG, PNG o WebP.');
        }

        return $this->storeImageBytes($bytes, $extension);
    }

    private function storeImageBytes(string $bytes, string $extension): string
    {
        $path = 'products/' . Str::uuid() . '.' . $extension;
        Storage::disk('public')->put($path, $bytes);

        return $path;
    }

    private function isSupportedImageName(string $name): bool
    {
        return $this->imageExtensionFromName($name) !== null;
    }

    private function imageExtensionFromName(string $name): ?string
    {
        $extension = strtolower(pathinfo(parse_url($name, PHP_URL_PATH) ?: $name, PATHINFO_EXTENSION));
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;

        return in_array($extension, self::IMAGE_EXTENSIONS, true) ? $extension : null;
    }

    private function imageExtensionFromContentType(string $contentType): ?string
    {
        return match (strtolower(strtok($contentType, ';') ?: '')) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null,
        };
    }

    private function imageExtensionFromBytes(string $bytes): ?string
    {
        $info = @getimagesizefromstring($bytes);
        $mime = is_array($info) ? (string) ($info['mime'] ?? '') : '';

        return $this->imageExtensionFromContentType($mime);
    }

    private function publicImageUrlEndpoint(string $url): array
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new RuntimeException('La URL de imagen debe usar http o https.');
        }

        if (! defined('CURLOPT_RESOLVE')) {
            throw new RuntimeException('El servidor no permite importar imagenes por URL de forma segura. Usa ZIP.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new RuntimeException('La URL de imagen no debe incluir usuario ni contrasena.');
        }

        if (! in_array($port, [80, 443], true)) {
            throw new RuntimeException('La URL de imagen solo puede usar puertos 80 o 443.');
        }

        if (in_array($host, ['localhost', 'localhost.localdomain'], true) || str_ends_with($host, '.local')) {
            throw new RuntimeException('La URL de imagen no puede apuntar a una direccion local.');
        }

        $addresses = $this->resolveHostAddresses($host);

        if ($addresses === []) {
            throw new RuntimeException('No se pudo resolver el dominio de una imagen.');
        }

        foreach ($addresses as $address) {
            if (! $this->isPublicIp($address)) {
                throw new RuntimeException('La URL de imagen no puede apuntar a redes privadas o internas.');
            }
        }

        return [
            'host' => $host,
            'port' => $port,
            'address' => $this->preferredAddress($addresses),
        ];
    }

    private function resolveHostAddresses(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $addresses = gethostbynamel($host) ?: [];

        foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $record) {
            if (! empty($record['ipv6'])) {
                $addresses[] = $record['ipv6'];
            }
        }

        return array_values(array_unique($addresses));
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    private function preferredAddress(array $addresses): string
    {
        foreach ($addresses as $address) {
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $address;
            }
        }

        return (string) reset($addresses);
    }

    private function curlResolveEntry(string $host, int $port, string $address): string
    {
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $address = '[' . $address . ']';
        }

        return "{$host}:{$port}:{$address}";
    }

    private function imageFullLookupKey(string $name): string
    {
        return Str::of($name)
            ->replace('\\', '/')
            ->ltrim('/')
            ->lower()
            ->ascii()
            ->trim()
            ->toString();
    }

    private function imageBaseLookupKey(string $name): string
    {
        return Str::of($name)
            ->replace('\\', '/')
            ->afterLast('/')
            ->lower()
            ->ascii()
            ->trim()
            ->toString();
    }
}
