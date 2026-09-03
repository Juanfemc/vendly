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
    $fashionCartIcon = $fashionCartIcon ?? '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7.2 9.2h9.6l-.7 10a2 2 0 0 1-2 1.8H9.9a2 2 0 0 1-2-1.8l-.7-10Z"/><path d="M9.5 9.2V7.4a2.5 2.5 0 0 1 5 0v1.8"/><path d="M12 13.1v4.2"/><path d="M9.9 15.2h4.2"/></svg>';
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

        @if($fashionProductSoldOut)
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
