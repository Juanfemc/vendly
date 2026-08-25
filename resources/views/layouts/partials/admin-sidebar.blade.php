@php
    $sidebarUser = auth()->user();
    $sidebarStores = $sidebarUser?->stores()->get() ?? collect();
    $sidebarStore = $sidebarUser?->store ?? $sidebarStores->first();
    $sidebarAllowsTemplates = $sidebarStores->contains(fn ($store) => $store->allowsTemplates());
    $sidebarStoreUrl = $sidebarStore?->slug ? app(\App\Services\StorefrontUrlService::class)->publicHome($sidebarStore) : url('/');
    $sidebarStoreHost = parse_url($sidebarStoreUrl, PHP_URL_HOST) ?: config('app.name', 'Vendly');
    $sidebarPlanLabel = $sidebarStore?->planLabel() ?? 'Admin';
    $sidebarUnreadNotifications = 0;

    if (\App\Models\StoreNotification::supportsTable()) {
        $sidebarNotificationsQuery = \App\Models\StoreNotification::query();

        if (! $sidebarUser?->isAdmin()) {
            $sidebarStoreIds = $sidebarStores->pluck('id');

            if ($sidebarUser?->store_id) {
                $sidebarStoreIds->push($sidebarUser->store_id);
            }

            $sidebarStoreIds = $sidebarStoreIds->filter()->unique()->values();
            $sidebarNotificationsQuery = $sidebarStoreIds->isEmpty()
                ? $sidebarNotificationsQuery->whereRaw('1 = 0')
                : $sidebarNotificationsQuery->whereIn('store_id', $sidebarStoreIds);
        }

        $sidebarUnreadNotifications = (clone $sidebarNotificationsQuery)->whereNull('read_at')->count();
    }

    $sidebarIsActive = fn (...$patterns) => collect($patterns)->contains(fn ($pattern) => request()->is($pattern));
    $sidebarLinkClass = fn (...$patterns) => 'sidebar-nav-link' . ($sidebarIsActive(...$patterns) ? ' is-active' : '');
    $sidebarSubLinkClass = fn (...$patterns) => $sidebarIsActive(...$patterns) ? 'is-active' : '';
    $sidebarIcon = function (string $name): string {
        $icons = [
            'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/>',
            'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
            'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            'image' => '<rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>',
            'star' => '<path d="m12 3 2.7 5.47 6.03.88-4.36 4.25 1.03 6-5.4-2.84-5.4 2.84 1.03-6-4.36-4.25 6.03-.88L12 3z"/>',
            'store' => '<path d="M4 10h16"/><path d="M5 10l1-5h12l1 5"/><path d="M6 10v10h12V10"/><path d="M9 20v-6h6v6"/>',
            'box' => '<path d="m21 8-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
            'tag' => '<path d="M20.59 13.41 12 22l-9-9V4h9l8.59 8.59a2 2 0 0 1 0 2.82z"/><circle cx="7.5" cy="8.5" r="1.5"/>',
            'chat' => '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>',
            'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19 13.5a7.8 7.8 0 0 0 0-3l2-1.5-2-3.5-2.4 1a8.2 8.2 0 0 0-2.6-1.5L13.7 2h-4l-.3 3A8.2 8.2 0 0 0 6.8 6.5l-2.4-1-2 3.5 2 1.5a7.8 7.8 0 0 0 0 3l-2 1.5 2 3.5 2.4-1a8.2 8.2 0 0 0 2.6 1.5l.3 3h4l.3-3a8.2 8.2 0 0 0 2.6-1.5l2.4 1 2-3.5-2-1.5z"/>',
            'cart' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h8.72a2 2 0 0 0 2-1.61L23 6H6"/>',
            'profile' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
            'gift' => '<rect x="3" y="8" width="18" height="13" rx="2"/><path d="M12 8v13"/><path d="M3 12h18"/><path d="M7.5 8a2.5 2.5 0 1 1 4.5-1.5V8"/><path d="M16.5 8A2.5 2.5 0 1 0 12 6.5V8"/>',
            'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
            'external' => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/>',
            'globe' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 0 20"/><path d="M12 2a15.3 15.3 0 0 0 0 20"/>',
            'ticket' => '<path d="M2 9a3 3 0 0 0 0 6v3a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-3a3 3 0 0 0 0-6V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v3Z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/>',
            'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15Z"/>',
        ];

        return '<svg class="sidebar-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($icons[$name] ?? $icons['home']) . '</svg>';
    };
@endphp

<aside class="sidebar" id="adminSidebar">
    <div class="sidebar-top">
        <div class="sidebar-brand">
            <img src="{{ asset('images/vendly-logo.svg') }}" alt="Vendly">
            <div class="sidebar-brand-text">
                <p class="sidebar-brand-title">Vendly</p>
                <p class="sidebar-brand-subtitle">{{ $sidebarUser->isAdmin() ? 'Panel admin' : 'Mi tienda' }}</p>
            </div>
        </div>

        @if(\App\Models\StoreNotification::supportsTable())
            <a href="{{ route('admin.notifications.index') }}" class="sidebar-notification-link" aria-label="Notificaciones">
                {!! $sidebarIcon('bell') !!}
                @if($sidebarUnreadNotifications > 0)
                    <span class="sidebar-notification-dot"></span>
                @endif
            </a>
        @endif
    </div>

    <nav class="sidebar-nav" aria-label="Menu del panel">
        <div class="sidebar-section">
            <a href="/dashboard" class="{{ $sidebarLinkClass('dashboard') }}">
                {!! $sidebarIcon('home') !!}
                <span>Inicio</span>
            </a>

        </div>

        @if ($sidebarUser->isAdmin())
            <div class="sidebar-section">
                <p class="sidebar-section-label">Administracion</p>

                <details class="sidebar-menu-group" {{ request()->is('admin/users*') ? 'open' : '' }}>
                    <summary><span>{!! $sidebarIcon('users') !!}Usuarios</span></summary>
                    <div class="sidebar-submenu">
                        <a href="/admin/users" class="{{ $sidebarSubLinkClass('admin/users') }}">Ver usuarios</a>
                        <a href="/admin/users/create" class="{{ $sidebarSubLinkClass('admin/users/create') }}">Crear usuario</a>
                    </div>
                </details>

                <details class="sidebar-menu-group" {{ request()->is('admin/stores*') ? 'open' : '' }}>
                    <summary><span>{!! $sidebarIcon('store') !!}Tiendas</span></summary>
                    <div class="sidebar-submenu">
                        <a href="/admin/stores" class="{{ $sidebarSubLinkClass('admin/stores') }}">Ver tiendas</a>
                        <a href="{{ route('admin.stores.visits') }}" class="{{ $sidebarSubLinkClass('admin/stores/visits') }}">Visitas</a>
                        <a href="{{ route('admin.stores.create-with-user') }}" class="{{ $sidebarSubLinkClass('admin/stores/create-with-user') }}">Crear cliente + tienda</a>
                        <a href="/admin/stores/create" class="{{ $sidebarSubLinkClass('admin/stores/create') }}">Crear tienda</a>
                    </div>
                </details>

                <details class="sidebar-menu-group" {{ request()->is('admin/products*') ? 'open' : '' }}>
                    <summary><span>{!! $sidebarIcon('box') !!}Productos</span></summary>
                    <div class="sidebar-submenu">
                        <a href="/admin/products" class="{{ $sidebarSubLinkClass('admin/products') }}">Ver productos</a>
                        <a href="/admin/products/create" class="{{ $sidebarSubLinkClass('admin/products/create') }}">Crear producto</a>
                        <a href="{{ route('admin.products.import') }}" class="{{ $sidebarSubLinkClass('admin/products/import') }}">Importar productos</a>
                    </div>
                </details>

                <details class="sidebar-menu-group" {{ request()->is('admin/categories*') || request()->is('admin/stores*/categories') || request()->is('admin/coupons*') || request()->is('admin/stores*/coupons') || request()->is('admin/price-lists*') || request()->is('admin/stores*/price-lists') ? 'open' : '' }}>
                    <summary><span>{!! $sidebarIcon('tag') !!}Catálogo</span></summary>
                    <div class="sidebar-submenu">
                        <a href="/admin/categories" class="{{ $sidebarSubLinkClass('admin/categories*') }}">Categorías</a>
                        <a href="{{ route('admin.coupons.index') }}" class="{{ $sidebarSubLinkClass('admin/coupons*') }}">Cupones</a>
                        <a href="{{ route('admin.price-lists.index') }}" class="{{ $sidebarSubLinkClass('admin/price-lists*') }}">Listas de precios</a>
                    </div>
                </details>
            </div>

            <div class="sidebar-section">
                <p class="sidebar-section-label">Contenido</p>

                <details class="sidebar-menu-group" {{ request()->is('admin/banners*') ? 'open' : '' }}>
                    <summary><span>{!! $sidebarIcon('image') !!}Banners</span></summary>
                    <div class="sidebar-submenu">
                        <a href="/admin/banners" class="{{ $sidebarSubLinkClass('admin/banners') }}">Ver banners</a>
                        <a href="/admin/banners/create" class="{{ $sidebarSubLinkClass('admin/banners/create') }}">Crear banner</a>
                    </div>
                </details>

                <details class="sidebar-menu-group" {{ request()->is('admin/testimonials*') ? 'open' : '' }}>
                    <summary><span>{!! $sidebarIcon('star') !!}Testimonios</span></summary>
                    <div class="sidebar-submenu">
                        <a href="{{ route('admin.testimonials.index') }}" class="{{ $sidebarSubLinkClass('admin/testimonials') }}">Ver testimonios</a>
                        <a href="{{ route('admin.testimonials.create') }}" class="{{ $sidebarSubLinkClass('admin/testimonials/create') }}">Crear testimonio</a>
                    </div>
                </details>

                <a href="{{ route('admin.whatsapp.index') }}" class="{{ $sidebarLinkClass('admin/whatsapp*') }}">
                    {!! $sidebarIcon('chat') !!}
                    <span>WhatsApp</span>
                </a>
            </div>
        @else
            <div class="sidebar-section">
                <p class="sidebar-section-label">Mi negocio</p>

                <a href="/admin/orders" class="{{ $sidebarLinkClass('admin/orders*') }}">
                    {!! $sidebarIcon('cart') !!}
                    <span>Mis pedidos</span>
                </a>

                <details class="sidebar-menu-group" {{ request()->is('admin/products*') ? 'open' : '' }}>
                    <summary><span>{!! $sidebarIcon('box') !!}Mis productos</span></summary>
                    <div class="sidebar-submenu">
                        <a href="/admin/products" class="{{ $sidebarSubLinkClass('admin/products') }}">Ver productos</a>
                        <a href="/admin/products/create" class="{{ $sidebarSubLinkClass('admin/products/create') }}">Crear producto</a>
                        <a href="{{ route('admin.products.import') }}" class="{{ $sidebarSubLinkClass('admin/products/import') }}">Importar productos</a>
                    </div>
                </details>

                @if(($sidebarStore?->allowsVisitStats() ?? false))
                    <a href="{{ route('admin.store.visits') }}" class="{{ $sidebarLinkClass('admin/store-visits*') }}">
                        {!! $sidebarIcon('store') !!}
                        <span>Metricas de mi tienda</span>
                    </a>
                @endif
            </div>

            <div class="sidebar-section">
                <p class="sidebar-section-label">Configuración</p>

                <details class="sidebar-menu-group" {{ request()->is('admin/onboarding') || request()->is('admin/store-settings') || request()->is('admin/templates*') || request()->is('admin/payments*') || request()->is('admin/categories*') || request()->is('admin/coupons*') || request()->is('admin/price-lists*') ? 'open' : '' }}>
                    <summary><span>{!! $sidebarIcon('settings') !!}Configurar catálogo</span></summary>
                    <div class="sidebar-submenu">
                        <a href="{{ route('admin.store.onboarding') }}" class="{{ $sidebarSubLinkClass('admin/onboarding') }}">Primeros pasos</a>
                        <a href="/admin/store-settings" class="{{ $sidebarSubLinkClass('admin/store-settings') }}">Apariencia e identidad</a>
                        @if($sidebarAllowsTemplates)
                            <a href="{{ route('admin.templates.index') }}" class="{{ $sidebarSubLinkClass('admin/templates*') }}">Plantillas</a>
                        @endif
                        @if(($sidebarStore?->allowsOnlinePayments() ?? false))
                            <a href="{{ route('admin.payments.index') }}" class="{{ $sidebarSubLinkClass('admin/payments*') }}">Métodos de pago</a>
                        @endif
                        @if(($sidebarStore?->allowsCategories() ?? true))
                            <a href="/admin/categories" class="{{ $sidebarSubLinkClass('admin/categories*') }}">Categorías</a>
                        @endif
                        <a href="{{ route('admin.coupons.index') }}" class="{{ $sidebarSubLinkClass('admin/coupons*') }}">Gestionar cupones <span class="sidebar-plan-mini">Premium</span></a>
                        @if(($sidebarStore?->allowsPriceLists() ?? false))
                            <a href="{{ route('admin.price-lists.index') }}" class="{{ $sidebarSubLinkClass('admin/price-lists*') }}">Listas de precios</a>
                        @endif
                    </div>
                </details>
            </div>
        @endif

        <div class="sidebar-section">
            <a href="/profile" class="{{ $sidebarLinkClass('profile') }}">
                {!! $sidebarIcon('profile') !!}
                <span>Perfil</span>
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        @if($sidebarStore)
            <a href="{{ $sidebarStoreUrl }}" class="sidebar-store-card" target="_blank" rel="noopener">
                <span class="sidebar-store-text">
                    <strong>{{ $sidebarStore->name }}</strong>
                    <span>{{ $sidebarStoreHost }}</span>
                </span>
                {!! $sidebarIcon('external') !!}
            </a>

            <a href="/admin/store-settings" class="sidebar-plan-card">
                <span class="sidebar-plan-icon">{!! $sidebarIcon('gift') !!}</span>
                <span class="sidebar-plan-text">
                    <strong>Plan {{ $sidebarPlanLabel }}</strong>
                    <span>Gestiona tu tienda</span>
                </span>
            </a>
        @endif

        <form method="POST" action="{{ route('logout') }}" class="sidebar-logout-form">
            @csrf
            <button type="submit" class="sidebar-logout-button">
                {!! $sidebarIcon('logout') !!}
                <span>Cerrar sesión</span>
            </button>
        </form>
    </div>
</aside>
