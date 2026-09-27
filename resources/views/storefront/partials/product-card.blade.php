@php
    if (isset($store) && (int) $product->store_id === (int) $store->id && ! $product->relationLoaded('store')) {
        $product->setRelation('store', $store);
    }

    $stockLabel = $product->stockLabel();
    $isSoldOut = $product->isSoldOut();
    $isRestaurantCard = isset($store) && $store->isRestaurant();
    $showsOfferBadge = isset($store) && $store->allowsOfferBadges() && $product->hasOfferBadge();
    $showsOfferPricing = $showsOfferBadge && $product->hasOfferPricing();
    $displayBadges = $product->displayBadges($store ?? null);
    $productUrl = $storefrontUrls->product($store, $product);
    $defaultCategoryProduct = $defaultCategoryProduct ?? null;
    $defaultProductIndex = $defaultProductIndex ?? 0;
    $defaultProductCategoryLabel = $defaultProductCategoryLabel ?? null;
    $defaultProductSizes = collect(is_array($product->sizes) ? $product->sizes : [])
        ->map(fn ($size) => \Illuminate\Support\Str::slug((string) $size))
        ->filter()
        ->implode(',');
    $cartIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="20" r="1.6"/><circle cx="18" cy="20" r="1.6"/><path d="M3 4h2.4l2.2 10.4a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 8H7"/></svg>';
    $detailIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="3"/></svg>';
@endphp

<article
    class="product-card {{ $cardClass ?? '' }}"
    @if($defaultCategoryProduct) data-default-category-product="{{ $defaultCategoryProduct }}" @endif
    data-default-product-available="{{ $isSoldOut ? 'false' : 'true' }}"
    data-default-product-offer="{{ $showsOfferBadge ? 'true' : 'false' }}"
    data-default-product-price="{{ (float) $product->price }}"
    data-default-product-name="{{ $product->name }}"
    data-default-product-index="{{ $defaultProductIndex }}"
    data-default-product-sizes="{{ $defaultProductSizes }}"
>
    <a href="{{ $productUrl }}" class="product-image" aria-label="Ver detalle de {{ $product->name }}">
        @if($displayBadges !== [])
            <div class="product-badges">
                @foreach($displayBadges as $badge)
                    <span class="product-offer-badge">{{ $badge }}</span>
                @endforeach
            </div>
        @endif
        @if($product->image)
            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
        @else
            <span class="product-image-placeholder" aria-hidden="true">{{ strtoupper(mb_substr($product->name, 0, 1)) }}</span>
        @endif
    </a>

    <h3><a href="{{ $productUrl }}">{{ $product->name }}</a></h3>

    @if($defaultProductCategoryLabel)
        <span class="product-card-category">{{ $defaultProductCategoryLabel }}</span>
    @endif

    @if($stockLabel)
        <span class="product-stock-badge {{ $isSoldOut ? 'is-sold-out' : '' }}">{{ $stockLabel }}</span>
    @endif

    @if($product->hasWholesalePricing($store ?? null))
        <span class="product-wholesale-note">Mayorista desde {{ $product->wholesale_min_quantity }} unidades: ${{ number_format((float) $product->wholesale_price, 0, ',', '.') }}</span>
    @endif

    <div class="product-card-footer">
        <div class="price-row">
            @if($showsOfferPricing)
                <span class="price-stack">
                    <span class="price-before">${{ number_format((float) $product->offer_original_price, 0, ',', '.') }}</span>
                    <span class="price">${{ number_format((float) $product->price, 0, ',', '.') }}</span>
                </span>
            @else
                <span class="price">${{ number_format((float) $product->price, 0, ',', '.') }}</span>
            @endif
        </div>

        <div class="product-card-actions">
            @if($isSoldOut)
                <span class="product-preview-link is-disabled">Agotado</span>
            @elseif($product->hasVariants())
                <a href="{{ $productUrl }}" class="product-card-add-link">
                    {!! $cartIcon !!}
                    <span>{{ $isRestaurantCard ? 'Elegir opciones' : 'Agregar al carrito' }}</span>
                </a>
            @else
                <form action="{{ route('cart.add', $product->id) }}" method="POST" class="add-to-cart-form">
                    @csrf
                    <button type="submit">
                        {!! $cartIcon !!}
                        <span>{{ $isRestaurantCard ? 'Agregar pedido' : 'Agregar al carrito' }}</span>
                    </button>
                </form>
            @endif

            @unless($isSoldOut)
                <a href="{{ $productUrl }}" class="product-preview-link">
                    {!! $detailIcon !!}
                    <span>Ver detalle</span>
                </a>
            @endunless
        </div>
    </div>
</article>
