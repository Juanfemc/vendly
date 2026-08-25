<?php

namespace App\Http\Controllers;

use App\Models\PriceList;
use App\Models\Store;
use App\Services\AdminUpdateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PriceListController extends Controller
{
    public function __construct(
        private AdminUpdateService $adminUpdateService,
    ) {
    }

    public function index(?Store $store = null): View
    {
        $selectedStore = null;

        if (auth()->user()?->isAdmin()) {
            $storesQuery = Store::orderBy('name');
            if (Store::supportsPriceListsTables()) {
                $storesQuery->withCount('priceLists');
            }
            $stores = $storesQuery->paginate(10);
            $selectedStore = $store?->exists ? $store : null;

            if (! $selectedStore) {
                return view('admin.price-lists.index', [
                    'store' => null,
                    'stores' => $stores,
                    'selectedStore' => null,
                    'priceLists' => collect(),
                    'products' => collect(),
                    'editingPriceList' => null,
                    'productPrices' => collect(),
                ]);
            }
        }

        $store = $selectedStore ?: $this->currentStore();

        if (! $store->allowsPriceLists()) {
            return view('admin.price-lists.index', [
                'store' => $store,
                'priceLists' => collect(),
                'products' => collect(),
                'selectedStore' => $selectedStore,
                'editingPriceList' => null,
                'productPrices' => collect(),
                'priceListsLocked' => true,
            ]);
        }

        if (! Store::supportsPriceListsTables()) {
            return view('admin.price-lists.index', [
                'store' => $store,
                'priceLists' => collect(),
                'products' => collect(),
                'selectedStore' => $selectedStore,
                'editingPriceList' => null,
                'productPrices' => collect(),
                'priceListsUnavailable' => true,
            ]);
        }

        $editingPriceList = request('edit')
            ? $store->priceLists()->find(request('edit'))
            : null;
        $productPrices = $editingPriceList
            ? $editingPriceList->productPrices()->pluck('price', 'product_id')
            : collect();
        $priceLists = $store->priceLists()
            ->withCount('productPrices')
            ->latest()
            ->get();
        $products = $store->products()
            ->orderBy('name')
            ->get(['id', 'name', 'price']);

        return view('admin.price-lists.index', compact('store', 'priceLists', 'products', 'selectedStore', 'editingPriceList', 'productPrices'));
    }

    public function store(Request $request): RedirectResponse
    {
        $store = $this->storeForRequest($request);

        if (! $store->allowsPriceLists()) {
            return $this->redirectToPriceLists($store)
                ->with('error', 'El plan ' . $store->planLabel() . ' no incluye listas de precios.');
        }

        if (! Store::supportsPriceListsTables()) {
            return $this->redirectToPriceLists($store)
                ->with('error', 'Falta ejecutar las migraciones para activar listas de precios.');
        }

        $validated = $this->validatePriceList($request, $store);

        $priceList = $store->priceLists()->create($validated);

        $this->adminUpdateService->record(
            'Lista de precios creada',
            $priceList->name . ' en ' . $store->name,
            'lista de precios',
            $this->redirectToPriceLists($store, ['edit' => $priceList->id])->getTargetUrl(),
        );

        return $this->redirectToPriceLists($store, ['edit' => $priceList->id])->with('success', 'Lista de precios creada.');
    }

    public function update(Request $request, PriceList $priceList): RedirectResponse
    {
        $store = auth()->user()?->isAdmin() ? $priceList->store : $this->currentStore();

        abort_unless($store && (int) $priceList->store_id === (int) $store->id, 404);

        if (! $store->allowsPriceLists()) {
            return $this->redirectToPriceLists($store)
                ->with('error', 'El plan ' . $store->planLabel() . ' no incluye listas de precios.');
        }

        if (! Store::supportsPriceListsTables()) {
            return $this->redirectToPriceLists($store)
                ->with('error', 'Falta ejecutar las migraciones para activar listas de precios.');
        }

        $validated = $this->validatePriceList($request, $store, $priceList);
        $priceList->update($validated);

        $allowedProductIds = $store->products()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $prices = collect($request->input('prices', []));

        foreach ($allowedProductIds as $productId) {
            $value = $this->numericInput($prices->get($productId));

            if ($value === null || (float) $value <= 0) {
                $priceList->productPrices()->where('product_id', $productId)->delete();
                continue;
            }

            $priceList->productPrices()->updateOrCreate(
                ['product_id' => $productId],
                ['price' => $value],
            );
        }

        return $this->redirectToPriceLists($store, ['edit' => $priceList->id])->with('success', 'Lista de precios actualizada.');
    }

    public function destroy(PriceList $priceList): RedirectResponse
    {
        $store = auth()->user()?->isAdmin() ? $priceList->store : $this->currentStore();

        abort_unless($store && (int) $priceList->store_id === (int) $store->id, 404);
        abort_unless($store->allowsPriceLists(), 404);

        $priceList->delete();

        return $this->redirectToPriceLists($store)->with('success', 'Lista de precios eliminada.');
    }

    private function validatePriceList(Request $request, Store $store, ?PriceList $priceList = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'access_code' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('price_lists', 'access_code')
                    ->where(fn ($query) => $query->where('store_id', $store->id))
                    ->ignore($priceList?->id),
            ],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['starts_at']) && ! empty($validated['ends_at']) && strtotime($validated['ends_at']) < strtotime($validated['starts_at'])) {
            throw ValidationException::withMessages([
                'ends_at' => 'La fecha final no puede ser anterior a la fecha inicial.',
            ]);
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['access_code'] = trim((string) ($validated['access_code'] ?? '')) ?: null;

        return $validated;
    }

    private function currentStore(): Store
    {
        $store = auth()->user()?->store ?? auth()->user()?->stores()->first();

        abort_if(! $store, 404);

        return $store;
    }

    private function storeForRequest(Request $request): Store
    {
        if (auth()->user()?->isAdmin()) {
            return Store::findOrFail($request->integer('store_id'));
        }

        return $this->currentStore();
    }

    private function redirectToPriceLists(Store $store, array $query = []): RedirectResponse
    {
        $route = auth()->user()?->isAdmin()
            ? route('admin.stores.price-lists.index', $store)
            : route('admin.price-lists.index');

        return redirect($query ? $route . '?' . http_build_query($query) : $route);
    }

    private function numericInput(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);

        if (str_contains($value, ',') && str_contains($value, '.')) {
            return str_replace(['.', ','], ['', '.'], $value);
        }

        if (str_contains($value, ',')) {
            return str_replace(',', '.', $value);
        }

        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            return str_replace('.', '', $value);
        }

        return $value;
    }
}
