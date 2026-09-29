@php
    $categoryLinks = ($activeCategories ?? collect())
        ->filter(fn ($category) => ! $category->parent_id)
        ->values();
    $minimalProducts = ($allProducts ?? collect())->values();
    $icons = \App\Support\MinimalShopIcons::class;
    $minimalHomeUrl = $storefrontUrls->home($store);
    $minimalCategoryUrl = fn (array $query = []) => $minimalHomeUrl . ($query ? '?' . http_build_query($query) : '') . '#catalogo';
    $selectedHomeCategorySlug = $selectedHomeCategory?->slug;
    $showTechHeroOverlay = (bool) ($store->show_hero_overlay ?? false);
    $configuredTechHeroTitle = $showTechHeroOverlay ? trim((string) ($heroOverlayTitle ?? '')) : '';
    $techHeroTitle = $configuredTechHeroTitle !== '' ? $configuredTechHeroTitle : trim((string) $store->name);
    $techHeroTitle = $techHeroTitle !== '' ? $techHeroTitle : 'Tienda';
    $techHeroTitleWords = preg_split('/\s+/', $techHeroTitle) ?: [];
    $techHeroTitleAccent = count($techHeroTitleWords) > 1 ? array_pop($techHeroTitleWords) : null;
    $techHeroTitleLead = $techHeroTitleAccent ? implode(' ', $techHeroTitleWords) : $techHeroTitle;
    $techHeroCopy = ($showTechHeroOverlay && \App\Models\Store::supportsHeroOverlaySubtitleColumn())
        ? trim((string) ($store->hero_overlay_subtitle ?? ''))
        : '';
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

<section @class(['minimal-shop-hero', 'tech-hero--image-only' => ! $showTechHeroOverlay])>
    <div @class([
        'minimal-shop-hero-media',
        'tech-hero-showcase',
        'tech-hero-showcase--image-only' => ! $showTechHeroOverlay,
    ])>
        @if($showTechHeroOverlay)
            <div class="tech-hero-copy">
                <h1>
                    {{ $techHeroTitleLead }}
                    @if($techHeroTitleAccent)
                        <span>{{ $techHeroTitleAccent }}</span>
                    @endif
                </h1>
                @if($techHeroCopy !== '')
                    <p>{{ $techHeroCopy }}</p>
                @endif
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
        @endif

        @if($heroImage)
            <img class="tech-hero-backdrop" src="{{ $heroImage }}" alt="" loading="eager" decoding="async">
        @endif
    </div>
</section>

<div class="minimal-shop-content-panel">
    @include('storefront.partials.technology-category-selector')
    @include('storefront.partials.technology-filter-panel')

    <section class="minimal-shop-layout" id="catalogo" aria-label="Catálogo">
        @include('storefront.partials.minimal-catalog', ['catalogProducts' => $catalogProducts])
    </section>

</div>
