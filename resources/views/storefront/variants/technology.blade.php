@php
    $categoryLinks = ($activeCategories ?? collect())
        ->filter(fn ($category) => ! $category->parent_id)
        ->values();
    $minimalProducts = ($allProducts ?? collect())->values();
    $icons = \App\Support\MinimalShopIcons::class;
    $minimalHomeUrl = $storefrontUrls->home($store);
    $minimalCategoryUrl = fn (array $query = []) => $minimalHomeUrl . ($query ? '?' . http_build_query($query) : '') . '#catalogo';
    $selectedHomeCategorySlug = $selectedHomeCategory?->slug;
    $techHeroTitle = trim((string) ($heroOverlayTitle ?? ''));
    $techHeroTitle = $techHeroTitle !== '' ? $techHeroTitle : 'Tecnología que te acompaña';
    $techHeroTitleWords = preg_split('/\s+/', $techHeroTitle) ?: [];
    $techHeroTitleAccent = count($techHeroTitleWords) > 1 ? array_pop($techHeroTitleWords) : null;
    $techHeroTitleLead = $techHeroTitleAccent ? implode(' ', $techHeroTitleWords) : $techHeroTitle;
    $techHeroCopy = trim((string) ($heroShortCopy ?? $store->shop_copy ?? ''));
    $techHeroCopy = $techHeroCopy !== ''
        ? $techHeroCopy
        : 'Explora los productos disponibles de ' . $store->name . ' en un solo lugar.';
    $techHeroPrimaryText = $showHeroProductsAction && trim((string) $heroOverlayButtonText) !== ''
        ? $heroOverlayButtonText
        : 'Comprar ahora';
    $techHeroPrimaryUrl = $showHeroProductsAction && trim((string) $heroOverlayButtonUrl) !== ''
        ? $heroOverlayButtonUrl
        : '#catalogo';
    $techCategoryItems = collect([
        [
            'name' => 'Todos',
            'url' => $minimalCategoryUrl(),
            'active' => ! $selectedHomeCategorySlug,
            'count' => $productsTotal,
            'icon' => $icons::categoryIcon('Todos los productos'),
        ],
    ])->merge(
        $categoryLinks->map(fn ($categoryLink) => [
            'name' => $categoryLink->name,
            'url' => $minimalCategoryUrl(['categoria' => $categoryLink->slug]),
            'active' => $selectedHomeCategorySlug === $categoryLink->slug,
            'count' => (int) (($categoryProductCounts ?? collect())[$categoryLink->name] ?? 0),
            'icon' => $icons::categoryIcon($categoryLink->name),
        ])
    )->values();
@endphp

<section class="minimal-shop-hero">
    <div @class([
        'minimal-shop-hero-media',
        'tech-hero-showcase',
    ])>
        <div class="tech-hero-copy">
            <h1>
                {{ $techHeroTitleLead }}
                @if($techHeroTitleAccent)
                    <span>{{ $techHeroTitleAccent }}</span>
                @endif
            </h1>
            <p>{{ $techHeroCopy }}</p>
            <div class="tech-hero-actions">
                <a href="{{ $techHeroPrimaryUrl }}" class="tech-hero-primary">
                    {!! $icons::icon('bag') !!}
                    <span>{{ $techHeroPrimaryText }}</span>
                </a>
                @if($store->hasOfferProducts())
                    <a href="{{ $storefrontUrls->offers($store) }}" class="tech-hero-secondary">Ver ofertas</a>
                @else
                    <a href="{{ $storefrontUrls->products($store) }}" class="tech-hero-secondary">Ver tienda</a>
                @endif
            </div>
        </div>

        @if($heroImage)
            <img class="tech-hero-backdrop" src="{{ $heroImage }}" alt="" loading="eager" decoding="async">
        @endif
    </div>
</section>

<div class="minimal-shop-content-panel">
    <div class="minimal-shop-search-panel">
        <form action="{{ $storefrontUrls->products($store) }}" method="GET" role="search">
            <label for="minimalShopSearch">Buscar en {{ $store->name }}</label>
            <input id="minimalShopSearch" type="search" name="q" placeholder="Buscar en {{ $store->name }}..." autocomplete="off">
            <button type="submit">Buscar</button>
        </form>
    </div>

    @include('storefront.partials.technology-category-selector')
    @include('storefront.partials.technology-filter-panel')

    <section class="minimal-shop-layout" id="catalogo" aria-label="Catálogo">
        @include('storefront.partials.minimal-catalog', ['catalogProducts' => $catalogProducts])
    </section>

</div>
