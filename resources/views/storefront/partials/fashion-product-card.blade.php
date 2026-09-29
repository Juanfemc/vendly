@php
    if (isset($store) && (int) $product->store_id === (int) $store->id && ! $product->relationLoaded('store')) {
        $product->setRelation('store', $store);
    }

    $fashionProductCategorySlugs = isset($fashionCategorySlugsByName)
        ? collect($fashionCategorySlugsByName->get(
            mb_strtolower(trim((string) $product->category)),
            [\Illuminate\Support\Str::slug($product->category ?: 'Productos')]
        ))
        : collect([\Illuminate\Support\Str::slug($product->category ?: 'Productos')]);
    $fashionProductCategorySlugs = $fashionProductCategorySlugs
        ->push(\Illuminate\Support\Str::slug($product->category ?: 'Productos'))
        ->filter()
        ->unique()
        ->values();
    $fashionProductCategorySlug = $fashionProductCategorySlugs->first() ?: 'productos';
    $fashionProductSizes = collect(is_array($product->sizes) ? $product->sizes : [])
        ->map(fn ($size) => \Illuminate\Support\Str::slug((string) $size))
        ->filter()
        ->implode(',');
    $fashionProductPrice = (float) $product->price;
    $fashionProductBadges = $product->displayBadges($store ?? null);
    $fashionProductStockLabel = $product->stockLabel();
    $fashionProductSoldOut = $product->isSoldOut();
    $fashionProductShowsOfferPricing = isset($store) && $store->allowsOfferBadges() && $product->hasOfferPricing();
    $fashionCartIcon = $fashionCartIcon ?? '<svg class="fashion-product-cart-bag-icon" viewBox="0 0 512 512" aria-hidden="true" focusable="false"><g class="fashion-product-cart-bag-outline" fill="none" stroke="currentColor" stroke-width="26" stroke-linecap="round" stroke-linejoin="round"><path d="M126 185H386V400H126Z"/><path d="M190 215V155C190 105 220 76 256 76C292 76 322 105 322 155V215"/></g><circle class="fashion-product-cart-bag-badge" cx="374" cy="386" r="90" fill="#fff"/><path class="fashion-product-cart-bag-plus" d="M374 336V436M324 386H424" fill="none" stroke="#111" stroke-width="40" stroke-linecap="round"/></svg>';
@endphp

<article
    class="fashion-product"
    data-fashion-product
    data-fashion-category="{{ $fashionProductCategorySlug }}"
    data-fashion-categories="{{ $fashionProductCategorySlugs->implode(',') }}"
    data-fashion-name="{{ \Illuminate\Support\Str::lower($product->name) }}"
    data-fashion-price="{{ (float) $fashionProductPrice }}"
    data-fashion-sizes="{{ $fashionProductSizes }}"
>
    <div class="fashion-product-media-shell">
        @if($fashionProductBadges !== [])
            <div class="fashion-product-badges" aria-label="Etiquetas del producto">
                @foreach($fashionProductBadges as $badge)
                    <span>{{ $badge }}</span>
                @endforeach
            </div>
        @endif

        @if($fashionProductStockLabel)
            <span class="fashion-product-stock {{ $fashionProductSoldOut ? 'is-sold-out' : '' }}">{{ $fashionProductStockLabel }}</span>
        @endif

        <a class="fashion-product-media" href="{{ $storefrontUrls->product($store, $product) }}">
            @if($product->image)
                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
            @else
                <span>{{ $product->name }}</span>
            @endif
        </a>

        @if(! $fashionProductSoldOut && $product->hasVariants())
            <a class="fashion-product-cart-float" href="{{ $storefrontUrls->product($store, $product) }}" aria-label="Ver opciones de {{ $product->name }}">
                {!! $fashionCartIcon !!}
                <span>Ver opciones</span>
            </a>
        @elseif(! $fashionProductSoldOut)
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
            @if($fashionProductShowsOfferPricing)
                <span class="fashion-product-price-before">${{ number_format((float) $product->offer_original_price, 0, ',', '.') }}</span>
            @endif
            <strong>${{ number_format((float) $fashionProductPrice, 0, ',', '.') }}</strong>
        </div>
        @if($product->hasWholesalePricing($store ?? null))
            <span class="product-wholesale-note">Mayorista desde {{ $product->wholesale_min_quantity }} unidades: ${{ number_format((float) $product->wholesale_price, 0, ',', '.') }}</span>
        @endif
    </div>
</article>
