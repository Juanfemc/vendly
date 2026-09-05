<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessProductImportBatch;
use App\Models\Product;
use App\Models\ProductImportBatch;
use App\Models\Store;
use App\Services\AdminUpdateService;
use App\Services\ProductImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ProductImportController extends Controller
{
    private const PRODUCT_FILE_MAX_KB = 5120;
    private const IMAGES_ZIP_MAX_KB = 51200;
    private const OCR_PDF_MAX_KB = 25600;

    public function __construct(
        private ProductImportService $productImportService,
        private AdminUpdateService $adminUpdateService,
    ) {
    }

    public function create()
    {
        $this->authorize('create', Product::class);

        $store = $this->currentStore();
        $stores = auth()->user()?->isAdmin()
            ? Store::orderBy('name')->get()
            : collect();
        $preview = session(ProductImportService::SESSION_KEY);
        $ocrBatches = auth()->user()?->isAdmin()
            ? ProductImportBatch::with('store')
                ->where('type', ProductImportBatch::TYPE_OCR_PDF)
                ->latest()
                ->take(10)
                ->get()
            : collect();

        return view('admin.products.import', [
            'store' => $store,
            'stores' => $stores,
            'preview' => $preview,
            'ocrBatches' => $ocrBatches,
            'productFileMaxKb' => self::PRODUCT_FILE_MAX_KB,
            'imagesZipMaxKb' => self::IMAGES_ZIP_MAX_KB,
            'ocrPdfMaxKb' => self::OCR_PDF_MAX_KB,
        ]);
    }

    public function preview(Request $request)
    {
        $this->authorize('create', Product::class);

        $validated = $request->validate([
            'store_id' => auth()->user()?->isAdmin()
                ? ['required', 'integer', 'exists:stores,id']
                : ['nullable'],
            'file' => ['required', 'file', 'max:' . self::PRODUCT_FILE_MAX_KB, 'mimes:csv,txt,xlsx'],
            'images_zip' => ['nullable', 'file', 'max:' . self::IMAGES_ZIP_MAX_KB, 'mimes:zip'],
        ], [
            'file.required' => 'Sube la plantilla CSV o XLSX antes de revisar.',
            'file.max' => 'La plantilla no puede pesar más de 5 MB. Si tiene imágenes incrustadas, usa la columna imagen o imagen_url.',
            'file.mimes' => 'La plantilla debe ser CSV o XLSX.',
            'images_zip.max' => 'El ZIP de imágenes no puede pesar más de 50 MB.',
            'images_zip.mimes' => 'El archivo de imágenes debe ser un ZIP.',
        ]);

        $store = $this->storeForRequest($validated);

        if (! $store) {
            return back()->with('error', 'No tienes tienda creada.');
        }

        try {
            $this->productImportService->deletePreviewFiles(session(ProductImportService::SESSION_KEY));
            $preview = $this->productImportService->preview($store, $request->file('file'), $request->file('images_zip'));
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        session([ProductImportService::SESSION_KEY => $preview]);

        return redirect()
            ->route('admin.products.import')
            ->with('success', 'Archivo revisado. Confirma la importacion para crear los productos.');
    }

    public function previewOcr(Request $request)
    {
        $this->authorize('create', Product::class);
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'pdf' => ['required', 'file', 'max:' . self::OCR_PDF_MAX_KB, 'mimes:pdf'],
            'images_zip' => ['nullable', 'file', 'max:' . self::IMAGES_ZIP_MAX_KB, 'mimes:zip'],
        ], [
            'store_id.required' => 'Selecciona la tienda donde se importarán los productos.',
            'pdf.required' => 'Sube un PDF antes de iniciar el OCR.',
            'pdf.max' => 'El PDF no puede pesar más de 25 MB.',
            'pdf.mimes' => 'El archivo debe ser un PDF.',
            'images_zip.max' => 'El ZIP de imágenes no puede pesar más de 50 MB.',
            'images_zip.mimes' => 'El archivo de imágenes debe ser un ZIP.',
        ]);

        $store = $this->storeForRequest($validated);

        if (! $store) {
            return back()->with('error', 'No encontramos la tienda para esta importacion.');
        }

        $uuid = (string) Str::uuid();
        $pdf = $request->file('pdf');
        $zip = $request->file('images_zip');
        $pdfPath = $pdf->storeAs('imports/product-ocr', $uuid . '.pdf', 'local');
        $zipPath = $zip ? $zip->storeAs('imports/product-ocr', $uuid . '-images.zip', 'local') : null;

        $batch = ProductImportBatch::create([
            'uuid' => $uuid,
            'store_id' => $store->id,
            'user_id' => $request->user()?->id,
            'type' => ProductImportBatch::TYPE_OCR_PDF,
            'status' => ProductImportBatch::STATUS_PENDING,
            'source_name' => $pdf->getClientOriginalName(),
            'pdf_path' => $pdfPath,
            'zip_path' => $zipPath,
            'zip_original_name' => $zip?->getClientOriginalName(),
        ]);

        ProcessProductImportBatch::dispatch($batch->id);

        return redirect()
            ->route('admin.products.import')
            ->with('success', 'PDF recibido. Lo procesaremos en segundo plano; vuelve a esta pantalla para cargar la vista previa cuando termine.');
    }

    public function loadBatchPreview(ProductImportBatch $batch)
    {
        $this->authorize('create', Product::class);
        abort_unless(auth()->user()?->isAdmin(), 403);

        if ($batch->status !== ProductImportBatch::STATUS_COMPLETED || ! is_array($batch->preview)) {
            return redirect()
                ->route('admin.products.import')
                ->with('error', 'Ese PDF todavía no tiene una vista previa lista.');
        }

        $this->productImportService->deletePreviewFiles(session(ProductImportService::SESSION_KEY));
        $preview = $batch->preview;
        $preview['batch_id'] = $batch->id;
        session([ProductImportService::SESSION_KEY => $preview]);

        return redirect()
            ->route('admin.products.import')
            ->with('success', 'Vista previa cargada. Revisa los productos antes de importar.');
    }

    public function assignImages(Request $request)
    {
        $this->authorize('create', Product::class);
        abort_unless($request->user()?->isAdmin(), 403);

        $preview = session(ProductImportService::SESSION_KEY);

        if (! is_array($preview)) {
            return redirect()
                ->route('admin.products.import')
                ->with('error', 'Sube un PDF primero para asignar imagenes.');
        }

        $validated = $request->validate([
            'image_from_zip' => ['nullable', 'array'],
            'image_from_zip.*' => ['nullable', 'string', 'max:255'],
            'manual_images' => ['nullable', 'array'],
            'manual_images.*' => ['nullable', 'image', 'max:8192'],
        ], [
            'manual_images.*.image' => 'Las imágenes manuales deben ser JPG, PNG o WebP.',
            'manual_images.*.max' => 'Cada imagen manual puede pesar máximo 8 MB.',
        ]);

        try {
            $preview = $this->productImportService->assignPreviewImages(
                $preview,
                $validated['image_from_zip'] ?? [],
                $request->file('manual_images', []),
            );
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('admin.products.import')
                ->with('error', $exception->getMessage());
        }

        session([ProductImportService::SESSION_KEY => $preview]);

        return redirect()
            ->route('admin.products.import')
            ->with('success', 'Imagenes asignadas. Revisa la vista previa y confirma la importacion.');
    }

    public function store()
    {
        $this->authorize('create', Product::class);

        $preview = session(ProductImportService::SESSION_KEY);

        if (! is_array($preview)) {
            return redirect()
                ->route('admin.products.import')
                ->with('error', 'Sube un archivo primero para revisar la importacion.');
        }

        $store = $this->storeForRequest(['store_id' => $preview['store_id'] ?? null]);

        if (! $store) {
            return redirect()
                ->route('admin.products.import')
                ->with('error', 'No encontramos la tienda de esta importacion.');
        }

        try {
            $created = $this->productImportService->import($store, $preview, (int) auth()->id());
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.products.import')
                ->with('error', $exception->getMessage());
        }

        $this->productImportService->deletePreviewFiles($preview);
        if (! empty($preview['batch_id'])) {
            ProductImportBatch::whereKey($preview['batch_id'])->update([
                'status' => ProductImportBatch::STATUS_IMPORTED,
                'preview' => null,
                'imported_at' => now(),
            ]);
        }
        session()->forget(ProductImportService::SESSION_KEY);

        $this->adminUpdateService->record(
            'Productos importados',
            $created . ' producto(s) en ' . $store->name,
            'producto',
            route('admin.products.index')
        );

        return redirect()
            ->route('admin.products.index')
            ->with('success', $created . ' producto(s) importado(s).');
    }

    public function destroy()
    {
        $this->authorize('create', Product::class);

        $this->productImportService->deletePreviewFiles(session(ProductImportService::SESSION_KEY));
        session()->forget(ProductImportService::SESSION_KEY);

        return redirect()
            ->route('admin.products.import')
            ->with('success', 'Previsualizacion descartada.');
    }

    public function template(): StreamedResponse
    {
        $this->authorize('create', Product::class);

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'wb');

            foreach ($this->productImportService->templateRows() as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 'plantilla-productos-vendly.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function currentStore(): ?Store
    {
        $user = auth()->user();

        return $user?->store ?? $user?->stores()->first();
    }

    private function storeForRequest(array $validated): ?Store
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        if ($user->isAdmin()) {
            return ! empty($validated['store_id'])
                ? Store::find($validated['store_id'])
                : $this->currentStore();
        }

        return $this->currentStore();
    }
}
