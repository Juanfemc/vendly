@extends('layouts.admin')

@section('meta_title', 'Vendly - Panel.')

@section('content')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ filemtime(public_path('css/dashboard.css')) }}">

<div class="header">
    <h2>{{ auth()->user()->isAdmin() ? 'Dashboard admin' : 'Dashboard de tienda' }}</h2>
</div>

@if (!auth()->user()->isAdmin() && !empty($banners) && $banners->isNotEmpty())
    <div class="list-card dashboard-banner-card dashboard-banner-card--top">
        <div id="dashboard-banner-slider" class="dashboard-slider">
            @foreach ($banners as $index => $banner)
                <div class="dashboard-slide {{ $index === 0 ? 'is-active' : '' }}">
                    <div class="dashboard-slide-media">
                        <img src="{{ asset('storage/' . $banner->image) }}" alt="{{ $banner->title ?: 'Banner' }}">
                        @if ($banner->title || $banner->subtitle || $banner->link)
                            <div class="dashboard-slide-overlay">
                                @if ($banner->title)
                                    <div class="dashboard-slide-title">{{ $banner->title }}</div>
                                @endif
                                @if ($banner->subtitle)
                                    <div class="dashboard-slide-text">{{ $banner->subtitle }}</div>
                                @endif
                                @if ($banner->link)
                                    <div class="dashboard-slide-actions">
                                        <a href="{{ $banner->link }}" class="btn dashboard-slide-link">Ver mas</a>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        @if ($banners->count() > 1)
            <div class="dashboard-dots">
                @foreach ($banners as $index => $banner)
                    <button type="button" class="dashboard-dot {{ $index === 0 ? 'is-active' : '' }}" data-slide="{{ $index }}"></button>
                @endforeach
            </div>
        @endif
    </div>
@endif

@if (auth()->user()->isAdmin())
    @if (!empty($expiredStores) && $expiredStores->isNotEmpty())
        <div class="dashboard-notification dashboard-notification--warning">
            <div>
                <strong>Tiendas vencidas</strong>
                <p>Estas tiendas ya no estan publicas. Valida el pago y activalas desde el panel.</p>
            </div>

            <div class="dashboard-notification-list">
                @foreach ($expiredStores as $expiredStore)
                    <a href="{{ route('admin.stores.edit', $expiredStore) }}" class="dashboard-notification-item">
                        <span>{{ $expiredStore->name }}</span>
                        <strong>{{ $expiredStore->subscriptionStatusLabel() }}</strong>
                        <small>{{ $expiredStore->user->name ?? 'Sin usuario' }} · {{ $expiredStore->subscriptionRemainingLabel() }}</small>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if (!empty($expiringStores) && $expiringStores->isNotEmpty())
        <div class="dashboard-notification">
            <div>
                <strong>Tiendas por vencer</strong>
                <p>Revisa estas tiendas antes de que salgan de publicacion.</p>
            </div>

            <div class="dashboard-notification-list">
                @foreach ($expiringStores as $expiringStore)
                    <a href="{{ route('admin.stores.edit', $expiringStore) }}" class="dashboard-notification-item">
                        <span>{{ $expiringStore->name }}</span>
                        <strong>{{ $expiringStore->subscriptionRemainingLabel() }}</strong>
                        <small>{{ $expiringStore->user->name ?? 'Sin usuario' }} · {{ $expiringStore->subscriptionStatusLabel() }}</small>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if (!empty($expiringUsers) && $expiringUsers->isNotEmpty())
        <div class="dashboard-notification">
            <div>
                <strong>Notificaciones</strong>
                <p>Hay usuarios cuyo tiempo activo esta por finalizar.</p>
            </div>

            <div class="dashboard-notification-list">
                @foreach ($expiringUsers as $expiringUser)
                    <a href="{{ route('admin.users.edit', $expiringUser) }}" class="dashboard-notification-item">
                        <span>{{ $expiringUser->name }}</span>
                        <strong>{{ $expiringUser->active_remaining_label }}</strong>
                        <small>Finaliza: {{ $expiringUser->active_ends_at->format('d/m/Y') }}</small>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div data-dashboard-metrics-region>
    <div class="list-card dashboard-filter-card" data-dashboard-metrics-card>
        <div class="dashboard-filter-head">
            <div>
                <strong>Periodo de estadísticas</strong>
                <span>Aplica a ventas, visitas y estadísticas de planes.</span>
            </div>
        </div>

        <div class="dashboard-filter-group">
            <span class="dashboard-filter-label">Métricas</span>
            <div class="dashboard-segmented">
                @foreach (($metricsPeriodOptions ?? []) as $periodKey => $periodLabel)
                    <a
                        href="{{ route('dashboard', array_merge(request()->except('metrics_month'), ['metrics_period' => $periodKey])) }}"
                        class="dashboard-segment {{ ($metricsPeriod ?? 'month') === $periodKey ? 'is-active' : '' }}"
                        data-dashboard-metrics-link
                    >
                        {{ $periodLabel }}
                    </a>
                @endforeach
            </div>
        </div>

        <form
            class="dashboard-month-form"
            action="{{ route('dashboard') }}"
            method="GET"
            data-dashboard-metrics-form
        >
            @foreach (request()->except(['metrics_period', 'metrics_month']) as $queryKey => $queryValue)
                @if (is_scalar($queryValue))
                    <input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">
                @endif
            @endforeach
            <input type="hidden" name="metrics_period" value="custom_month">
            <label class="dashboard-filter-label" for="metrics_month">Mes específico</label>
            <div class="dashboard-month-control">
                <input
                    id="metrics_month"
                    type="month"
                    name="metrics_month"
                    value="{{ $metricsMonthValue ?? now()->format('Y-m') }}"
                    max="{{ now()->format('Y-m') }}"
                    data-dashboard-metrics-month
                >
                <button type="submit" class="btn btn-secondary">Ver mes</button>
            </div>
        </form>
    </div>

    <div class="dashboard-admin-stats">
        <div class="card dashboard-stat-card dashboard-stat-card--sales">
            <span class="dashboard-stat-label">Total de ventas</span>
            <strong class="dashboard-stat-value">$ {{ number_format($totalSales ?? 0, 0, ',', '.') }}</strong>
            <small class="dashboard-stat-note">{{ $metricsPeriodLabel ?? 'Mensual' }}</small>
        </div>

        <div class="card dashboard-stat-card">
            <span class="dashboard-stat-label">Usuarios de tienda</span>
            <strong class="dashboard-stat-value">{{ $storeUsersCount ?? 0 }}</strong>
            <small class="dashboard-stat-note">{{ $metricsPeriodLabel ?? 'Mensual' }}</small>
        </div>

        <div class="card dashboard-stat-card">
            <span class="dashboard-stat-label">Tiendas creadas</span>
            <strong class="dashboard-stat-value">{{ $storesCount ?? 0 }}</strong>
            <small class="dashboard-stat-note">{{ $metricsPeriodLabel ?? 'Mensual' }}</small>
        </div>

        <div class="card dashboard-stat-card">
            <span class="dashboard-stat-label">Total de visitas</span>
            <strong class="dashboard-stat-value">{{ number_format($totalVisits ?? 0, 0, ',', '.') }}</strong>
            <small class="dashboard-stat-note">{{ $metricsPeriodLabel ?? 'Mensual' }}</small>
        </div>
    </div>

    @if (!empty($subscriptionStats))
        <div class="list-card dashboard-subscription-card">
            <div class="dashboard-users-head">
                <div>
                    <strong>Estadísticas de planes</strong>
                    <span>{{ $metricsPeriodLabel ?? 'Mensual' }} · Pruebas, pagos activos y vencimientos</span>
                </div>
                <a href="{{ url('/admin/stores') }}" class="btn btn-secondary">Gestionar tiendas</a>
            </div>

            <div class="dashboard-subscription-grid">
                <div class="dashboard-subscription-metric">
                    <span>Pruebas activas</span>
                    <strong>{{ $subscriptionStats['trial_active'] ?? 0 }}</strong>
                    <small>{{ $subscriptionStats['trial_ending_soon'] ?? 0 }} por vencer</small>
                </div>
                <div class="dashboard-subscription-metric">
                    <span>Pagos activos</span>
                    <strong>{{ $subscriptionStats['paid_active'] ?? 0 }}</strong>
                    <small>{{ $subscriptionStats['paid_ending_soon'] ?? 0 }} por vencer</small>
                </div>
                <div class="dashboard-subscription-metric dashboard-subscription-metric--warning">
                    <span>Vencidas</span>
                    <strong>{{ ($subscriptionStats['trial_expired'] ?? 0) + ($subscriptionStats['paid_expired'] ?? 0) }}</strong>
                    <small>{{ $subscriptionStats['trial_expired'] ?? 0 }} pruebas / {{ $subscriptionStats['paid_expired'] ?? 0 }} pagos</small>
                </div>
                <div class="dashboard-subscription-metric dashboard-subscription-metric--mrr">
                    <span>MRR estimado</span>
                    <strong>$ {{ number_format($subscriptionStats['mrr'] ?? 0, 0, ',', '.') }}</strong>
                    <small>{{ number_format($subscriptionStats['conversion_rate'] ?? 0, 1, ',', '.') }}% conversión</small>
                </div>
            </div>

            @if (!empty($subscriptionStats['attention_stores']) && $subscriptionStats['attention_stores']->isNotEmpty())
                <div class="dashboard-subscription-table-wrap">
                    <table class="dashboard-subscription-table">
                        <thead>
                            <tr>
                                <th>Tienda</th>
                                <th>Cliente</th>
                                <th>Plan</th>
                                <th>Estado</th>
                                <th>Vence</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($subscriptionStats['attention_stores'] as $attentionStore)
                                @php
                                    $endsAt = $attentionStore->subscriptionStatus() === \App\Models\Store::SUBSCRIPTION_TRIALING
                                        ? $attentionStore->trial_ends_at
                                        : $attentionStore->subscription_ends_at;
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $attentionStore->name }}</strong>
                                        <span>{{ $attentionStore->whatsapp ?: 'Sin WhatsApp' }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $attentionStore->user->name ?? 'Sin usuario' }}</strong>
                                        <span>{{ $attentionStore->user->email ?? 'Sin correo' }}</span>
                                    </td>
                                    <td>{{ $attentionStore->planLabel() }}</td>
                                    <td>
                                        <span class="dashboard-subscription-status {{ $attentionStore->subscriptionExpired() ? 'is-expired' : 'is-ending' }}">
                                            {{ $attentionStore->subscriptionRemainingLabel() }}
                                        </span>
                                    </td>
                                    <td>{{ $endsAt ? $endsAt->format('d/m/Y') : 'Sin fecha' }}</td>
                                    <td>
                                        @if ($attentionStore->getKey())
                                            <a href="{{ route('admin.stores.edit', ['store' => $attentionStore->getKey()]) }}" class="btn btn-secondary">Ver</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="dashboard-subscription-empty">No hay tiendas por vencer o vencidas en este momento.</p>
            @endif
        </div>
    @endif
    </div>

    <div class="list-card">
        <p>Desde aquí puedes crear usuarios de tienda, asignar tiendas y publicar banners/noticias.</p>
    </div>

    <div class="list-card dashboard-updates-card">
        <div class="dashboard-users-head">
            <strong>Nuevas actualizaciones</strong>
            <span>Últimas 10</span>
        </div>

        @if (!empty($adminUpdates) && $adminUpdates->isNotEmpty())
            <div class="dashboard-updates-list">
                @foreach ($adminUpdates as $adminUpdate)
                    <a
                        href="{{ $adminUpdate->url ?: '#' }}"
                        class="dashboard-update-item {{ $adminUpdate->url ? '' : 'is-static' }}"
                    >
                        <span class="dashboard-update-type">{{ ucfirst($adminUpdate->type) }}</span>
                        <strong>{{ $adminUpdate->title }}</strong>
                        @if ($adminUpdate->body)
                            <p>{{ $adminUpdate->body }}</p>
                        @endif
                        <small>{{ $adminUpdate->created_at?->diffForHumans() }}</small>
                    </a>
                @endforeach
            </div>
        @else
            <p>No hay actualizaciones recientes.</p>
        @endif
    </div>

    <div class="list-card dashboard-users-card">
        <div class="dashboard-users-head">
            <strong>Tiempo activo de usuarios</strong>
            <a href="/admin/users" class="btn btn-secondary">Gestionar usuarios</a>
        </div>

        @if (!empty($storeUsers) && $storeUsers->isNotEmpty())
            <div class="panel-list">
                @foreach ($storeUsers as $storeUser)
                    @php
                        $remainingLabel = $storeUser->active_remaining_label;
                        $remainingClass = $remainingLabel === 'Vencida' ? 'resource-metric__value--danger' : ($remainingLabel === 'Vence hoy' ? 'resource-metric__value--warning' : '');
                    @endphp
                    <article class="resource-card">
                        <div class="resource-card__main">
                            <div class="resource-card__header">
                                <div>
                                    <h3 class="resource-card__title">{{ $storeUser->name }}</h3>
                                    <p class="resource-card__subtitle">{{ $storeUser->email }}</p>
                                </div>
                                <div class="resource-badges">
                                    <span class="resource-badge {{ $storeUser->isActive() ? 'resource-badge--active' : 'resource-badge--inactive' }}">
                                        {{ $storeUser->isActive() ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </div>
                            </div>

                            <div class="resource-metrics">
                                <div class="resource-metric">
                                    <span class="resource-metric__label">Duración</span>
                                    <span class="resource-metric__value">{{ $storeUser->active_duration_days ? $storeUser->active_duration_days . ' dia(s)' : 'Sin límite' }}</span>
                                </div>
                                <div class="resource-metric">
                                    <span class="resource-metric__label">Inicio</span>
                                    <span class="resource-metric__value">{{ $storeUser->active_starts_at ? $storeUser->active_starts_at->format('d/m/Y') : 'Sin fecha' }}</span>
                                </div>
                                <div class="resource-metric">
                                    <span class="resource-metric__label">Final</span>
                                    <span class="resource-metric__value">{{ $storeUser->active_ends_at ? $storeUser->active_ends_at->format('d/m/Y') : 'Sin fecha final' }}</span>
                                </div>
                                <div class="resource-metric">
                                    <span class="resource-metric__label">Restante</span>
                                    <span class="resource-metric__value {{ $remainingClass }}">{{ $remainingLabel }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="resource-actions">
                            <a href="{{ route('admin.users.edit', $storeUser) }}" class="btn">Editar</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <p>No hay usuarios de tienda registrados.</p>
        @endif
    </div>
@else
    @php
        $supportWhatsapp = preg_replace('/\D+/', '', (string) config('services.support.whatsapp'));
        $activationWhatsappUrl = $supportWhatsapp !== ''
            ? 'https://wa.me/' . $supportWhatsapp . '?text=' . rawurlencode('Hola, quiero activar mi tienda ' . ($store?->name ?? ''))
            : '#';
    @endphp

    @if (!empty($subscriptionExpired) && !empty($store))
        <div class="dashboard-notification dashboard-notification--warning">
            <div>
                <strong>Tu prueba o suscripcion venció</strong>
                <p>
                    La tienda no estara visible para clientes ni podrá recibir pedidos hasta que se active nuevamente.
                    Contacta al administrador para validar el pago.
                </p>
            </div>
            <a href="{{ $activationWhatsappUrl }}" class="btn" target="_blank" rel="noopener noreferrer">Solicitar activación</a>
        </div>
    @elseif (!empty($subscriptionEndsSoon) && !empty($store))
        <div class="dashboard-notification dashboard-notification--warning">
            <div>
                <strong>Tu prueba esta por finalizar</strong>
                <p>
                    {{ $subscriptionRemainingLabel }}. Cuando venza, la tienda dejara de estar visible para clientes hasta activar la suscripcion.
                </p>
            </div>
        </div>
    @endif

    @if (!empty($accountExpiresSoon) && auth()->user()->active_ends_at)
        <div class="dashboard-notification dashboard-notification--warning">
            <div>
                <strong>Notificacion</strong>
                <p>
                    Tu cuenta esta por finalizar:
                    <b>{{ auth()->user()->active_remaining_label }}</b>.
                    Fecha final: {{ auth()->user()->active_ends_at->format('d/m/Y') }}.
                </p>
            </div>
        </div>
    @endif

    @if (!empty($needsOnboarding) && !empty($store))
        <div class="dashboard-notification dashboard-notification--warning">
            <div>
                <strong>Completa tu tienda</strong>
                <p>
                    Tu configuración inicial va en <b>{{ $onboardingProgress ?? 0 }}%</b>.
                    Termina los primeros pasos para que tu tienda se vea lista para vender.
                </p>
            </div>
            <a href="{{ route('admin.store.onboarding') }}" class="btn">Continuar</a>
        </div>
    @endif

    @php
        $dashboardStore = $store ?? null;
        $dashboardStoreUrl = !empty($dashboardStore) && $dashboardStore->slug
            ? app(\App\Services\StorefrontUrlService::class)->publicHome($dashboardStore)
            : null;
        $dashboardChecklist = collect($onboardingChecklist ?? []);
        $dashboardCompletedSteps = $dashboardChecklist->filter(fn ($item) => (bool) ($item['complete'] ?? false))->count();
        $dashboardTotalSteps = max(1, $dashboardChecklist->count());
        $dashboardOwnerName = $dashboardStore?->name ?: auth()->user()->name;
        $dashboardMetrics = [
            ['label' => 'Ventas', 'value' => '$ ' . number_format($totalSales ?? 0, 0, ',', '.'), 'icon' => 'receipt'],
            ['label' => 'Pedidos', 'value' => number_format($ordersCount ?? 0, 0, ',', '.'), 'icon' => 'orders'],
            ['label' => 'Productos', 'value' => number_format($productsCount ?? 0, 0, ',', '.'), 'icon' => 'box'],
            ['label' => 'Pagados', 'value' => number_format($paidOrdersCount ?? 0, 0, ',', '.'), 'icon' => 'paid'],
            ['label' => 'Enviados', 'value' => number_format($shippedOrdersCount ?? 0, 0, ',', '.'), 'icon' => 'ship'],
            ['label' => 'Visitas', 'value' => number_format($totalVisits ?? 0, 0, ',', '.'), 'icon' => 'visits'],
        ];
        $dashboardQuickActions = collect([
            ['label' => 'Ver pedidos', 'href' => url('/admin/orders'), 'icon' => 'orders'],
            ['label' => 'Personalizar tienda', 'href' => url('/admin/store-settings'), 'icon' => 'edit'],
            $dashboardStoreUrl ? ['label' => 'Compartir mi tienda', 'href' => $dashboardStoreUrl, 'icon' => 'share', 'external' => true] : null,
        ])->filter();
        $dashboardIcon = function (string $icon): string {
            $icons = [
                'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6"/><path d="M9 12h6"/>',
                'orders' => '<path d="M7 7h10"/><path d="M7 12h10"/><path d="M7 17h6"/><rect x="4" y="3" width="16" height="18" rx="2"/>',
                'box' => '<path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
                'paid' => '<circle cx="12" cy="12" r="9"/><path d="M15 9.5a3 3 0 0 0-3-1.5c-1.7 0-3 .8-3 2s1.3 2 3 2 3 .8 3 2-1.3 2-3 2a3 3 0 0 1-3-1.5"/><path d="M12 6v12"/>',
                'ship' => '<path d="M3 7h11v9H3z"/><path d="M14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
                'visits' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.8"/><path d="M16 3.2a4 4 0 0 1 0 7.6"/>',
                'edit' => '<path d="M12 20h9"/><path d="m16.5 3.5 4 4L8 20H4v-4L16.5 3.5Z"/>',
                'share' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.5 6.8-4"/><path d="m8.6 13.5 6.8 4"/>',
            ];

            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($icons[$icon] ?? $icons['box']) . '</svg>';
        };
    @endphp

    <section class="vendly-home-panel">
        <div class="vendly-home-head">
            <div>
                <h2>Hola, ¡bienvenido!</h2>
                <p>Administra tu tienda desde aquí.</p>
            </div>
            <div class="vendly-home-actions">
                <a href="{{ url('/admin/products/create') }}" class="btn vendly-home-primary">
                    <span aria-hidden="true">+</span>
                    Agregar producto
                </a>
                @if($dashboardStoreUrl)
                    <a href="{{ $dashboardStoreUrl }}" class="vendly-home-store-link" target="_blank" rel="noopener noreferrer">Ver mi tienda ↗</a>
                @endif
            </div>
        </div>

        <div class="vendly-home-metrics" aria-label="Indicadores de la tienda">
            @foreach($dashboardMetrics as $metric)
                <div class="vendly-home-metric">
                    <span class="vendly-home-metric-icon">{!! $dashboardIcon($metric['icon']) !!}</span>
                    <span>{{ $metric['label'] }}</span>
                    <strong>{{ $metric['value'] }}</strong>
                </div>
            @endforeach
        </div>

        <div class="vendly-home-section">
            <h3>Accesos rápidos</h3>
            <div class="vendly-home-quick">
                @foreach($dashboardQuickActions as $action)
                    <a href="{{ $action['href'] }}" @if(!empty($action['external'])) target="_blank" rel="noopener noreferrer" @endif>
                        <span>{!! $dashboardIcon($action['icon']) !!}</span>
                        <strong>{{ $action['label'] }}</strong>
                    </a>
                @endforeach
            </div>
        </div>

        @if($dashboardChecklist->isNotEmpty())
            <div class="vendly-home-section vendly-home-progress">
                <div class="vendly-home-progress-head">
                    <h3>Completa tu tienda</h3>
                    <span>{{ $dashboardCompletedSteps }} de {{ $dashboardTotalSteps }} pasos</span>
                </div>
                <div class="vendly-home-progress-track" aria-hidden="true">
                    <span style="width: {{ min(100, max(0, $onboardingProgress ?? 0)) }}%"></span>
                </div>
                <div class="vendly-home-checklist">
                    @foreach($dashboardChecklist as $step)
                        <div @class(['is-complete' => (bool) ($step['complete'] ?? false)])>
                            <span aria-hidden="true">{{ (bool) ($step['complete'] ?? false) ? '✓' : '' }}</span>
                            <strong>{{ $step['label'] ?? 'Paso pendiente' }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    @if (!empty($products) && $products->isNotEmpty())
        <section class="list-card dashboard-products-panel">
            <div class="dashboard-users-head">
                <div>
                    <strong>Productos recientes</strong>
                    <span>Vista rápida de lo que ya está publicado.</span>
                </div>
                <a href="{{ url('/admin/products') }}" class="btn btn-secondary">Ver todos</a>
            </div>

            <div class="grid dashboard-product-grid">
                @foreach ($products as $product)
                    <article class="card dashboard-product-card">
                        @if ($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}">
                        @else
                            <div class="dashboard-product-placeholder" aria-hidden="true">{{ strtoupper(mb_substr($product->name, 0, 1)) }}</div>
                        @endif
                        <div>
                            <strong>{{ $product->name }}</strong>
                            <span>${{ number_format((float) $product->price, 0, ',', '.') }}</span>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if (!empty($banners) && $banners->count() > 1)
        <script src="{{ asset('js/dashboard.js') }}?v={{ filemtime(public_path('js/dashboard.js')) }}" defer></script>
    @endif
@endif

@push('scripts')
    <script>
        (() => {
            const loadDashboardMetrics = async (url) => {
                const region = document.querySelector('[data-dashboard-metrics-region]');

                if (!region || !window.DOMParser || !window.fetch) {
                    window.location.href = url;
                    return;
                }

                const card = region.querySelector('[data-dashboard-metrics-card]');
                card?.classList.add('is-loading');

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                    });

                    if (!response.ok) {
                        window.location.href = url;
                        return;
                    }

                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const nextRegion = doc.querySelector('[data-dashboard-metrics-region]');

                    if (!nextRegion) {
                        window.location.href = url;
                        return;
                    }

                    region.replaceWith(nextRegion);
                    window.history.pushState({}, '', url);
                    bindDashboardMetrics();
                } catch (error) {
                    window.location.href = url;
                }
            };

            const bindDashboardMetrics = () => {
                document.querySelectorAll('[data-dashboard-metrics-link]:not([data-bound])').forEach((link) => {
                    link.dataset.bound = 'true';
                    link.addEventListener('click', (event) => {
                        event.preventDefault();
                        loadDashboardMetrics(link.href);
                    });
                });

                document.querySelectorAll('[data-dashboard-metrics-form]:not([data-bound])').forEach((form) => {
                    form.dataset.bound = 'true';
                    const monthInput = form.querySelector('[data-dashboard-metrics-month]');

                    const submitForm = () => {
                        const formData = new FormData(form);
                        const searchParams = new URLSearchParams(formData);
                        loadDashboardMetrics(`${form.action}?${searchParams.toString()}`);
                    };

                    form.addEventListener('submit', (event) => {
                        event.preventDefault();
                        submitForm();
                    });

                    monthInput?.addEventListener('change', submitForm);
                });
            };

            window.addEventListener('popstate', () => window.location.reload());
            bindDashboardMetrics();
        })();
    </script>
@endpush
@endsection
