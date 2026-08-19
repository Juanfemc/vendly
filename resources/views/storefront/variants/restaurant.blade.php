@php
    $restaurantSections = collect($visibleCategorySections ?? []);
    $restaurantCategories = collect($activeCategories ?? []);
    $restaurantProducts = $restaurantSections
        ->flatMap(fn ($section) => collect($section['products'] ?? []))
        ->merge(collect($otherProducts ?? []))
        ->values();
    $restaurantCategoryImages = $restaurantSections
        ->mapWithKeys(function ($section) {
            $category = $section['category'] ?? null;
            $product = collect($section['products'] ?? [])->first(fn ($item) => filled($item->image));

            return $category ? [$category->slug => ($product?->image ? asset('storage/' . $product->image) : null)] : [];
        });
    $restaurantHeroProduct = $restaurantProducts->first();
    $restaurantHeroImage = $heroImage ?: ($restaurantHeroProduct?->image ? asset('storage/' . $restaurantHeroProduct->image) : null);
    $restaurantWhatsapp = preg_replace('/\D+/', '', (string) ($store->whatsapp ?? ''));
    $restaurantCartCount = (int) ($cartCount ?? 0);
    $restaurantFeaturedProducts = $restaurantProducts->take(6)->values();
    $restaurantSpecialDeals = $restaurantProducts->skip(6)->take(2)->values();
    if ($restaurantSpecialDeals->isEmpty()) {
        $restaurantSpecialDeals = $restaurantProducts->take(2)->values();
    }
    $restaurantServices = [
        ['title' => 'Pagos seguros', 'copy' => 'Compra con confianza desde la tienda.', 'icon' => 'card'],
        ['title' => 'Pedidos por WhatsApp', 'copy' => 'Confirma tu pedido en pocos pasos.', 'icon' => 'phone'],
        ['title' => 'Entrega a domicilio', 'copy' => 'Recibe tu comida donde estes.', 'icon' => 'delivery'],
        ['title' => 'Atencion rapida', 'copy' => 'Soporte directo para tus pedidos.', 'icon' => 'pin'],
    ];
    $restaurantCartIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="20" r="1.6"/><circle cx="18" cy="20" r="1.6"/><path d="M3 4h2.4l2.2 10.4a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 8H7"/></svg>';
    $restaurantUserIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>';
    $restaurantOptionIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/><circle cx="8" cy="7" r="2"/><circle cx="14" cy="12" r="2"/><circle cx="10" cy="17" r="2"/></svg>';
    $restaurantCategoryIcon = function (string $name): string {
        $normalized = \Illuminate\Support\Str::lower($name);

        return match (true) {
            str_contains($normalized, 'burger'), str_contains($normalized, 'hamburg') => 'B',
            str_contains($normalized, 'pollo'), str_contains($normalized, 'chicken') => 'P',
            str_contains($normalized, 'pizza') => 'Z',
            str_contains($normalized, 'ensalada'), str_contains($normalized, 'salad') => 'S',
            str_contains($normalized, 'papa'), str_contains($normalized, 'frita'), str_contains($normalized, 'fries') => 'F',
            str_contains($normalized, 'bebida'), str_contains($normalized, 'drink'), str_contains($normalized, 'jugo') => 'D',
            default => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(\Illuminate\Support\Str::ascii($name), 0, 1)) ?: 'M',
        };
    };
@endphp

<section class="restaurant-menu-hero">
    <span class="restaurant-food-pattern restaurant-food-pattern--burger" aria-hidden="true"></span>
    <span class="restaurant-food-pattern restaurant-food-pattern--drink" aria-hidden="true"></span>
    <span class="restaurant-food-pattern restaurant-food-pattern--pizza" aria-hidden="true"></span>
    <span class="restaurant-hero-word" aria-hidden="true">Menu</span>

    <div class="restaurant-menu-hero-top">
        <a href="#catalogo" class="restaurant-menu-button" aria-label="Abrir menu">
            <span></span>
            <span></span>
            <span></span>
        </a>
        <div class="restaurant-menu-mini-actions">
            <a href="{{ route('login') }}" class="restaurant-menu-account">
                {!! $restaurantUserIcon !!}
                <span>Mi cuenta</span>
            </a>
            <label for="minimalShopCartToggle" class="restaurant-menu-cart" role="button" tabindex="0">
                {!! $restaurantCartIcon !!}
                <span>Carrito</span>
                <b>{{ $restaurantCartCount }}</b>
            </label>
        </div>
    </div>

    <a href="#catalogo" class="restaurant-menu-hero-media" aria-label="Ver carta de {{ $store->name }}">
        @if($restaurantHeroImage)
            <img src="{{ $restaurantHeroImage }}" alt="{{ $store->name }}" loading="eager" fetchpriority="high" decoding="async">
        @else
            <div class="restaurant-hero-fallback">
                <span>{{ mb_substr($store->name, 0, 1) }}</span>
                <strong>Menu</strong>
            </div>
        @endif
    </a>

    <div class="restaurant-menu-hero-copy">
        <span class="restaurant-eyebrow">Menu digital</span>
        <h1>{{ $store->name }}</h1>
        <p>{{ $store->short_description ?: 'Comida lista para pedir por WhatsApp. Explora la carta, elige tus favoritos y compra sin complicarte.' }}</p>

        <div class="restaurant-menu-hero-actions">
            <a href="#catalogo" class="restaurant-primary-link">Pedir ahora</a>
            @if($restaurantWhatsapp !== '')
                <a href="https://wa.me/{{ $restaurantWhatsapp }}" class="restaurant-secondary-link" target="_blank" rel="noopener">WhatsApp</a>
            @endif
        </div>
    </div>
</section>

@if($restaurantCategories->isNotEmpty())
    <nav class="restaurant-category-strip" aria-label="Categorias del menu">
        <div class="restaurant-category-title">
            <span></span>
            <strong>Categorias</strong>
            <span></span>
        </div>
        <div class="restaurant-category-row">
            <a href="#catalogo" class="is-active">
                <span>M</span>
                <strong>Todos</strong>
            </a>
            @foreach($restaurantCategories as $category)
                <a href="{{ $storefrontUrls->category($store, $category) }}">
                    <span>
                        @if($restaurantCategoryImages->get($category->slug))
                            <img src="{{ $restaurantCategoryImages->get($category->slug) }}" alt="" loading="lazy" decoding="async">
                        @else
                            {{ $restaurantCategoryIcon($category->name) }}
                        @endif
                    </span>
                    <strong>{{ $category->name }}</strong>
                </a>
            @endforeach
        </div>
    </nav>
@endif

<section class="restaurant-menu" id="catalogo">
    @if($restaurantFeaturedProducts->isNotEmpty())
        <section class="restaurant-menu-section restaurant-menu-section--featured">
            <div class="restaurant-menu-section-head">
                <span>Favoritos</span>
                <h2>Los mas pedidos</h2>
            </div>

            <div class="restaurant-menu-items">
                @foreach($restaurantFeaturedProducts as $product)
                    @include('storefront.variants.restaurant-menu-item', ['product' => $product])
                @endforeach
            </div>
        </section>
    @else
        @if($otherProducts->isEmpty())
            <div class="empty-state">Aun no hay platos publicados.</div>
        @endif
    @endif

    @if($restaurantSpecialDeals->isNotEmpty())
        <section class="restaurant-menu-section restaurant-menu-section--deals">
            <div class="restaurant-menu-section-head">
                <span>Especiales</span>
                <h2>Combos destacados</h2>
            </div>

            <div class="restaurant-deal-grid">
                @foreach($restaurantSpecialDeals as $product)
                    @include('storefront.variants.restaurant-menu-item', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    <section class="restaurant-service-grid" aria-label="Beneficios">
        @foreach($restaurantServices as $service)
            <article>
                <span class="restaurant-service-icon restaurant-service-icon--{{ $service['icon'] }}"></span>
                <h3>{{ $service['title'] }}</h3>
                <p>{{ $service['copy'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="restaurant-menu-cta">
        <span>Explora el menu</span>
        <h2>Elige tus favoritos y haz tu pedido en minutos</h2>
        <p>{{ $store->short_description ?: 'Carta sencilla, productos claros y compra directa desde tu tienda.' }}</p>
        <div class="restaurant-menu-hero-actions">
            <a href="#catalogo" class="restaurant-primary-link">Pedir ahora</a>
            <a href="{{ $storefrontUrls->products($store) }}" class="restaurant-secondary-link">Ver menu</a>
        </div>
    </section>
</section>
