<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequest;
use App\Http\Requests\StoreSettingsRequest;
use App\Http\Requests\StoreWithUserRequest;
use App\Models\ColombiaLocation;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\AdminUpdateService;
use App\Services\AiCreditService;
use App\Services\CustomerFollowupScheduler;
use App\Services\StoreFileService;
use App\Services\StorefrontUrlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function __construct(
        private StoreFileService $storeFileService,
        private AdminUpdateService $adminUpdateService,
        private AiCreditService $aiCreditService,
        private CustomerFollowupScheduler $customerFollowups,
        private StorefrontUrlService $storefrontUrls,
    ) {
    }

    public function index(Request $request)
    {
        $this->authorize('create', Store::class);

        $storeStatus = $this->normalizeStoreStatus($request->query('store_status'));
        $storeFilterOptions = $this->storeStatusLabels();
        $storesQuery = Store::with(['user', 'creatorAdmin'])->latest();

        $this->applyStoreStatusFilter($storesQuery, $storeStatus);

        $stores = $storesQuery->get();
        $storesCount = $stores->count();

        return view('admin.stores.index', compact('stores', 'storeStatus', 'storeFilterOptions', 'storesCount'));
    }

    public function create()
    {
        $this->authorize('create', Store::class);

        $users = User::where('role', 'store')
            ->whereDoesntHave('store')
            ->orderBy('name')
            ->get();

        return view('admin.stores.create', compact('users'));
    }

    public function createWithUser()
    {
        $this->authorize('create', Store::class);

        return view('admin.stores.create-with-user');
    }

    public function visits()
    {
        if (! Schema::hasColumn('stores', 'views_count')) {
            $stores = new LengthAwarePaginator([], 0, 10);

            return view('admin.stores.visits', [
                'stores' => $stores,
                'totalVisits' => 0,
                'needsMigration' => true,
            ]);
        }

        if (! auth()->user()?->isAdmin()) {
            $store = auth()->user()?->store ?? auth()->user()?->stores()->first();

            abort_if(! $store, 404);
            abort_unless($store->allowsVisitStats(), 403);

            $stores = new LengthAwarePaginator(collect([$store]), 1, 10);

            return view('admin.stores.visits', [
                'stores' => $stores,
                'totalVisits' => (int) ($store->views_count ?? 0),
                'needsMigration' => false,
                'selectedStore' => $store,
            ]);
        }

        $this->authorize('create', Store::class);

        $stores = Store::with('user')
            ->where('views_count', '>', 0)
            ->orderByDesc('views_count')
            ->orderBy('name')
            ->paginate(10);
        $totalVisits = (int) Store::where('views_count', '>', 0)->sum('views_count');

        return view('admin.stores.visits', [
            'stores' => $stores,
            'totalVisits' => $totalVisits,
            'needsMigration' => false,
        ]);
    }

    public function store(StoreRequest $request)
    {
        $this->authorize('create', Store::class);

        $store = Store::create(array_merge($request->storeData(), $this->storeFileService->storeUploadedImages($request), [
            'created_by_admin_id' => auth()->id(),
        ]));

        $this->adminUpdateService->record(
            'Tienda creada',
            $store->name,
            'tienda',
            route('admin.stores.edit', $store)
        );

        $this->scheduleSubscriptionReminders($store);

        return redirect('/admin/stores')->with('success', 'Tienda creada.');
    }

    public function storeWithUser(StoreWithUserRequest $request): RedirectResponse
    {
        $this->authorize('create', Store::class);

        [$user, $store] = DB::transaction(function () use ($request) {
            $user = User::create([
                ...$request->userData(),
                'password' => Hash::make($request->validated('password')),
                ...$this->activePeriodData($request),
            ]);

            $store = Store::create(array_merge($request->storeData(), $this->storeFileService->storeUploadedImages($request), [
                'user_id' => $user->id,
                'created_by_admin_id' => auth()->id(),
            ]));

            return [$user, $store];
        });

        $this->adminUpdateService->record(
            'Cliente y tienda creados',
            $user->name . ' / ' . $store->name,
            'tienda',
            route('admin.stores.edit', $store)
        );

        $this->scheduleSubscriptionReminders($store);

        return redirect()
            ->route('admin.stores.edit', $store)
            ->with('success', 'Cliente y tienda creados correctamente.');
    }

    public function edit(Store $store)
    {
        $this->authorize('update', $store);

        $users = User::where('role', 'store')
            ->where(function ($query) use ($store) {
                $query
                    ->whereDoesntHave('store')
                    ->orWhere('id', $store->user_id);
            })
            ->orderBy('name')
            ->get();

        return view('admin.stores.edit', compact('store', 'users'));
    }

    public function update(StoreRequest $request, Store $store)
    {
        $this->authorize('update', $store);

        $storeData = $request->storeData();
        $requestedPlan = $storeData['plan'] ?? $store->plan;

        if (! $this->storeCanUsePlan($store, $requestedPlan)) {
            return back()
                ->withInput()
                ->with('error', 'Esta tienda tiene mas productos que los permitidos por el plan ' . Store::planOptions()[$requestedPlan] . '.');
        }

        $store->update($this->storeFileService->replaceUploadedImages($store, $request, $storeData));
        $this->enforcePlanLimits($store);

        $this->adminUpdateService->record(
            'Tienda actualizada',
            $store->name,
            'tienda',
            route('admin.stores.edit', $store)
        );

        $this->scheduleSubscriptionReminders($store->refresh());

        return redirect('/admin/stores')->with('success', 'Tienda actualizada.');
    }

    public function destroy(Store $store)
    {
        $this->authorize('update', $store);

        $this->storeFileService->deleteStoreFiles($store);

        $storeName = $store->name;
        $store->delete();

        $this->adminUpdateService->record('Tienda eliminada', $storeName, 'tienda');

        return redirect('/admin/stores')->with('success', 'Tienda eliminada.');
    }

    public function addAiCredits(Request $request, Store $store): RedirectResponse
    {
        $this->authorize('update', $store);
        abort_unless(auth()->user()?->isAdmin(), 403);
        abort_unless($store->allowsAiContent(), 403);

        $validated = $request->validate([
            'package_key' => ['required', 'string', Rule::in(array_keys(AiCreditService::PACKAGES))],
        ]);

        $transaction = $this->aiCreditService->addPackage($store, $validated['package_key'], auth()->id());

        $this->adminUpdateService->record(
            'Creditos IA agregados',
            $store->name . ' recibio ' . number_format($transaction->amount, 0, ',', '.') . ' creditos IA',
            'tienda',
            route('admin.stores.edit', $store)
        );

        return back()->with('success', 'Creditos IA agregados a ' . $store->name . '.');
    }

    public function activateSubscription(Request $request, Store $store): RedirectResponse
    {
        $this->authorize('update', $store);
        abort_unless(auth()->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'plan' => ['nullable', Rule::in(array_keys(Store::planOptions()))],
        ]);

        $requestedPlan = $validated['plan'] ?? $store->plan ?? Store::PLAN_PRO;

        if (! $this->storeCanUsePlan($store, $requestedPlan)) {
            return back()
                ->with('error', 'Esta tienda tiene mas productos que los permitidos por el plan ' . Store::planOptions()[$requestedPlan] . '.');
        }

        $store->activateSubscription((int) $validated['duration_days'], $requestedPlan);
        $this->enforcePlanLimits($store->refresh());

        $this->scheduleSubscriptionReminders($store);

        $this->adminUpdateService->record(
            'Suscripcion activada',
            $store->name . ' quedo activa por ' . (int) $validated['duration_days'] . ' dia(s)',
            'tienda',
            route('admin.stores.edit', $store)
        );

        return back()->with('success', 'Suscripcion activada para ' . $store->name . '.');
    }

    public function settings()
    {
        $store = auth()->user()?->store ?? auth()->user()?->stores()->first();

        abort_if(! $store, 404);
        $this->authorize('update', $store);

        $colombiaLocations = ColombiaLocation::citiesForSelect();
        $storeUrl = $this->storefrontUrls->publicHome($store);

        return view('admin.stores.settings', compact('store', 'colombiaLocations', 'storeUrl'));
    }

    public function updateSettings(StoreSettingsRequest $request)
    {
        $store = auth()->user()?->store ?? auth()->user()?->stores()->first();

        abort_if(! $store, 404);
        $this->authorize('update', $store);

        $store->update($this->storeFileService->replaceUploadedImages($store, $request, $request->settingsData()));
        $this->enforcePlanLimits($store);

        $this->adminUpdateService->record(
            'Configuracion de tienda actualizada',
            $store->name,
            'tienda',
            route('admin.stores.edit', $store)
        );

        return redirect('/admin/store-settings')->with('success', 'Configuración de tienda actualizada.');
    }

    private function enforcePlanLimits(Store $store): void
    {
        if (! $store->allowsCategories()) {
            Product::where('store_id', $store->id)->update(['category' => null]);
        }

        if (! $store->allowsProductGallery()) {
            Product::where('store_id', $store->id)->update(['images' => null]);
        }

        if (Store::supportsShippingMethodsColumn() && ! $store->allowsShippingMethods()) {
            $data = ['shipping_methods' => []];

            if (Store::supportsLocalDeliveryColumns()) {
                $data['local_delivery_area'] = null;
                if (Store::supportsLocalDeliveryCityCodeColumn()) {
                    $data['local_delivery_city_code'] = null;
                }
                $data['local_delivery_cost'] = null;
                $data['outside_delivery_cost'] = null;
            }

            $store->forceFill($data)->save();
        }

        if (! $store->allowsFullCustomization()) {
            $customizationData = [
                'brand_color' => null,
                'background_color' => null,
                'text_color' => Store::automaticTextColorFor(null),
                'font_family' => 'system',
                'responsive_product_columns' => 2,
                'show_hero_products_action' => false,
            ];

            if (Store::supportsHeroOverlayColumns()) {
                $customizationData['show_hero_overlay'] = false;
                $customizationData['hero_overlay_eyebrow'] = null;
                $customizationData['hero_overlay_title'] = null;
                $customizationData['hero_overlay_button_text'] = null;
                $customizationData['hero_overlay_button_url'] = null;
            }

            $store->forceFill($customizationData)->save();
        }

        if (Store::supportsCustomDomainColumns() && ! $store->allowsCustomDomain()) {
            $store->forceFill([
                'custom_domain' => null,
                'custom_domain_status' => Store::CUSTOM_DOMAIN_PENDING,
                'custom_domain_verified_at' => null,
            ])->save();
        }
    }

    private function storeCanUsePlan(Store $store, string $plan): bool
    {
        if ($plan === $store->plan) {
            return true;
        }

        $limit = match ($plan) {
            Store::PLAN_BASIC => Store::BASIC_PRODUCT_LIMIT,
            Store::PLAN_PRO => Store::PRO_PRODUCT_LIMIT,
            default => null,
        };

        return $limit === null || $store->products()->count() <= $limit;
    }

    private function normalizeStoreStatus(?string $status): string
    {
        return array_key_exists($status, $this->storeStatusLabels()) ? $status : 'all';
    }

    private function storeStatusLabels(): array
    {
        return [
            'all' => 'Todas',
            'active' => 'Activas',
            'trial' => 'En prueba',
            'paid' => 'Pagas',
        ];
    }

    private function applyStoreStatusFilter(Builder $query, string $status): void
    {
        if ($status === 'all') {
            return;
        }

        $today = now()->toDateString();

        if (! Store::supportsSubscriptionColumns()) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } else {
                $query->whereRaw('1 = 0');
            }

            return;
        }

        match ($status) {
            'active' => $query->publiclyAvailable(),
            'trial' => $query
                ->publiclyAvailable()
                ->where('subscription_status', Store::SUBSCRIPTION_TRIALING)
                ->whereNotNull('trial_ends_at')
                ->whereDate('trial_ends_at', '>=', $today),
            'paid' => $query
                ->publiclyAvailable()
                ->whereIn('plan', [Store::PLAN_PRO, Store::PLAN_PREMIUM])
                ->where(function (Builder $statusQuery) {
                    $statusQuery
                        ->whereNull('subscription_status')
                        ->orWhere('subscription_status', Store::SUBSCRIPTION_ACTIVE);
                })
                ->where(function (Builder $dateQuery) use ($today) {
                    $dateQuery
                        ->whereNull('subscription_ends_at')
                        ->orWhereDate('subscription_ends_at', '>=', $today);
                }),
            default => null,
        };
    }

    private function activePeriodData(Request $request): array
    {
        $startsAt = $request->filled('active_starts_at')
            ? Carbon::parse($request->input('active_starts_at'))->toDateString()
            : null;

        $durationDays = $request->filled('active_duration_days')
            ? (int) $request->input('active_duration_days')
            : null;

        return [
            'active_starts_at' => $startsAt,
            'active_duration_days' => $durationDays,
            'active_ends_at' => $startsAt && $durationDays
                ? Carbon::parse($startsAt)->addDays($durationDays)->toDateString()
                : null,
        ];
    }

    private function scheduleSubscriptionReminders(Store $store): void
    {
        try {
            $store = $store->refresh();

            if ($store->subscriptionStatus() !== Store::SUBSCRIPTION_ACTIVE
                || ! $store->subscription_ends_at
                || ! $store->is_active
                || ! $store->user?->isActive()) {
                $this->customerFollowups->cancelPendingSubscriptionReminders(
                    $store,
                    'El plan de la tienda cambio o ya no tiene fecha de vencimiento.',
                );

                return;
            }

            $this->customerFollowups->scheduleSubscriptionReminders($store);
        } catch (\Throwable $exception) {
            Log::warning('No se pudieron programar recordatorios de suscripcion.', [
                'store_id' => $store->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
    
}
