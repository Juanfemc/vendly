@php
    $announcementMessages = \App\Models\Store::supportsCommercialNoticeColumns()
        ? $store->announcementMessages()
        : [];
    $storeWhatsappUrl = $store->whatsappInfoUrl();
    $canManageStore = $canManageStore ?? false;
    $cartCount = $cartCount ?? 0;
    $instagramUrl = $instagramUrl ?? null;
    $facebookUrl = $facebookUrl ?? null;
    $tiktokUrl = $tiktokUrl ?? null;
    $hasOfferProducts = $store->hasOfferProducts();
    $drawerCart = app(\App\Services\CartService::class)->cartForStore($store);
    $drawerSubtotal = collect($drawerCart)->sum(fn ($item) => (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 1));
    $drawerShipping = 0;
    $drawerTax = 0;
    $drawerTotal = $drawerSubtotal + $drawerShipping + $drawerTax;
    $drawerProgress = $drawerSubtotal > 0 ? min(100, max(18, ($drawerSubtotal / 100) * 72)) : 0;
    $fashionBrandWords = preg_split('/\s+/', trim((string) $store->name)) ?: [];
    $fashionBrandInitials = collect($fashionBrandWords)
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_substr($word, 0, 1))
        ->implode('');
    $fashionBrandInitials = mb_strtoupper($fashionBrandInitials !== '' ? $fashionBrandInitials : 'T');
    $fashionSearchProductsSource = collect($allProducts ?? []);

    if ($fashionSearchProductsSource->isEmpty()) {
        $fashionSearchProductsSource = $store->products()
            ->latest()
            ->take(48)
            ->get();
    }

    $fashionSearchProducts = $fashionSearchProductsSource
        ->take(48)
        ->map(fn ($product) => [
            'name' => (string) $product->name,
            'search' => \Illuminate\Support\Str::lower((string) $product->name . ' ' . (string) $product->category . ' ' . (string) $product->material),
            'price' => '$ ' . number_format((float) $product->price, 0, ',', '.'),
            'image' => $product->image ? asset('storage/' . $product->image) : null,
            'url' => $storefrontUrls->product($store, $product),
        ])
        ->values();
@endphp

<input class="fashion-cart-state minimal-shop-cart-state" type="checkbox" id="minimalShopCartToggle" aria-hidden="true">

<div class="storefront-topbar fashion-topbar" data-storefront-topbar>
    @if(! empty($announcementMessages))
        <section class="store-announcement-bar" aria-label="Avisos de la tienda" data-announcement-bar data-announcement-speed="42">
            <div class="shell store-announcement-shell">
                <div class="store-announcement-viewport">
                    <div class="store-announcement-message is-marquee-active" data-announcement-message>
                        @for($announcementLoop = 0; $announcementLoop < 8; $announcementLoop++)
                            <p class="store-announcement-group" @if($announcementLoop > 0) aria-hidden="true" @endif>
                                @foreach($announcementMessages as $announcementMessage)
                                    <span>{{ $announcementMessage }}</span>
                                @endforeach
                            </p>
                        @endfor
                    </div>
                </div>
            </div>
        </section>
    @endif

    <header class="fashion-navbar navbar">
        <input class="fashion-search-state" type="checkbox" id="fashionSearchToggle" aria-hidden="true">

        <div class="fashion-navbar-shell">
            <a href="{{ $storefrontUrls->home($store) }}" class="fashion-brand" aria-label="{{ $store->name }}">
                @if($store->logo_image)
                    <img src="{{ asset('storage/' . $store->logo_image) }}" alt="{{ $store->name }}" loading="eager" decoding="async">
                @else
                    <span>{{ $fashionBrandInitials }}</span>
                @endif
            </a>

            <button
                type="button"
                class="fashion-menu-toggle nav-toggle"
                aria-expanded="false"
                aria-controls="storefrontNavPanel"
                aria-label="Abrir menu"
            >
                <span></span>
                <span></span>
                <span></span>
            </button>

            <div class="fashion-mobile-actions" aria-label="Acciones rapidas">
                <label for="minimalShopCartToggle" class="fashion-icon-link fashion-cart-link cart-link" role="button" tabindex="0" aria-label="Carrito">
                    <svg class="fashion-cart-icon cart-link-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M6.5 8.5h11l-.8 11h-9.4l-.8-11Z"></path>
                        <path d="M9 8.5a3 3 0 0 1 6 0"></path>
                    </svg>
                    @if($cartCount > 0)
                        <span class="fashion-cart-badge cart-badge">{{ $cartCount }}</span>
                    @endif
                </label>
            </div>

            <div class="fashion-nav-panel nav-panel" id="storefrontNavPanel">
                <div class="nav-panel-head">
                    <span>{{ $store->name }}</span>
                    <button type="button" class="nav-close" aria-label="Cerrar menu">
                        <span></span>
                        <span></span>
                    </button>
                </div>

                <nav class="fashion-nav-links" aria-label="Navegación principal">
                    <a href="{{ $storefrontUrls->home($store) }}">
                        <span>Inicio</span>
                    </a>
                    @php
                        $fashionNavCategories = ($activeCategories ?? collect())
                            ->when(! $store->isRestaurant(), fn ($categories) => $categories->filter(fn ($category) => ! $category->parent_id))
                            ->values();
                        $fashionNavHasSubcategories = $store->allowsSubcategories()
                            && $fashionNavCategories->contains(fn ($category) => ($category->activeChildren ?? collect())->isNotEmpty());
                    @endphp
                    @if($fashionNavCategories->isNotEmpty())
                        <div class="fashion-nav-dropdown">
                            <button type="button" class="fashion-nav-dropdown-trigger" aria-haspopup="true" aria-expanded="false">
                                <span>Categorías</span>
                                <span class="fashion-nav-chevron" aria-hidden="true"></span>
                            </button>
                            <div class="fashion-nav-dropdown-menu {{ $fashionNavHasSubcategories ? 'fashion-nav-dropdown-menu--grouped' : '' }}">
                                @foreach($fashionNavCategories as $categoryLink)
                                    @if($fashionNavHasSubcategories)
                                        <div class="fashion-nav-dropdown-group">
                                            <a href="{{ $storefrontUrls->category($store, $categoryLink) }}" class="fashion-nav-dropdown-title">
                                                {{ $categoryLink->name }}
                                            </a>
                                            @foreach($categoryLink->activeChildren ?? collect() as $subcategoryLink)
                                                <a href="{{ $storefrontUrls->category($store, $subcategoryLink) }}" class="fashion-nav-subcategory-link">
                                                    {{ $subcategoryLink->name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <a href="{{ $storefrontUrls->category($store, $categoryLink) }}">
                                            {{ $categoryLink->name }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if($hasOfferProducts)
                        <a href="{{ $storefrontUrls->offers($store) }}">
                            <span>Ofertas</span>
                        </a>
                    @endif
                    @if($showAboutSection ?? false)
                        <a href="{{ $storefrontUrls->about($store) }}">
                            <span>Nosotros</span>
                        </a>
                    @endif
                </nav>

                <div class="fashion-nav-actions">
                    @include('storefront.partials.fashion-search-form', [
                        'fashionSearchId' => 'fashionDesktopSearch',
                        'fashionSearchClass' => 'fashion-inline-search--desktop',
                    ])
                    <label for="minimalShopCartToggle" class="fashion-icon-link fashion-cart-link cart-link" role="button" tabindex="0" aria-label="Carrito">
                        <svg class="fashion-cart-icon cart-link-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M6.5 8.5h11l-.8 11h-9.4l-.8-11Z"></path>
                            <path d="M9 8.5a3 3 0 0 1 6 0"></path>
                        </svg>
                        @if($cartCount > 0)
                            <span class="fashion-cart-badge cart-badge">{{ $cartCount }}</span>
                        @endif
                    </label>
                </div>

                <div class="fashion-mobile-drawer">
                    @include('storefront.partials.fashion-search-form', [
                        'fashionSearchId' => 'fashionMobileSearch',
                        'fashionSearchClass' => 'fashion-inline-search--mobile fashion-mobile-drawer-search',
                    ])
                    <nav class="fashion-mobile-menu" aria-label="Menú móvil">
                        <a href="{{ $storefrontUrls->home($store) }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11.5 12 4l8 7.5"></path><path d="M6.5 10.5V20h11v-9.5"></path></svg>
                            <span>Inicio</span>
                        </a>
                        @if($fashionNavCategories->isNotEmpty())
                            <details class="fashion-mobile-category-menu">
                                <summary>
                                    <span>
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4h8l2 4-3 2v10H9V10L6 8l2-4Z"></path></svg>
                                <span>Categorías</span>
                                    </span>
                                    <span class="fashion-nav-chevron" aria-hidden="true"></span>
                                </summary>
                                <div class="{{ $fashionNavHasSubcategories ? 'fashion-mobile-category-groups' : '' }}">
                                    @foreach($fashionNavCategories as $categoryLink)
                                        @if($fashionNavHasSubcategories)
                                            <div class="fashion-mobile-category-group">
                                                <a href="{{ $storefrontUrls->category($store, $categoryLink) }}" class="fashion-mobile-category-title">
                                                    {{ $categoryLink->name }}
                                                </a>
                                                @foreach($categoryLink->activeChildren ?? collect() as $subcategoryLink)
                                                    <a href="{{ $storefrontUrls->category($store, $subcategoryLink) }}" class="fashion-nav-subcategory-link">
                                                        {{ $subcategoryLink->name }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @else
                                            <a href="{{ $storefrontUrls->category($store, $categoryLink) }}">
                                                {{ $categoryLink->name }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </details>
                        @endif
                        @if($hasOfferProducts)
                            <a href="{{ $storefrontUrls->offers($store) }}">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.2 12.4 12.4 20.2a2.2 2.2 0 0 1-3.1 0l-5.5-5.5a2.2 2.2 0 0 1 0-3.1l7.8-7.8H20v8.6Z"></path><circle cx="16.5" cy="7.5" r="1.4"></circle></svg>
                                <span>Ofertas</span>
                            </a>
                        @endif
                        @if($showAboutSection ?? false)
                            <a href="{{ $storefrontUrls->about($store) }}">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="8" r="3"></circle><circle cx="16" cy="7" r="3"></circle><path d="M3 20a5 5 0 0 1 10 0"></path><path d="M12 20a5 5 0 0 1 9 0"></path></svg>
                                <span>Nosotros</span>
                            </a>
                        @endif
                    </nav>

                    @include('storefront.partials.fashion-social-links', [
                        'class' => 'fashion-mobile-socials',
                        'label' => 'Redes sociales de ' . $store->name,
                    ])
                </div>
            </div>

            <button type="button" class="nav-backdrop" aria-label="Cerrar menu"></button>
        </div>

        <label class="fashion-search-backdrop" for="fashionSearchToggle" aria-hidden="true"></label>
        <div class="fashion-search-popover" role="dialog" aria-modal="true" aria-label="Buscar productos">
            <form action="{{ $storefrontUrls->products($store) }}" method="GET" role="search">
                <label for="fashionSearchInput">Buscar en {{ $store->name }}</label>
                <div>
                    <input
                        id="fashionSearchInput"
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Buscar producto..."
                        autocomplete="off"
                    >
                    <button type="submit">Buscar</button>
                </div>
            </form>
            <label class="fashion-search-close" for="fashionSearchToggle" aria-label="Cerrar buscador">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
            </label>
        </div>

    </header>
</div>

<label class="fashion-cart-backdrop" for="minimalShopCartToggle" aria-hidden="true"></label>

        <aside
            class="fashion-cart-drawer minimal-shop-cart-drawer{{ $cartCount < 1 ? ' is-empty' : '' }}"
            aria-label="Carrito"
            data-cart-drawer
            data-cart-subtotal="{{ $drawerSubtotal }}"
            data-cart-shipping="{{ $drawerShipping }}"
            data-cart-tax="{{ $drawerTax }}"
            data-store-url="{{ $storefrontUrls->home($store) }}"
        >
            <div class="minimal-shop-cart-head">
                <h2>Tu carrito (<span data-cart-drawer-count>{{ $cartCount }}</span>)</h2>
                <label for="minimalShopCartToggle" aria-label="Cerrar carrito">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
                </label>
            </div>

            <div class="minimal-shop-cart-items" data-cart-drawer-items>
                @forelse($drawerCart as $cartKey => $item)
                    @php
                        $drawerItemImage = trim((string) ($item['image'] ?? ''));
                        $drawerItemPrice = (float) ($item['price'] ?? 0);
                        $drawerItemQuantity = (int) ($item['quantity'] ?? 1);
                        $drawerVariant = trim(collect([$item['color'] ?? null, $item['size'] ?? null])->filter()->implode(' / '));
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
                            <small>{{ $drawerVariant !== '' ? $drawerVariant : 'Sin variante' }}</small>
                            <b data-cart-item-total>${{ number_format($drawerItemPrice * $drawerItemQuantity, 0, ',', '.') }}</b>
                        </div>
                        <div class="minimal-shop-cart-controls">
                            <button type="button" data-cart-drawer-minus aria-label="Restar">&minus;</button>
                            <span data-cart-drawer-quantity>{{ $drawerItemQuantity }}</span>
                            <button type="button" data-cart-drawer-plus aria-label="Sumar">+</button>
                            <button type="button" data-cart-drawer-remove aria-label="Eliminar">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16"></path><path d="M10 11v6M14 11v6"></path><path d="M6 7l1 14h10l1-14"></path><path d="M9 7V4h6v3"></path></svg>
                            </button>
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
                <p><span>Impuestos</span><strong data-cart-drawer-tax>$0</strong></p>
                <p class="minimal-shop-cart-total"><span>Total</span><small>COP</small><strong data-cart-drawer-total>${{ number_format($drawerTotal, 0, ',', '.') }}</strong></p>
            </div>

            <div class="minimal-shop-cart-actions">
                <a href="{{ route('cart.index', ['store' => $store->slug]) }}">Ver carrito</a>
                <a href="{{ route('cart.index', ['store' => $store->slug]) }}">Finalizar compra</a>
            </div>

        </aside>
