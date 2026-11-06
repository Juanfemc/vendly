<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVisit;
use App\Models\AdminUpdate;
use App\Models\Order;
use App\Models\Store;
use App\Models\StoreBanner;
use App\Models\StoreVisit;
use App\Models\User;
use App\Services\SubscriptionStatsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $hasVisitsColumn = Schema::hasColumn('stores', 'views_count');

        if ($user?->isAdmin()) {
            $metricsMonth = $this->normalizeMetricsMonth($request->query('metrics_month'));
            $metricsPeriod = $this->normalizeMetricsPeriod($request->query('metrics_period'), $metricsMonth);
            $dateRange = $this->metricsDateRange($metricsPeriod, $metricsMonth);
            $metricsPeriodLabel = $this->metricsPeriodLabel($metricsPeriod, $metricsMonth);
            $metricsMonthValue = $metricsMonth ?: now()->format('Y-m');

            $storeUsersCount = $this->applyDateRange(User::where('role', 'store'), $dateRange)->count();
            $storesCount = $this->applyDateRange(Store::query(), $dateRange)->count();
            $storeUsers = User::where('role', 'store')->latest()->get();
            $expiringStores = Store::with('user')
                ->subscriptionsEndingWithin(3)
                ->latest()
                ->take(8)
                ->get();
            $expiredStores = Store::with('user')
                ->expiredSubscriptions()
                ->latest()
                ->take(8)
                ->get();
            $expiringUsers = User::where('role', 'store')
                ->where('is_active', true)
                ->whereDate('active_ends_at', '>=', now()->toDateString())
                ->whereDate('active_ends_at', '<=', now()->addDays(7)->toDateString())
                ->orderBy('active_ends_at')
                ->get();
            $paidSalesQuery = $this->applyDateRange(Order::whereIn('status', ['pagado', 'enviado']), $dateRange);
            $returnedSalesQuery = $this->applyDateRange(Order::where('status', 'devuelto'), $dateRange);
            $totalSales = (float) $paidSalesQuery->sum('total') - (float) $returnedSalesQuery->sum('total');
            $totalVisits = $this->visitsForPeriod($metricsPeriod, $dateRange, $hasVisitsColumn);
            $adminUpdates = Schema::hasTable('admin_updates')
                ? AdminUpdate::orderByDesc('id')->take(10)->get()
                : collect();
            $subscriptionStats = app(SubscriptionStatsService::class)->summary($dateRange);
            $metricsPeriodOptions = $this->metricsPeriodLabels();

            return view('dashboard', compact(
                'storeUsersCount',
                'storesCount',
                'storeUsers',
                'expiringStores',
                'expiredStores',
                'expiringUsers',
                'totalSales',
                'totalVisits',
                'adminUpdates',
                'subscriptionStats',
                'metricsPeriod',
                'metricsPeriodLabel',
                'metricsPeriodOptions',
                'metricsMonthValue',
            ));
        }

        $store = $user?->store ?? $user?->stores()->first();
        $productsCount = $store
            ? Product::where('store_id', $store->id)->count()
            : 0;
        $ordersCount = $store
            ? $store->orders()->count()
            : 0;
        $paidOrdersCount = $store
            ? $store->orders()->where('status', 'pagado')->count()
            : 0;
        $shippedOrdersCount = $store
            ? $store->orders()->where('status', 'enviado')->count()
            : 0;
        $totalSales = $store
            ? (float) $store->orders()->whereIn('status', ['pagado', 'enviado'])->sum('total')
                - (float) $store->orders()->where('status', 'devuelto')->sum('total')
            : 0;
        $totalVisits = $store && $hasVisitsColumn ? (int) $store->views_count : 0;
        $onboardingProgress = $store ? $store->onboardingProgress() : 0;
        $onboardingChecklist = $store ? $store->onboardingChecklist() : [];
        $needsOnboarding = $store ? $store->needsOnboarding() : false;
        $subscriptionExpired = $store ? $store->subscriptionExpired() : false;
        $subscriptionEndsSoon = $store ? $store->subscriptionEndsSoon() : false;
        $subscriptionRemainingLabel = $store ? $store->subscriptionRemainingLabel() : null;
        $accountExpiresSoon = $user?->active_ends_at
            && $user->is_active
            && $user->active_ends_at->toDateString() >= now()->toDateString()
            && $user->active_ends_at->toDateString() <= now()->addDays(7)->toDateString();

        $banners = $store
            ? StoreBanner::where('store_id', $store->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->latest()
                ->get()
            : collect();

        return view('dashboard', compact(
            'store',
            'banners',
            'productsCount',
            'ordersCount',
            'paidOrdersCount',
            'shippedOrdersCount',
            'totalSales',
            'totalVisits',
            'onboardingProgress',
            'onboardingChecklist',
            'needsOnboarding',
            'subscriptionExpired',
            'subscriptionEndsSoon',
            'subscriptionRemainingLabel',
            'accountExpiresSoon'
        ));
    }

    public function storeMetrics(Request $request): View
    {
        $user = auth()->user();

        if ($user?->isAdmin()) {
            abort(404);
        }

        $store = $user?->store ?? $user?->stores()->first();
        $currentMonth = now()->startOfMonth();
        $requestedMonth = trim((string) $request->query('month', ''));
        $selectedMonth = $currentMonth;

        if (preg_match('/^\d{4}-\d{2}$/', $requestedMonth)) {
            try {
                $candidateMonth = Carbon::createFromFormat('Y-m', $requestedMonth)->startOfMonth();

                if ($candidateMonth->betweenIncluded($currentMonth->copy()->subMonths(11), $currentMonth)) {
                    $selectedMonth = $candidateMonth;
                }
            } catch (\Throwable) {
                // Keep the current month when the query parameter is invalid.
            }
        }

        $dateRange = [$selectedMonth->copy()->startOfMonth(), $selectedMonth->copy()->endOfMonth()];
        $productsQuery = $store ? Product::where('store_id', $store->id) : null;
        $ordersQuery = $store ? $store->orders() : null;

        if ($dateRange) {
            $productsQuery?->whereBetween('created_at', $dateRange);
            $ordersQuery?->whereBetween('created_at', $dateRange);
        }

        $productsCount = $productsQuery?->count() ?? 0;
        $ordersCount = $ordersQuery?->count() ?? 0;
        $shippedOrdersCount = $ordersQuery ? (clone $ordersQuery)->where('status', 'enviado')->count() : 0;
        $pendingOrdersCount = $ordersQuery ? (clone $ordersQuery)->where('status', 'pendiente')->count() : 0;
        $returnedOrdersCount = $ordersQuery ? (clone $ordersQuery)->where('status', 'devuelto')->count() : 0;
        $paidSales = $ordersQuery
            ? (float) (clone $ordersQuery)->whereIn('status', ['pagado', 'enviado'])->sum('total')
            : 0;
        $pendingSales = $ordersQuery
            ? (float) (clone $ordersQuery)->where('status', 'pendiente')->sum('total')
            : 0;
        $totalSales = $paidSales - ($ordersQuery ? (float) (clone $ordersQuery)->where('status', 'devuelto')->sum('total') : 0);
        $hasVisitHistory = Schema::hasTable('store_visits');
        $visitsQuery = $store && $hasVisitHistory ? StoreVisit::where('store_id', $store->id) : null;

        if ($dateRange) {
            $visitsQuery?->whereBetween('visited_at', $dateRange);
        }

        $totalVisits = $visitsQuery?->count() ?? 0;
        $isPremium = ($store?->plan ?? Store::PLAN_PRO) === Store::PLAN_PREMIUM;
        $showVisits = $isPremium && $request->query('view') === 'visits';
        $uniqueVisitors = 0;
        $dailyVisits = collect();
        $topViewedProducts = collect();

        if ($isPremium && $visitsQuery) {
            $uniqueVisitorQuery = (clone $visitsQuery)
                ->select('ip_hash', 'user_agent_hash')
                ->distinct()
                ->toBase();
            $uniqueVisitors = DB::query()
                ->fromSub($uniqueVisitorQuery, 'unique_visitors')
                ->count();

            $dailyVisits = (clone $visitsQuery)
                ->selectRaw('visited_on, COUNT(*) as visits_count')
                ->groupBy('visited_on')
                ->orderBy('visited_on')
                ->get()
                ->map(fn (StoreVisit $visit) => [
                    'date' => $visit->visited_on?->format('Y-m-d'),
                    'label' => $visit->visited_on?->format('d'),
                    'visits' => (int) $visit->visits_count,
                ])
                ->values();

            if (Schema::hasTable('product_visits')) {
                $topViewedProducts = ProductVisit::query()
                    ->where('product_visits.store_id', $store->id)
                    ->whereBetween('product_visits.visited_at', $dateRange)
                    ->selectRaw('product_id, COUNT(*) as visits_count')
                    ->with('product:id,name,slug')
                    ->groupBy('product_id')
                    ->orderByDesc('visits_count')
                    ->limit(5)
                    ->get();
            }
        }

        $visitConversionRate = $uniqueVisitors > 0
            ? round(($shippedOrdersCount / $uniqueVisitors) * 100, 2)
            : 0;
        $selectedMonthLabel = ucfirst($selectedMonth->locale('es')->translatedFormat('F \d\e Y'));

        $monthOptions = collect(range(0, 11))->map(function (int $monthsAgo) use ($currentMonth) {
            $month = $currentMonth->copy()->subMonths($monthsAgo);

            return [
                'value' => $month->format('Y-m'),
                'label' => ucfirst($month->locale('es')->translatedFormat('F \d\e Y')),
            ];
        });

        return view('admin.metrics.index', compact(
            'store',
            'hasVisitHistory',
            'selectedMonth',
            'selectedMonthLabel',
            'monthOptions',
            'paidSales',
            'pendingSales',
            'totalSales',
            'pendingOrdersCount',
            'returnedOrdersCount',
            'productsCount',
            'ordersCount',
            'totalVisits',
            'shippedOrdersCount',
            'isPremium',
            'showVisits',
            'uniqueVisitors',
            'dailyVisits',
            'topViewedProducts',
            'visitConversionRate',
        ));
    }

    private function normalizeMetricsPeriod(mixed $period, ?string $metricsMonth = null): string
    {
        $period = (string) $period;

        if ($period === 'custom_month' && $metricsMonth) {
            return 'custom_month';
        }

        return array_key_exists($period, $this->metricsPeriodLabels()) ? $period : 'month';
    }

    private function metricsPeriodLabels(): array
    {
        return [
            'week' => 'Semanal',
            'month' => 'Mensual',
            'total' => 'Total',
        ];
    }

    private function metricsDateRange(string $period, ?string $metricsMonth = null): ?array
    {
        return match ($period) {
            'week' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'month' => [now()->startOfMonth(), now()->endOfDay()],
            'custom_month' => $metricsMonth
                ? [
                    Carbon::createFromFormat('Y-m', $metricsMonth)->startOfMonth(),
                    Carbon::createFromFormat('Y-m', $metricsMonth)->endOfMonth(),
                ]
                : [now()->startOfMonth(), now()->endOfDay()],
            default => null,
        };
    }

    private function metricsPeriodLabel(string $period, ?string $metricsMonth = null): string
    {
        if ($period === 'custom_month' && $metricsMonth) {
            return 'Mes ' . Carbon::createFromFormat('Y-m', $metricsMonth)->format('m/Y');
        }

        return $this->metricsPeriodLabels()[$period] ?? 'Mensual';
    }

    private function normalizeMetricsMonth(mixed $month): ?string
    {
        $month = trim((string) $month);

        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m', $month)->format('Y-m');
        } catch (\Throwable) {
            return null;
        }
    }

    private function applyDateRange(Builder $query, ?array $dateRange): Builder
    {
        if (! $dateRange) {
            return $query;
        }

        return $query->whereBetween('created_at', $dateRange);
    }

    private function visitsForPeriod(string $period, ?array $dateRange, bool $hasVisitsColumn): int
    {
        if (Schema::hasTable('store_visits')) {
            $visitsQuery = StoreVisit::query();

            if ($dateRange) {
                $visitsQuery->whereBetween('visited_at', $dateRange);
            }

            return $visitsQuery->count();
        }

        return $period === 'total' && $hasVisitsColumn ? (int) Store::sum('views_count') : 0;
    }

}
