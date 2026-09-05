<?php

use App\Jobs\ProcessProductImportBatch;
use App\Models\AiGeneration;
use App\Models\ProductImportBatch;
use App\Models\Store;
use App\Models\User;
use App\Services\PdfProductOcrImportService;
use App\Services\ProductImportService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

test('store users cannot preview product imports from ocr pdfs', function () {
    $user = User::factory()->create(['role' => 'store']);
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda OCR',
        'slug' => 'tienda-ocr',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('admin.products.import.ocr-preview'), [
            'store_id' => $store->id,
            'pdf' => UploadedFile::fake()->create('catalogo.pdf', 100, 'application/pdf'),
        ])
        ->assertForbidden();
});

test('ocr pdf import converts openai connection failures into controlled errors', function () {
    config(['services.openai.key' => 'test-key']);
    Http::fake(fn () => throw new ConnectionException('Connection timed out'));

    $admin = User::factory()->create(['role' => 'admin']);
    $owner = User::factory()->create(['role' => 'store']);
    $store = Store::create([
        'user_id' => $owner->id,
        'name' => 'Tienda PDF',
        'slug' => 'tienda-pdf',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    expect(fn () => app(PdfProductOcrImportService::class)->extractRows(
        $store,
        UploadedFile::fake()->createWithContent('catalogo.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF"),
    ))->toThrow(RuntimeException::class, 'El OCR con IA tardó demasiado');

    $generation = AiGeneration::where('store_id', $store->id)
        ->where('type', 'product_import_ocr')
        ->first();

    expect($generation)->not->toBeNull()
        ->and($generation->status)->toBe('failed')
        ->and($generation->error)->toBe('El OCR con IA tardó demasiado o no pudo conectarse. Intenta con un PDF más liviano o dividido en partes.');
});

test('admins can assign manual images to an ocr product import preview', function () {
    Storage::fake('local');

    $admin = User::factory()->create(['role' => 'admin']);
    $owner = User::factory()->create(['role' => 'store']);
    $store = Store::create([
        'user_id' => $owner->id,
        'name' => 'Tienda Imagen OCR',
        'slug' => 'tienda-imagen-ocr',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $preview = [
        'store_id' => $store->id,
        'store_name' => $store->name,
        'source' => 'PDF con OCR + IA: catalogo.pdf',
        'zip_path' => null,
        'zip_image_count' => 0,
        'zip_files' => [],
        'temp_images' => [],
        'rows' => [[
            'line' => 2,
            'raw' => [],
            'data' => [
                'name' => 'Producto OCR',
                'price' => 25000,
                'category' => null,
                'stock_quantity' => null,
                'custom_badges' => [],
                'image_source' => ['type' => 'none', 'value' => ''],
            ],
            'errors' => [],
            'valid' => true,
            'is_empty' => false,
        ]],
        'summary' => [
            'total' => 1,
            'valid' => 1,
            'errors' => 0,
            'limit' => null,
            'available_slots' => null,
        ],
    ];

    $response = $this->actingAs($admin)
        ->withSession([ProductImportService::SESSION_KEY => $preview])
        ->post(route('admin.products.import.images'), [
            'manual_images' => [
                0 => UploadedFile::fake()->createWithContent(
                    'producto.png',
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
                ),
            ],
        ]);

    $response->assertRedirect(route('admin.products.import'));
    $updatedPreview = session(ProductImportService::SESSION_KEY);
    $imageSource = $updatedPreview['rows'][0]['data']['image_source'] ?? [];

    expect($imageSource['type'] ?? null)->toBe('temp');
    Storage::disk('local')->assertExists($imageSource['value']);
});

test('admin ocr preview dispatches a background import batch', function () {
    Bus::fake();
    Storage::fake('local');

    $admin = User::factory()->create(['role' => 'admin']);
    $owner = User::factory()->create(['role' => 'store']);
    $store = Store::create([
        'user_id' => $owner->id,
        'name' => 'Tienda Batch OCR',
        'slug' => 'tienda-batch-ocr',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('admin.products.import.ocr-preview'), [
            'store_id' => $store->id,
            'pdf' => UploadedFile::fake()->create('catalogo.pdf', 100, 'application/pdf'),
        ]);

    $response->assertRedirect(route('admin.products.import'));

    $batch = ProductImportBatch::where('store_id', $store->id)->first();

    expect($batch)->not->toBeNull()
        ->and($batch->status)->toBe(ProductImportBatch::STATUS_PENDING)
        ->and($batch->source_name)->toBe('catalogo.pdf');

    Storage::disk('local')->assertExists($batch->pdf_path);
    Bus::assertDispatched(ProcessProductImportBatch::class);
});

test('admins can load a completed ocr batch preview into the session', function () {
    Storage::fake('local');

    $admin = User::factory()->create(['role' => 'admin']);
    $owner = User::factory()->create(['role' => 'store']);
    $store = Store::create([
        'user_id' => $owner->id,
        'name' => 'Tienda Preview OCR',
        'slug' => 'tienda-preview-ocr',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    $preview = [
        'store_id' => $store->id,
        'store_name' => $store->name,
        'source' => 'PDF con OCR + IA: catalogo.pdf',
        'zip_path' => null,
        'zip_image_count' => 0,
        'zip_files' => [],
        'temp_images' => [],
        'rows' => [],
        'summary' => [
            'total' => 0,
            'valid' => 0,
            'errors' => 0,
            'limit' => null,
            'available_slots' => null,
        ],
    ];
    $batch = ProductImportBatch::create([
        'uuid' => (string) Str::uuid(),
        'store_id' => $store->id,
        'user_id' => $admin->id,
        'type' => ProductImportBatch::TYPE_OCR_PDF,
        'status' => ProductImportBatch::STATUS_COMPLETED,
        'source_name' => 'catalogo.pdf',
        'preview' => $preview,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('admin.products.import.batches.load', $batch));

    $response->assertRedirect(route('admin.products.import'));

    $loadedPreview = session(ProductImportService::SESSION_KEY);

    expect($loadedPreview['batch_id'] ?? null)->toBe($batch->id)
        ->and($loadedPreview['source'] ?? null)->toBe('PDF con OCR + IA: catalogo.pdf');
});
