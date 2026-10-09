@php
    $fashionProducts = ($allProducts ?? collect())->values();
    $fashionFilterProducts = ($filterProducts ?? $fashionProducts)->values();
    $placeholderProducts = collect([
        ['category' => 'Jackets', 'name' => 'Urban Pop Polo shirt, navy / blue', 'price' => 69000],
        ['category' => 'Jackets', 'name' => 'Pop TRX Vintage, navy / white', 'price' => 69000],
        ['category' => 'Jackets', 'name' => 'Pop Beckenbauer Track Jacket', 'price' => 120000],
        ['category' => 'Jackets', 'name' => 'Pop Classic t-shirt, grey / navy', 'price' => 120000],
        ['category' => 'Jackets', 'name' => 'Pop SL Cap, navy / white', 'price' => 65000],
        ['category' => 'Jackets', 'name' => 'Border Yard Pullover Hood, denim', 'price' => 110000],
        ['category' => 'Jackets', 'name' => 'Rug Pull t-shirt, white', 'price' => 69000],
        ['category' => 'Jackets', 'name' => 'Knock Knock Sweat', 'price' => 130000],
    ]);
    $fashionCategoryLinks = ($activeCategories ?? collect())->values();
    $fashionCategorySlugsByName = $fashionCategoryLinks
        ->mapWithKeys(function ($category) {
            $slugs = collect([
                $category->slug,
                \Illuminate\Support\Str::slug($category->name),
                $category->parent?->slug,
                $category->parent ? \Illuminate\Support\Str::slug($category->parent->name) : null,
            ])
                ->filter()
                ->unique()
                ->values()
                ->all();

            return [mb_strtolower(trim((string) $category->name)) => $slugs];
        });
    $fashionProductCategoryTabs = $fashionFilterProducts
        ->pluck('category')
        ->filter()
        ->unique()
        ->values();
    $fashionQueryForCategory = function (?string $categorySlug = null) {
        $query = request()->query();
        unset($query['page'], $query['infinite'], $query['partial']);

        if ($categorySlug && $categorySlug !== 'all') {
            $query['categoria'] = $categorySlug;
        } else {
            unset($query['categoria']);
        }

        return url()->current() . ($query ? ('?' . http_build_query($query)) : '');
    };
    $fashionPlaceholderCategoryTabs = $placeholderProducts
        ->pluck('category')
        ->unique()
        ->take(5)
        ->values();
$fashionTabs = $fashionCategoryLinks->isNotEmpty()
    ? $fashionCategoryLinks->map(fn ($category) => [
        'name' => $category->name,
        'slug' => $category->slug ?: \Illuminate\Support\Str::slug($category->name),
        'url' => $storefrontUrls->category($store, $category),
    ])
    : ($fashionProductCategoryTabs->isNotEmpty()
    ? $fashionProductCategoryTabs->map(fn ($category) => [
        'name' => $category,
        'slug' => \Illuminate\Support\Str::slug($category),
        'url' => '#catalogo',
    ])
    : ($fashionProducts->isEmpty()
    ? $fashionPlaceholderCategoryTabs->map(fn ($category) => [
        'name' => $category,
        'slug' => \Illuminate\Support\Str::slug($category),
        'url' => '#catalogo',
    ])
    : collect()));
$fashionTabs = collect([[
    'name' => 'Todos',
    'slug' => 'all',
    'url' => '#catalogo',
]])->concat($fashionTabs)->values();
    $activeFashionCategorySlug = trim((string) (($selectedHomeCategory ?? null)?->slug ?? request('categoria', '')));
    $activeFashionCategorySlug = $activeFashionCategorySlug !== '' ? $activeFashionCategorySlug : 'all';

    if (! $fashionTabs->contains('slug', $activeFashionCategorySlug)) {
        $activeFashionCategorySlug = 'all';
    }

    $fashionSizeOptions = $fashionFilterProducts
        ->flatMap(fn ($product) => collect(is_array($product->sizes) ? $product->sizes : []))
        ->map(fn ($size) => trim((string) $size))
        ->filter()
        ->unique()
        ->sortBy(fn ($size) => mb_strtolower($size))
        ->values();
    $showFashionSizeFilter = $store->showsFashionSizeFilter();
    $fashionHeroImage = $heroImage;
    $fashionHeroOverlayEnabled = (bool) ($showHeroProductsAction ?? false)
        || ($supportsHeroOverlay && (bool) ($store->show_hero_overlay ?? false));
    $fashionHeroEyebrow = $supportsHeroOverlay ? trim((string) ($store->hero_overlay_eyebrow ?? '')) : '';
    $fashionHeroTitle = $supportsHeroOverlay ? trim((string) ($store->hero_overlay_title ?? '')) : trim((string) ($heroOverlayTitle ?? ''));
    $fashionHeroButtonText = $supportsHeroOverlay ? trim((string) ($store->hero_overlay_button_text ?? '')) : trim((string) ($heroOverlayButtonText ?? ''));
    $fashionHeroButtonText = $fashionHeroButtonText !== '' ? $fashionHeroButtonText : trim((string) ($heroOverlayButtonText ?? 'Comprar ahora'));
    $fashionHeroButtonUrl = ($supportsHeroOverlay && trim((string) ($store->hero_overlay_button_url ?? '')) !== '')
        ? trim((string) $store->hero_overlay_button_url)
        : $storefrontUrls->products($store);
    $fashionHeroHasCopy = $fashionHeroOverlayEnabled
        && ($fashionHeroEyebrow !== '' || $fashionHeroTitle !== '' || $fashionHeroButtonText !== '');
    $fashionCartIcon = '<svg class="fashion-product-cart-bag-icon" viewBox="0 0 512 512" aria-hidden="true" focusable="false"><g class="fashion-product-cart-bag-outline" fill="none" stroke="currentColor" stroke-width="26" stroke-linecap="round" stroke-linejoin="round"><path d="M126 185H386V400H126Z"/><path d="M190 215V155C190 105 220 76 256 76C292 76 322 105 322 155V215"/></g><circle class="fashion-product-cart-bag-badge" cx="374" cy="386" r="90" fill="#fff"/><path class="fashion-product-cart-bag-plus" d="M374 336V436M324 386H424" fill="none" stroke="#111" stroke-width="40" stroke-linecap="round"/></svg>';
@endphp

<section @class(['fashion-hero', 'has-copy' => $fashionHeroHasCopy, 'is-cover-only' => ! $fashionHeroHasCopy])>
    @if($fashionHeroHasCopy)
        <div class="fashion-hero-copy">
            @if($fashionHeroEyebrow !== '')
                <span>{{ $fashionHeroEyebrow }}</span>
            @endif
            @if($fashionHeroTitle !== '')
                <h1>{{ $fashionHeroTitle }}</h1>
            @endif
            @if($fashionHeroButtonText !== '')
                <a href="{{ $fashionHeroButtonUrl }}">{{ $fashionHeroButtonText }}</a>
            @endif
        </div>
    @endif

    @if($fashionHeroImage)
        <img src="{{ $fashionHeroImage }}" alt="{{ $store->name }}" loading="eager" fetchpriority="high" decoding="async">
    @else
        <div class="fashion-hero-fallback">
            <span>{{ $store->name }}</span>
        </div>
    @endif
</section>

<section class="fashion-arrivals" id="catalogo" data-infinite-products>
    <div class="fashion-section-head">
        <nav class="fashion-category-tabs" aria-label="Categorias destacadas" data-fashion-category-tabs>
            @foreach($fashionTabs as $tab)
                @php($isActiveFashionTab = $tab['slug'] === $activeFashionCategorySlug)
                <button
                    type="button"
                    data-fashion-category-filter="{{ $tab['slug'] }}"
                    data-fashion-category-url="{{ $fashionQueryForCategory($tab['slug']) }}"
                    aria-pressed="{{ $isActiveFashionTab ? 'true' : 'false' }}"
                    @class(['is-active' => $isActiveFashionTab])
                >
                    {{ $tab['name'] }}
                </button>
            @endforeach
        </nav>
    </div>

    <button type="button" class="fashion-filter-launch" data-filter-drawer-open="fashion" aria-expanded="false">
        <span aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M4 7h16M7 12h10M10 17h4"/><circle cx="8" cy="7" r="2"/><circle cx="15" cy="12" r="2"/><circle cx="11" cy="17" r="2"/></svg>
        </span>
        Filtrar productos
    </button>

    <div class="fashion-filter-backdrop" data-filter-drawer-close="fashion" hidden></div>

    <div class="fashion-catalog-controls" aria-label="Filtros de productos" data-filter-drawer="fashion" hidden>
        <div class="fashion-filter-drawer-head">
            <span aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4 7h16M7 12h10M10 17h4"/><circle cx="8" cy="7" r="2"/><circle cx="15" cy="12" r="2"/><circle cx="11" cy="17" r="2"/></svg>
            </span>
            <strong>Filtrar productos</strong>
            <button type="button" data-filter-drawer-close="fashion" aria-label="Cerrar filtros">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        @if($showFashionSizeFilter && $fashionSizeOptions->isNotEmpty())
            <div class="fashion-size-filter" data-fashion-size-filter>
                <span>Talla</span>
                <button type="button" class="is-active" data-fashion-size-option="all" aria-pressed="true">Todas</button>
                @foreach($fashionSizeOptions as $fashionSize)
                    <button type="button" data-fashion-size-option="{{ \Illuminate\Support\Str::slug($fashionSize) }}" aria-pressed="false">
                        {{ $fashionSize }}
                    </button>
                @endforeach
            </div>
        @endif

        <label class="fashion-sort-control">
            <span>Ordenar</span>
            <select data-fashion-sort>
                <option value="default">Recomendado</option>
                <option value="name-asc">Nombre A-Z</option>
                <option value="name-desc">Nombre Z-A</option>
                <option value="price-desc">Mayor precio</option>
                <option value="price-asc">Menor precio</option>
            </select>
        </label>

        <button type="button" class="fashion-filter-apply" data-filter-drawer-close="fashion">Ver productos</button>
    </div>

    <div class="fashion-product-grid" data-fashion-product-grid data-infinite-grid>
        @forelse($fashionProducts as $product)
            @include('storefront.partials.fashion-product-card')
        @empty
            @foreach($placeholderProducts as $placeholder)
                <article
                    class="fashion-product"
                    data-fashion-product
                    data-fashion-category="{{ \Illuminate\Support\Str::slug($placeholder['category']) }}"
                    data-fashion-categories="{{ \Illuminate\Support\Str::slug($placeholder['category']) }}"
                    data-fashion-name="{{ \Illuminate\Support\Str::lower($placeholder['name']) }}"
                    data-fashion-price="{{ (float) $placeholder['price'] }}"
                    data-fashion-sizes=""
                >
                    <div class="fashion-product-media fashion-product-media--placeholder">
                        <span>{{ $placeholder['name'] }}</span>
                    </div>
                    <div class="fashion-product-copy">
                        <p class="fashion-product-category">{{ $placeholder['category'] }}</p>
                        <h3>{{ $placeholder['name'] }}</h3>
                        <div class="fashion-product-foot">
                            <strong>${{ number_format($placeholder['price'], 0, ',', '.') }}</strong>
                        </div>
                    </div>
                </article>
            @endforeach
        @endforelse
    </div>

    @if($fashionProducts->isNotEmpty())
        @if(isset($catalogProducts) && method_exists($catalogProducts, 'hasPages') && $catalogProducts->hasPages())
            <div class="store-pagination" data-infinite-pagination>
                {{ $catalogProducts->onEachSide(1)->fragment('catalogo')->links('storefront.partials.pagination') }}
            </div>
        @endif

        @include('storefront.partials.infinite-products-controls')

        @if(isset($catalogProducts) && (! method_exists($catalogProducts, 'hasMorePages') || ! $catalogProducts->hasMorePages()))
            <p class="catalog-end-message fashion-end-message" data-fashion-end-message data-infinite-end>Has visto todos los productos</p>
        @endif
    @endif
    <p class="fashion-empty-state" data-fashion-empty-state hidden>No hay productos en esta categoria por ahora.</p>
</section>
