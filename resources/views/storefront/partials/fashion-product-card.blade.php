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
    $fashionCartIcon = $fashionCartIcon ?? '<svg class="fashion-product-cart-bag-icon" viewBox="0 0 512 512" aria-hidden="true" focusable="false"><path fill="currentColor" fill-rule="evenodd" d="M169 169v-25c0-48 39-87 87-87s87 39 87 87v25h25c23 0 42 16 46 39l47 236c5 27-15 52-43 52H94c-28 0-48-25-43-52l47-236c4-23 23-39 46-39h25Zm42 0h90v-25c0-25-20-45-45-45s-45 20-45 45v25Zm153 183a15 15 0 0 0-15-15h-29v-29a15 15 0 0 0-30 0v29h-29a15 15 0 0 0 0 30h29v29a15 15 0 0 0 30 0v-29h29a15 15 0 0 0 15-15Z"/></svg>';
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
