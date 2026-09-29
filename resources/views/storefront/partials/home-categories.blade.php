@php
    $homeCategories = ($activeCategories ?? collect())
        ->when(! $store->isRestaurant(), fn ($categories) => $categories->filter(fn ($category) => ! $category->parent_id))
        ->values();
    $homeCategoryItemsLabel = $itemsLabel ?? 'productos';
    $homeTotalProducts = isset($allProducts)
        ? collect($allProducts)->count()
        : collect($visibleCategorySections ?? [])->sum(fn ($section) => collect($section['products'] ?? [])->count()) + collect($otherProducts ?? [])->count();
    $homeTotalLabel = $homeTotalProducts === 1 ? 'producto' : $homeCategoryItemsLabel;
@endphp

@if($homeCategories->isNotEmpty())
    <section class="home-categories home-categories--selector" aria-label="Categorías">
        <div class="home-categories-scroll-shell">
            <button
                type="button"
                class="home-categories-nav home-categories-nav--prev"
                data-home-category-scroll="prev"
                aria-label="Ver categorías anteriores"
            >
                <span aria-hidden="true">&lsaquo;</span>
            </button>
            <div class="home-categories-track is-scrollable" data-default-category-tabs>
                <a
                    href="#catalogo"
                    class="home-category-card is-active"
                    data-default-category-filter="all"
                    data-default-category-count="{{ $homeTotalProducts }}"
                    aria-pressed="true"
                >
                    <span class="home-category-copy">
                        <strong>Todos</strong>
                        <small>{{ $homeTotalProducts }} {{ $homeTotalLabel }}</small>
                    </span>
                </a>

                @foreach($homeCategories as $homeCategory)
                    @php
                        $homeCategoryCount = (int) (($categoryProductCounts ?? collect())[$homeCategory->name] ?? 0);
                        $homeCategoryLabel = $homeCategoryCount === 1 ? 'producto' : $homeCategoryItemsLabel;
                    @endphp
                    <a
                        href="{{ $storefrontUrls->category($store, $homeCategory) }}"
                        class="home-category-card"
                        data-default-category-filter="{{ $homeCategory->slug }}"
                        data-default-category-count="{{ $homeCategoryCount }}"
                        aria-pressed="false"
                    >
                        <span class="home-category-copy">
                            <strong>{{ $homeCategory->name }}</strong>
                            <small>{{ $homeCategoryCount }} {{ $homeCategoryLabel }}</small>
                        </span>
                    </a>
                @endforeach
            </div>
            <button
                type="button"
                class="home-categories-nav home-categories-nav--next"
                data-home-category-scroll="next"
                aria-label="Ver más categorías"
            >
                <span aria-hidden="true">&rsaquo;</span>
            </button>
        </div>
    </section>
@endif
