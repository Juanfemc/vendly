@php
    $restaurantSections = collect($visibleCategorySections ?? []);
    $restaurantCategories = collect($activeCategories ?? []);
    $restaurantProducts = $restaurantSections
        ->flatMap(fn ($section) => collect($section['products'] ?? []))
        ->merge(collect($otherProducts ?? []))
        ->values();
    $restaurantHeroProduct = $restaurantProducts->first();
    $restaurantHeroImage = $heroImage ?: ($restaurantHeroProduct?->image ? asset('storage/' . $restaurantHeroProduct->image) : null);
    $restaurantWhatsapp = preg_replace('/\D+/', '', (string) ($store->whatsapp ?? ''));
    $restaurantProductsCount = (int) ($storeProductsTotal ?? $restaurantProducts->count());
    $restaurantCategoriesCount = $restaurantCategories->count();
    $restaurantCartIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="20" r="1.6"/><circle cx="18" cy="20" r="1.6"/><path d="M3 4h2.4l2.2 10.4a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 8H7"/></svg>';
    $restaurantOptionIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/><circle cx="8" cy="7" r="2"/><circle cx="14" cy="12" r="2"/><circle cx="10" cy="17" r="2"/></svg>';
@endphp

<section class="restaurant-menu-hero">
    <div class="restaurant-menu-hero-copy">
        <span class="restaurant-eyebrow">Menu digital</span>
        <h1>{{ $store->name }}</h1>
        <p>{{ $store->short_description ?: 'Comida lista para pedir por WhatsApp. Explora la carta, elige tus favoritos y compra sin complicarte.' }}</p>

        <div class="restaurant-menu-hero-actions">
            <a href="#catalogo" class="restaurant-primary-link">Ver menu</a>
            @if($restaurantWhatsapp !== '')
                <a href="https://wa.me/{{ $restaurantWhatsapp }}" class="restaurant-secondary-link" target="_blank" rel="noopener">Pedir por WhatsApp</a>
            @endif
        </div>

        <div class="restaurant-menu-stats" aria-label="Resumen del menu">
            <span><strong>{{ $restaurantProductsCount }}</strong> platos</span>
            <span><strong>{{ $restaurantCategoriesCount }}</strong> categorias</span>
            <span><strong>WhatsApp</strong> pedidos</span>
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
</section>

@if($restaurantCategories->isNotEmpty())
    <nav class="restaurant-category-strip" aria-label="Categorias del menu">
        <a href="#catalogo" class="is-active">Todos</a>
        @foreach($restaurantCategories as $category)
            <a href="#categoria-{{ $category->slug }}">{{ $category->name }}</a>
        @endforeach
    </nav>
@endif

@include('storefront.partials.product-search', ['productSearchId' => 'home'])

<section class="restaurant-menu" id="catalogo">
    @forelse($restaurantSections as $section)
        @php($sectionCategory = $section['category'])
        <section class="restaurant-menu-section" id="categoria-{{ $sectionCategory->slug }}">
            <div class="restaurant-menu-section-head">
                <div>
                    <span>Carta</span>
                    <h2>{{ $sectionCategory->name }}</h2>
                </div>

                @if($sectionCategory->description)
                    <p>{{ $sectionCategory->description }}</p>
                @endif
            </div>

            <div class="restaurant-menu-items">
                @foreach($section['products'] as $product)
                    @include('storefront.variants.restaurant-menu-item', ['product' => $product])
                @endforeach
            </div>

            <a href="{{ $storefrontUrls->category($store, $sectionCategory) }}" class="restaurant-menu-more-link">
                Ver todo en {{ $sectionCategory->name }}
            </a>
        </section>
    @empty
        @if($otherProducts->isEmpty())
            <div class="empty-state">Aun no hay platos publicados.</div>
        @endif
    @endforelse

    @if($otherProducts->isNotEmpty())
        <section class="restaurant-menu-section">
            <div class="restaurant-menu-section-head">
                <div>
                    <span>Carta</span>
                    <h2>Otros platos</h2>
                </div>
            </div>

            <div class="restaurant-menu-items">
                @foreach($otherProducts as $product)
                    @include('storefront.variants.restaurant-menu-item', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif
</section>
