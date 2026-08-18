@php
    $fashionProducts = ($allProducts ?? collect())->values();
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
    $fashionCategoryLinks = ($activeCategories ?? collect())
        ->filter(fn ($category) => ! $category->parent_id)
        ->values();
    $fashionCategorySlugByName = $fashionCategoryLinks
        ->mapWithKeys(fn ($category) => [
            mb_strtolower(trim((string) $category->name)) => $category->slug ?: \Illuminate\Support\Str::slug($category->name),
        ]);
    $fashionProductCategoryTabs = $fashionProducts
        ->pluck('category')
        ->filter()
        ->unique()
        ->values();
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
    $fashionSizeOptions = $fashionProducts
        ->flatMap(fn ($product) => collect(is_array($product->sizes) ? $product->sizes : []))
        ->map(fn ($size) => trim((string) $size))
        ->filter()
        ->unique()
        ->sortBy(fn ($size) => mb_strtolower($size))
        ->values();
    $fashionHeroImage = $heroImage;
    $fashionRecommended = $fashionProducts->take(6)->map(fn ($product, $index) => [
        'category' => $product->category ?: 'Jackets',
        'name' => $product->name,
        'price' => (float) $product->price,
        'image' => $product->image ? asset('storage/' . $product->image) : null,
        'url' => $storefrontUrls->product($store, $product),
        'reviews' => 12 + $index,
    ])->values();

    if ($fashionRecommended->count() < 6) {
        $fashionRecommended = $fashionRecommended
            ->concat($placeholderProducts->skip($fashionRecommended->count())->take(6 - $fashionRecommended->count())->map(fn ($product, $index) => [
                'category' => $product['category'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => null,
                'url' => $storefrontUrls->products($store),
                'reviews' => 12 + $index,
            ]))
            ->values();
    }
    $fashionHeroOverlayEnabled = $supportsHeroOverlay && (bool) ($store->show_hero_overlay ?? false);
    $fashionHeroEyebrow = $supportsHeroOverlay ? trim((string) ($store->hero_overlay_eyebrow ?? '')) : '';
    $fashionHeroTitle = $supportsHeroOverlay ? trim((string) ($store->hero_overlay_title ?? '')) : '';
    $fashionHeroButtonText = $supportsHeroOverlay ? trim((string) ($store->hero_overlay_button_text ?? '')) : '';
    $fashionHeroButtonUrl = ($supportsHeroOverlay && trim((string) ($store->hero_overlay_button_url ?? '')) !== '')
        ? trim((string) $store->hero_overlay_button_url)
        : $storefrontUrls->products($store);
    $fashionHeroHasCopy = $fashionHeroOverlayEnabled
        && ($fashionHeroEyebrow !== '' || $fashionHeroTitle !== '' || $fashionHeroButtonText !== '');
    $fashionCartIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="20" r="1.6"/><circle cx="18" cy="20" r="1.6"/><path d="M3 4h2.4l2.2 10.4a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 8H7"/><path d="M12 11h5"/><path d="M14.5 8.5v5"/></svg>';
@endphp

<section class="fashion-hero">
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

<section class="fashion-arrivals" id="catalogo">
    <div class="fashion-section-head">
        <nav class="fashion-category-tabs" aria-label="Categorias destacadas" data-fashion-category-tabs>
            @foreach($fashionTabs as $tab)
                <a
                    href="{{ $tab['url'] ?? '#catalogo' }}"
                    data-fashion-category-filter="{{ $tab['slug'] }}"
                    aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                    @class(['is-active' => $loop->first])
                >
                    {{ $tab['name'] }}
                </a>
            @endforeach
        </nav>
    </div>

    <div class="fashion-catalog-controls" aria-label="Filtros de productos">
        @if($fashionSizeOptions->isNotEmpty())
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
    </div>

    <div class="fashion-product-grid" data-fashion-product-grid>
        @forelse($fashionProducts as $product)
            @php
                $fashionProductCategorySlug = $fashionCategorySlugByName->get(
                    mb_strtolower(trim((string) $product->category)),
                    \Illuminate\Support\Str::slug($product->category ?: 'Jackets')
                );
                $fashionProductSizes = collect(is_array($product->sizes) ? $product->sizes : [])
                    ->map(fn ($size) => \Illuminate\Support\Str::slug((string) $size))
                    ->filter()
                    ->implode(',');
            @endphp
            <article
                class="fashion-product"
                data-fashion-product
                data-fashion-category="{{ $fashionProductCategorySlug }}"
                data-fashion-name="{{ \Illuminate\Support\Str::lower($product->name) }}"
                data-fashion-price="{{ (float) $product->price }}"
                data-fashion-sizes="{{ $fashionProductSizes }}"
            >
                <div class="fashion-product-media-shell">
                    <a class="fashion-product-media" href="{{ $storefrontUrls->product($store, $product) }}">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
                        @else
                            <span>{{ $product->name }}</span>
                        @endif
                    </a>

                    @if($product->isSoldOut())
                        <span class="fashion-product-cart-float fashion-product-cart-float--disabled">Agotado</span>
                    @elseif($product->hasVariants())
                        <a class="fashion-product-cart-float" href="{{ $storefrontUrls->product($store, $product) }}" aria-label="Ver opciones de {{ $product->name }}">
                            {!! $fashionCartIcon !!}
                            <span>Ver opciones</span>
                        </a>
                    @else
                        <form action="{{ route('cart.add', $product->id) }}" method="POST" class="fashion-product-cart-form add-to-cart-form" data-compact-fashion-cart>
                            @csrf
                            <button type="submit" class="fashion-product-cart-float" aria-label="Agregar {{ $product->name }} al carrito">
                                {!! $fashionCartIcon !!}
                                <span>Agregar al carrito</span>
                            </button>
                        </form>
                    @endif
                </div>
                <div class="fashion-product-copy">
                    <p class="fashion-product-category">{{ $product->category ?: 'Productos' }}</p>
                    <h3>
                        <a href="{{ $storefrontUrls->product($store, $product) }}">
                            {{ $product->name }}
                        </a>
                    </h3>
                    <div class="fashion-product-foot">
                        <strong>${{ number_format((float) $product->price, 0, ',', '.') }}</strong>
                        @if($product->hasVariants())
                            <span>Opciones</span>
                        @elseif($product->isSoldOut())
                            <span>Agotado</span>
                        @else
                            <span>Disponible</span>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            @foreach($placeholderProducts as $placeholder)
                <article
                    class="fashion-product"
                    data-fashion-product
                    data-fashion-category="{{ \Illuminate\Support\Str::slug($placeholder['category']) }}"
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
                            <span>Disponible</span>
                        </div>
                    </div>
                </article>
            @endforeach
        @endforelse
    </div>

    @if($fashionProducts->isNotEmpty())
        <p class="catalog-end-message fashion-end-message" data-fashion-end-message>Has visto todos los productos</p>
    @endif
    <p class="fashion-empty-state" data-fashion-empty-state hidden>No hay productos en esta categoria por ahora.</p>
</section>
