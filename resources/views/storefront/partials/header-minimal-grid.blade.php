@php
    $announcementMessages = \App\Models\Store::supportsCommercialNoticeColumns()
        ? $store->announcementMessages()
        : [];
    $enableAnnouncementRotation = (bool) ($enableAnnouncementRotation ?? true);
    $showAnnouncementBar = (bool) ($showAnnouncementBar ?? true);
    $showCartControl = (bool) ($showCartControl ?? true);
    $useCenteredCheckoutHeader = (bool) ($useCenteredCheckoutHeader ?? false);
    $visibleAnnouncementMessages = $enableAnnouncementRotation
        ? $announcementMessages
        : array_slice($announcementMessages, 0, 1);
    $hasOfferProducts = $store->hasOfferProducts();
    $mobileMenuId = 'minimalShopMenuToggle';
    $cartDrawerId = 'minimalShopCartToggle';
    $searchToggleId = 'minimalShopSearchToggle';
    $mobileCategories = ($activeCategories ?? collect())
        ->filter(fn ($category) => ! $category->parent_id)
        ->values();
    $visibleMobileCategories = $mobileCategories->take(8);
    $hasMoreMobileCategories = $mobileCategories->count() > $visibleMobileCategories->count();
    $minimalNavHasSubcategories = $store->allowsSubcategories()
        && $mobileCategories->contains(fn ($category) => ($category->activeChildren ?? collect())->isNotEmpty());
    $mobileBadgeFilters = ($customBadgeFilters ?? collect())->values();
    $selectedMobileBadge = trim((string) ($selectedHomeBadge ?? request('etiqueta', '')));
    $icons = \App\Support\MinimalShopIcons::class;
    $minimalHomeUrl = $storefrontUrls->home($store);
    $minimalCategoryUrl = fn (array $query = []) => $minimalHomeUrl . ($query ? '?' . http_build_query($query) : '') . '#catalogo';
    $techPanelCtaText = trim((string) ($heroOverlayButtonText ?? ''));
    $techPanelCtaText = $techPanelCtaText !== '' ? $techPanelCtaText : 'Comprar ahora';
    $techPanelCtaUrl = trim((string) ($heroOverlayButtonUrl ?? ''));
    $techPanelCtaUrl = $techPanelCtaUrl !== '' ? $techPanelCtaUrl : $storefrontUrls->products($store);
    $drawerCart = app(\App\Services\CartService::class)->cartForStore($store);
    $drawerSubtotal = collect($drawerCart)->sum(fn ($item) => (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 1));
    $drawerShipping = 0;
    $drawerTotal = $drawerSubtotal + $drawerShipping;
    $mobileProductCount = $productsTotal
        ?? (isset($products) && method_exists($products, 'total')
            ? $products->total()
            : (isset($allProducts) ? $allProducts->count() : $store->products()->count()));
    $techHeaderProductPool = collect();

    foreach (['filterProducts', 'allProducts', 'products', 'relatedProducts'] as $techHeaderProductSource) {
        if (isset(${$techHeaderProductSource})) {
            $techHeaderProductValue = ${$techHeaderProductSource};
            $techHeaderProductItems = method_exists($techHeaderProductValue, 'getCollection')
                ? $techHeaderProductValue->getCollection()
                : collect($techHeaderProductValue);

            $techHeaderProductPool = $techHeaderProductPool->merge($techHeaderProductItems);
        }
    }

    $techHeaderProductPool = $techHeaderProductPool
        ->filter(fn ($item) => $item instanceof \App\Models\Product)
        ->unique('id')
        ->values();

    if ($techHeaderProductPool->count() < min(max((int) $mobileProductCount, 1), 48)) {
        $techHeaderProductPool = $store->products()
            ->latest()
            ->take(80)
            ->get();
    }

    $techMenuFeaturedProduct = $techHeaderProductPool
        ->unique('id')
        ->first(fn ($item) => trim((string) $item->image) !== '')
        ?? $techHeaderProductPool->first(fn ($item) => $item instanceof \App\Models\Product);
    $techMenuFeaturedCategory = $mobileCategories->first();
    $techMenuFeaturedUrl = $techMenuFeaturedProduct
        ? $storefrontUrls->product($store, $techMenuFeaturedProduct)
        : ($techMenuFeaturedCategory ? $storefrontUrls->category($store, $techMenuFeaturedCategory) : $storefrontUrls->products($store));
    $techMenuFeaturedTitle = $techMenuFeaturedProduct?->name
        ?? ($techMenuFeaturedCategory?->name ?? 'Tecnología');
    $techMenuFeaturedSubtitle = $techMenuFeaturedProduct
        ? 'Producto destacado'
        : (trim((string) ($store->shop_copy ?? '')) ?: 'Explora el catálogo de ' . $store->name);
    $mobileMenuSections = [];

    if ($mobileBadgeFilters->isNotEmpty()) {
        $mobileMenuSections[] = [
            'label' => 'Etiquetas personalizadas',
            'items' => $mobileBadgeFilters
                ->map(fn ($badge) => [
                    'icon' => 'tag',
                    'text' => $badge,
                    'url' => $minimalCategoryUrl(['etiqueta' => $badge]),
                    'data' => 'minimal-badge-link',
                    'active' => $selectedMobileBadge === $badge,
                ])
                ->all(),
        ];
    }

    $mobileMenuSections[] = [
            'label' => 'Informacion movil',
            'items' => array_values(array_filter([
                ($showAboutSection ?? false)
                    ? ['icon' => 'users', 'text' => 'Nosotros', 'url' => $storefrontUrls->about($store)]
                    : null,
            ])),
        ];
@endphp

<header @class(['minimal-shop-header', 'minimal-shop-header--checkout-centered' => $useCenteredCheckoutHeader])>
    <input class="minimal-shop-menu-state" type="checkbox" id="{{ $mobileMenuId }}" aria-hidden="true">
    <input class="minimal-shop-cart-state" type="checkbox" id="{{ $cartDrawerId }}" aria-hidden="true">
    <input class="minimal-shop-search-state" type="checkbox" id="{{ $searchToggleId }}" aria-hidden="true">

    @if($showAnnouncementBar && ! empty($announcementMessages))
        <section
            class="store-announcement-bar minimal-shop-announcement-bar tech-announcement-bar"
            aria-label="Avisos de la tienda"
            @if($enableAnnouncementRotation)
                data-announcement-bar
                data-fashion-announcement
                data-fashion-announcement-interval="5200"
            @endif
        >
            <div class="tech-announcement-shell">
                <span class="tech-announcement-icon" aria-hidden="true">
                    {!! $icons::icon('truck') !!}
                </span>

                <div class="tech-announcement-viewport">
                    @foreach($visibleAnnouncementMessages as $announcementMessage)
                        <p
                            class="fashion-announcement-message tech-announcement-message {{ $loop->first ? 'is-active' : '' }}"
                            @if($enableAnnouncementRotation) data-fashion-announcement-message @endif
                            @unless($loop->first) hidden @endunless
                        >
                            {{ $announcementMessage }}
                        </p>
                    @endforeach
                </div>

                @if($enableAnnouncementRotation && count($announcementMessages) > 1)
                    <div class="tech-announcement-meta">
                        <span class="tech-announcement-count">
                            <span data-tech-announcement-current>1</span>/{{ count($announcementMessages) }}
                        </span>
                        <div class="fashion-announcement-dots tech-announcement-dots" role="tablist" aria-label="Seleccionar aviso">
                            @foreach($announcementMessages as $announcementMessage)
                                <button
                                    type="button"
                                    class="{{ $loop->first ? 'is-active' : '' }}"
                                    data-fashion-announcement-dot="{{ $loop->index }}"
                                    role="tab"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                    aria-label="Mostrar aviso {{ $loop->iteration }}"
                                ></button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <a href="{{ $techPanelCtaUrl }}" class="tech-announcement-cta">{{ $techPanelCtaText }}</a>
            </div>
        </section>
    @endif

    <div class="shell minimal-shop-nav">
        <label class="minimal-shop-menu-button" for="{{ $mobileMenuId }}" aria-label="Abrir menu">
            {!! $icons::icon('menu') !!}
        </label>

        <a href="{{ $storefrontUrls->home($store) }}" class="minimal-shop-brand" aria-label="{{ $store->name }}">
            @if($store->logo_image)
                <img src="{{ asset('storage/' . $store->logo_image) }}" alt="{{ $store->name }}" class="minimal-shop-logo" loading="eager" decoding="async">
            @else
                <span class="minimal-shop-logo minimal-shop-logo-fallback">{{ strtoupper(substr($store->name ?? 'S', 0, 1)) }}</span>
            @endif
            <span>{{ $store->name }}</span>
        </a>

        @unless($useCenteredCheckoutHeader)
            <nav class="minimal-shop-links" aria-label="Navegacion principal">
                <a href="{{ $storefrontUrls->home($store) }}" @class(['is-active' => request()->routeIs('store.show', 'subdomain.store.show')])>Inicio</a>
                @if($mobileCategories->isNotEmpty())
                    <div class="minimal-shop-nav-dropdown">
                        <button type="button" class="minimal-shop-nav-dropdown-trigger" aria-haspopup="true" aria-expanded="false">
                            <span>Categorias</span>
                            <i aria-hidden="true"></i>
                        </button>
                        <div class="minimal-shop-nav-dropdown-menu tech-mega-menu {{ $minimalNavHasSubcategories ? 'has-subcategories' : '' }}">
                            <div class="tech-mega-menu-rail minimal-shop-nav-category-group" aria-label="Categorias destacadas">
                                @foreach($mobileCategories->take(6) as $categoryLink)
                                    <a href="{{ $storefrontUrls->category($store, $categoryLink) }}" @class(['is-active' => $loop->first])>
                                        <span>{!! $icons::categoryIcon($categoryLink->name) !!}</span>
                                        <strong>{{ $categoryLink->name }}</strong>
                                        <i>{!! $icons::icon('arrow') !!}</i>
                                    </a>
                                @endforeach
                            </div>

                            <div class="tech-mega-menu-links">
                                <span>Explorar tienda</span>
                                <h2>{{ $techMenuFeaturedCategory?->name ?? 'Categorías' }}</h2>
                                <div>
                                    @foreach($mobileCategories->take(5) as $categoryLink)
                                        <a href="{{ $storefrontUrls->category($store, $categoryLink) }}">{{ $categoryLink->name }}</a>
                                    @endforeach
                                    <a href="{{ $storefrontUrls->products($store) }}">Ver todo el catálogo</a>
                                </div>
                            </div>

                            <a class="tech-mega-menu-feature" href="{{ $techMenuFeaturedUrl }}">
                                <div>
                                    <span>{{ $store->name }}</span>
                                    <strong>{{ $techMenuFeaturedTitle }}</strong>
                                    <small>{{ $techMenuFeaturedSubtitle }}</small>
                                    <b>{{ $techPanelCtaText }}</b>
                                </div>
                                @if($techMenuFeaturedProduct && $techMenuFeaturedProduct->image)
                                    <img src="{{ asset('storage/' . $techMenuFeaturedProduct->image) }}" alt="{{ $techMenuFeaturedProduct->name }}" loading="lazy" decoding="async">
                                @else
                                    <em>{!! $icons::icon($techMenuFeaturedCategory ? \App\Support\MinimalShopIcons::categoryIconKey($techMenuFeaturedCategory->name) : 'phone') !!}</em>
                                @endif
                            </a>
                        </div>
                    </div>
                @endif
                @foreach($mobileBadgeFilters as $badge)
                    <a
                        href="{{ $minimalCategoryUrl(['etiqueta' => $badge]) }}"
                        data-minimal-badge-link
                        @class(['is-active' => $selectedMobileBadge === $badge])
                    >
                        {{ $badge }}
                    </a>
                @endforeach
                @if($hasOfferProducts)
                    <a href="{{ $storefrontUrls->offers($store) }}" @class(['minimal-shop-offers-link', 'is-active' => request()->routeIs('store.offers.index', 'subdomain.store.offers.index')])>
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20.2 12.4 12.4 20.2a2.2 2.2 0 0 1-3.1 0l-5.5-5.5a2.2 2.2 0 0 1 0-3.1l7.8-7.8H20v8.6Z"></path>
                            <circle cx="16.5" cy="7.5" r="1.4"></circle>
                        </svg>
                        <span>Ofertas</span>
                    </a>
                @endif
                @if($showAboutSection ?? false)
                    <a href="{{ $storefrontUrls->about($store) }}" @class(['is-active' => request()->routeIs('store.about', 'subdomain.store.about')])>Nosotros</a>
                @endif
            </nav>
        @endunless

        @unless($useCenteredCheckoutHeader)
        <div class="minimal-shop-actions">
            @include('storefront.partials.technology-search-form', [
                'technologySearchId' => 'technologyDesktopSearch',
                'technologySearchClass' => 'tech-navbar-search',
                'technologySearchProducts' => $techHeaderProductPool,
            ])

            <label for="{{ $searchToggleId }}" class="minimal-shop-icon-link tech-navbar-search-toggle" aria-label="Buscar productos">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="6"></circle>
                    <path d="m16 16 4 4"></path>
                </svg>
            </label>

            @if($showCartControl)
                <label for="{{ $cartDrawerId }}" class="minimal-shop-icon-link minimal-shop-cart cart-link" aria-label="{{ $cartLabel }}">
                    <svg class="cart-link-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M6.5 7h14l-1.4 8.4a2 2 0 0 1-2 1.6H9.2a2 2 0 0 1-2-1.6L5.8 4.8H3.5"></path>
                        <circle cx="9.5" cy="20" r="1.4"></circle>
                        <circle cx="17" cy="20" r="1.4"></circle>
                    </svg>
                    <span class="tech-navbar-cart-text">Carrito</span>
                    @if($cartCount > 0)
                        <span class="cart-badge">{{ $cartCount }}</span>
                    @endif
                </label>
            @endif
        </div>

        <div class="minimal-shop-search-popover" role="search">
            @include('storefront.partials.technology-search-form', [
                'technologySearchId' => 'technologyPopoverSearch',
                'technologySearchClass' => 'tech-popover-search',
                'technologySearchProducts' => $techHeaderProductPool,
            ])
        </div>
        @endunless
    </div>

    @unless($useCenteredCheckoutHeader)
        <label class="minimal-shop-search-backdrop" for="{{ $searchToggleId }}" aria-hidden="true"></label>
        <label class="minimal-shop-menu-backdrop" for="{{ $mobileMenuId }}" aria-hidden="true"></label>
        @if($showCartControl)
            <label class="minimal-shop-cart-backdrop" for="{{ $cartDrawerId }}" aria-hidden="true"></label>
        @endif
    @endunless

    @if($showCartControl && ! $useCenteredCheckoutHeader)
    <aside
        class="minimal-shop-cart-drawer{{ $cartCount < 1 ? ' is-empty' : '' }}"
        aria-label="Carrito"
        data-cart-drawer
        data-cart-subtotal="{{ $drawerSubtotal }}"
        data-cart-shipping="{{ $drawerShipping }}"
        data-store-url="{{ $storefrontUrls->home($store) }}"
    >
        <div class="minimal-shop-cart-head">
            <div>
                <h2>Tu carrito</h2>
                <p><span data-cart-drawer-count>{{ $cartCount }}</span> productos</p>
            </div>
            <label for="{{ $cartDrawerId }}" aria-label="Cerrar carrito">{!! $icons::icon('close') !!}</label>
        </div>

        <div class="minimal-shop-cart-items" data-cart-drawer-items>
            @forelse($drawerCart as $cartKey => $item)
                @php
                    $drawerItemImage = trim((string) ($item['image'] ?? ''));
                    $drawerItemPrice = (float) ($item['price'] ?? 0);
                    $drawerItemQuantity = (int) ($item['quantity'] ?? 1);
                @endphp
                <article class="minimal-shop-cart-item" data-cart-drawer-item data-cart-key="{{ $cartKey }}">
                    <div class="minimal-shop-cart-thumb">
                        @if($drawerItemImage !== '')
                            <img src="{{ asset('storage/' . $drawerItemImage) }}" alt="{{ $item['name'] ?? 'Producto' }}">
                        @else
                            <span>{{ strtoupper(substr((string) ($item['name'] ?? 'P'), 0, 1)) }}</span>
                        @endif
                    </div>
                    <div class="minimal-shop-cart-info">
                        <strong>{{ $item['name'] ?? 'Producto' }}</strong>
                        <b data-cart-item-total>${{ number_format($drawerItemPrice * $drawerItemQuantity, 0, ',', '.') }}</b>
                    </div>
                    <div class="minimal-shop-cart-controls">
                        <button type="button" data-cart-drawer-minus aria-label="Restar">&minus;</button>
                        <span data-cart-drawer-quantity>{{ $drawerItemQuantity }}</span>
                        <button type="button" data-cart-drawer-plus aria-label="Sumar">+</button>
                        <button type="button" data-cart-drawer-remove aria-label="Eliminar">{!! $icons::icon('trash') !!}</button>
                    </div>
                </article>
            @empty
                <div class="minimal-shop-cart-empty" data-cart-drawer-empty>
                    <strong>Tu carrito esta vacio</strong>
                    <a href="{{ $storefrontUrls->home($store) }}">Volver a la tienda</a>
                </div>
            @endforelse
        </div>

        <div class="minimal-shop-cart-summary">
            <p><span>Subtotal</span><strong data-cart-drawer-subtotal>${{ number_format($drawerSubtotal, 0, ',', '.') }}</strong></p>
            <p><span>Envio</span><strong data-cart-drawer-shipping>Por calcular</strong></p>
            <p class="minimal-shop-cart-total"><span>Total</span><strong data-cart-drawer-total>${{ number_format($drawerTotal, 0, ',', '.') }}</strong></p>
        </div>

        <div class="minimal-shop-cart-actions">
            <a href="{{ route('cart.index', ['store' => $store->slug]) }}">Finalizar compra</a>
        </div>

        <p class="minimal-shop-cart-secure">{!! $icons::icon('lock') !!} Compra segura</p>
    </aside>
    @endif

    @unless($useCenteredCheckoutHeader)
    <aside class="minimal-shop-mobile-menu" aria-label="Menu movil">
        <label class="minimal-shop-menu-close" for="{{ $mobileMenuId }}" aria-label="Cerrar menu">
            {!! $icons::icon('close') !!}
        </label>

        <a href="{{ $storefrontUrls->home($store) }}" class="minimal-shop-mobile-brand" aria-label="{{ $store->name }}">
            @if($store->logo_image)
                <img src="{{ asset('storage/' . $store->logo_image) }}" alt="{{ $store->name }}">
            @else
                <span>{{ strtoupper(substr($store->name ?? 'S', 0, 1)) }}</span>
            @endif
            <strong>{{ $store->name }}</strong>
        </a>

        @include('storefront.partials.technology-search-form', [
            'technologySearchId' => 'technologyMobileSearch',
            'technologySearchClass' => 'tech-mobile-menu-search',
            'technologySearchProducts' => $techHeaderProductPool,
        ])

        <nav class="minimal-shop-mobile-section tech-mobile-menu-primary" aria-label="Menu principal movil">
            <a href="{{ $storefrontUrls->home($store) }}" class="is-active">
                <span>{!! $icons::icon('home') !!}</span>
                <strong>Inicio</strong>
                <i>{!! $icons::icon('arrow') !!}</i>
            </a>
            @if($hasOfferProducts)
                <a href="{{ $storefrontUrls->offers($store) }}">
                    <span>{!! $icons::icon('tag') !!}</span>
                    <strong>Ofertas</strong>
                    <i>{!! $icons::icon('arrow') !!}</i>
                </a>
            @endif
        </nav>

        <nav class="minimal-shop-mobile-section tech-mobile-menu-categories" aria-label="Categorias moviles">
            <details class="minimal-shop-mobile-category-group tech-mobile-category-dropdown">
                <summary>
                    <span>{!! $icons::icon('grid') !!}</span>
                    <strong>Categorias</strong>
                    <em>{{ $mobileCategories->count() }}</em>
                    <i>{!! $icons::icon('arrow') !!}</i>
                </summary>
                <div>
                    @foreach($mobileCategories as $categoryLink)
                        @php($categoryChildren = $store->allowsSubcategories() ? ($categoryLink->activeChildren ?? collect()) : collect())
                        @if($categoryChildren->isNotEmpty())
                            <details class="minimal-shop-mobile-category-group tech-mobile-subcategory-dropdown">
                                <summary>
                                    <span>{!! $icons::categoryIcon($categoryLink->name) !!}</span>
                                    <strong>{{ $categoryLink->name }}</strong>
                                    <i>{!! $icons::icon('arrow') !!}</i>
                                </summary>
                                <div>
                                    <a href="{{ $storefrontUrls->category($store, $categoryLink) }}" data-minimal-category-link>Ver todo</a>
                                    @foreach($categoryChildren as $subcategoryLink)
                                        <a href="{{ $storefrontUrls->category($store, $subcategoryLink) }}" data-minimal-category-link>{{ $subcategoryLink->name }}</a>
                                    @endforeach
                                </div>
                            </details>
                        @else
                            <a href="{{ $minimalCategoryUrl(['categoria' => $categoryLink->slug]) }}" data-minimal-category-link>
                                <span>{!! $icons::categoryIcon($categoryLink->name) !!}</span>
                                <strong>{{ $categoryLink->name }}</strong>
                            </a>
                        @endif
                    @endforeach
                </div>
            </details>
        </nav>

        @foreach($mobileMenuSections as $section)
            <nav class="minimal-shop-mobile-section" aria-label="{{ $section['label'] }}">
                @foreach($section['items'] as $item)
                    <a
                        href="{{ $item['url'] }}"
                        @if(! empty($item['data'])) data-{{ $item['data'] }} @endif
                        @class(['is-active' => ! empty($item['active'])])
                    >
                        <span>{!! $icons::icon($item['icon']) !!}</span>
                        <strong>{{ $item['text'] }}</strong>
                        <i>{!! $icons::icon('arrow') !!}</i>
                    </a>
                @endforeach
            </nav>
        @endforeach

        <a class="tech-mobile-cart-cta" href="{{ route('cart.index', ['store' => $store->slug]) }}">
            {!! $icons::icon('bag') !!}
            <span>Carrito ({{ $cartCount }})</span>
        </a>
    </aside>
    @endunless
</header>
