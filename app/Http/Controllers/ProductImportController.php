<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Services\AdminUpdateService;
use App\Services\ProductImportService;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ProductImportController extends Controller
{
    private const PRODUCT_FILE_MAX_KB = 5120;
    private const IMAGES_ZIP_MAX_KB = 51200;

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

        return view('admin.products.import', [
            'store' => $store,
            'stores' => $stores,
            'preview' => $preview,
            'productFileMaxKb' => self::PRODUCT_FILE_MAX_KB,
            'imagesZipMaxKb' => self::IMAGES_ZIP_MAX_KB,
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
