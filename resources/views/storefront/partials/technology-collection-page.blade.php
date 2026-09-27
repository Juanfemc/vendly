@php
    $collectionKind = $collectionKind ?? 'category';
    $collectionTitle = $collectionTitle ?? ($collectionKind === 'offers' ? 'Ofertas' : ($category->name ?? 'Categoría'));
    $collectionDescription = $collectionDescription ?? null;
    $collectionTotal = method_exists($products, 'total') ? $products->total() : $products->count();
    $collectionProducts = collect(method_exists($products, 'items') ? $products->items() : $products)->values();
    $icons = \App\Support\MinimalShopIcons::class;
    $topLevelCategories = ($activeCategories ?? collect())->filter(fn ($categoryItem) => ! $categoryItem->parent_id)->values();
    $activeCategorySlug = $category->slug ?? null;
    $categoryCountLabel = fn (int $count) => $count . ' ' . ($count === 1 ? 'producto' : 'productos');
    $heroImage = $store->cover_image ? asset('storage/' . $store->cover_image) : null;
@endphp

<section class="tech-collection-hero tech-collection-hero--{{ $collectionKind }}">
    <div class="tech-collection-hero-copy">
        <nav class="tech-collection-breadcrumb" aria-label="Miga de pan">
            <a href="{{ $storefrontUrls->home($store) }}">Inicio</a>
            <span aria-hidden="true">/</span>
            <span>{{ $collectionTitle }}</span>
        </nav>

        <h1>{{ $collectionTitle }}</h1>

        <div class="tech-collection-stats" aria-label="Resumen de la colección">
            <span>{{ $categoryCountLabel((int) $collectionTotal) }}</span>
            @if($collectionKind === 'offers')
                <b>Ofertas activas</b>
            @elseif($category->description ?? false)
                <b>Categoría destacada</b>
            @endif
        </div>
    </div>

    <div class="tech-collection-visual" aria-hidden="true">
        @if($heroImage)
            <img src="{{ $heroImage }}" alt="" loading="lazy" decoding="async">
        @else
            <span>{!! $icons::icon($collectionKind === 'offers' ? 'tag' : \App\Support\MinimalShopIcons::categoryIconKey($collectionTitle)) !!}</span>
        @endif
    </div>
</section>

<section class="tech-collection-panel" id="catalogo">
    @include('storefront.partials.product-search', [
        'productSearchId' => $collectionKind,
        'productSearchAction' => $collectionAction,
    ])

    <div class="tech-collection-results">
        @if($collectionProducts->isNotEmpty())
            <div class="minimal-shop-catalog-shell tech-collection-catalog-shell" data-minimal-catalog-shell>
                <div class="minimal-shop-product-grid">
                    @foreach($collectionProducts as $product)
                        @include('storefront.partials.minimal-product-card', ['product' => $product, 'isRecommendation' => false])
                    @endforeach
                </div>

                @if(method_exists($products, 'hasPages') && $products->hasPages())
                    <div class="minimal-shop-pagination">
                        {{ $products->fragment('catalogo')->links('storefront.partials.pagination') }}
                    </div>
                @endif

                @if(! method_exists($products, 'hasMorePages') || ! $products->hasMorePages())
                    <p class="catalog-end-message minimal-shop-end-message">Has visto todos los productos</p>
                @endif
            </div>
        @else
            <div class="minimal-shop-empty-state tech-collection-empty">
                @if(($searchQuery ?? '') !== '')
                    No encontramos productos para esa búsqueda.
                @elseif($collectionKind === 'offers')
                    Aún no hay productos en oferta.
                @else
                    Aún no hay productos en esta categoría.
                @endif
            </div>
        @endif
    </div>
</section>
