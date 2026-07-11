<?php

namespace App\Http\Controllers;

use App\Models\DiscountCoupon;
use App\Models\Store;
use App\Services\AdminUpdateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DiscountCouponController extends Controller
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
            if (DiscountCoupon::supportsTable()) {
                $storesQuery->withCount('discountCoupons');
            }
            $stores = $storesQuery->paginate(10);
            $selectedStore = $store?->exists ? $store : null;

            if (! $selectedStore) {
                return view('admin.coupons.index', [
                    'store' => null,
                    'stores' => $stores,
                    'selectedStore' => null,
                    'coupons' => collect(),
                ]);
            }
        }

        $store = $selectedStore ?: $this->currentStore();

        if (! $store->allowsDiscountCoupons()) {
            return view('admin.coupons.index', [
                'store' => $store,
                'coupons' => collect(),
                'selectedStore' => $selectedStore,
                'couponsLocked' => true,
            ]);
        }

        $coupons = $store->discountCoupons()
            ->latest()
            ->get();

        return view('admin.coupons.index', compact('store', 'coupons', 'selectedStore'));
    }

    public function store(Request $request): RedirectResponse
    {
        $store = $this->storeForRequest($request);

        if (! $store->allowsDiscountCoupons()) {
            return $this->redirectToCoupons($store)
                ->with('error', 'El plan ' . $store->planLabel() . ' no incluye cupones de descuento.');
        }

        $request->merge([
            'code' => DiscountCoupon::normalizeCode($request->input('code')),
            'value' => $this->numericInput($request->input('value')),
            'min_subtotal' => $this->numericInput($request->input('min_subtotal')),
            'max_discount_amount' => $this->numericInput($request->input('max_discount_amount')),
        ]);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:60',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('discount_coupons', 'code')->where(fn ($query) => $query->where('store_id', $store->id)),
            ],
            'type' => ['required', Rule::in(array_keys(DiscountCoupon::typeOptions()))],
            'value' => ['required', 'numeric', 'min:1', 'max:99999999'],
            'min_subtotal' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'code.regex' => 'Usa solo letras, numeros, guion o guion bajo en el codigo.',
        ]);

        if ($validated['type'] === DiscountCoupon::TYPE_PERCENT && (float) $validated['value'] > 100) {
            return back()->withInput()->withErrors(['value' => 'El porcentaje no puede ser mayor a 100%.']);
        }

        if (! empty($validated['starts_at']) && ! empty($validated['expires_at']) && strtotime($validated['expires_at']) < strtotime($validated['starts_at'])) {
            return back()->withInput()->withErrors(['expires_at' => 'La fecha de vencimiento no puede ser anterior a la fecha de inicio.']);
        }

        $coupon = $store->discountCoupons()->create([
            'code' => $validated['code'],
            'type' => $validated['type'],
            'value' => $validated['value'],
            'min_subtotal' => $validated['min_subtotal'] ?? 0,
            'max_discount_amount' => $validated['max_discount_amount'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->adminUpdateService->record(
            'Cupon creado',
            $coupon->code . ' en ' . $store->name,
            'cupon',
            $this->redirectToCoupons($store)->getTargetUrl(),
        );

        return $this->redirectToCoupons($store)->with('success', 'Cupon creado.');
    }

    public function toggle(DiscountCoupon $coupon): RedirectResponse
    {
        $store = auth()->user()?->isAdmin()
            ? $coupon->store
            : $this->currentStore();

        abort_unless($store && (int) $coupon->store_id === (int) $store->id, 404);
        abort_unless($store->allowsDiscountCoupons(), 404);

        $coupon->update(['is_active' => ! $coupon->is_active]);

        return $this->redirectToCoupons($store)->with('success', 'Cupon actualizado.');
    }

    public function destroy(DiscountCoupon $coupon): RedirectResponse
    {
        $store = auth()->user()?->isAdmin()
            ? $coupon->store
            : $this->currentStore();

        abort_unless($store && (int) $coupon->store_id === (int) $store->id, 404);
        abort_unless($store->allowsDiscountCoupons(), 404);

        $coupon->delete();

        return $this->redirectToCoupons($store)->with('success', 'Cupon eliminado.');
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

    private function redirectToCoupons(Store $store): RedirectResponse
    {
        if (auth()->user()?->isAdmin()) {
            return redirect()->route('admin.stores.coupons.index', $store);
        }

        return redirect()->route('admin.coupons.index');
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
