@php
    if (isset($store) && (int) $product->store_id === (int) $store->id && ! $product->relationLoaded('store')) {
        $product->setRelation('store', $store);
    }

    $stockLabel = $product->stockLabel();
    $isSoldOut = $product->isSoldOut();
    $isRestaurantCard = isset($store) && $store->isRestaurant();
    $showsOfferBadge = isset($store) && $store->allowsOfferBadges() && $product->hasOfferBadge();
    $cardPriceList = $activePriceList ?? null;
    $cardPrice = app(\App\Services\PriceListService::class)->priceFor($product, $cardPriceList);
    $usesPriceListPrice = $cardPriceList && (float) $cardPrice !== (float) $product->price;
    $showsOfferPricing = ! $usesPriceListPrice && $showsOfferBadge && $product->hasOfferPricing();
    $displayBadges = $product->displayBadges($store ?? null);
    $productUrl = $storefrontUrls->product($store, $product);
    $cartIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="20" r="1.6"/><circle cx="18" cy="20" r="1.6"/><path d="M3 4h2.4l2.2 10.4a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 8H7"/></svg>';
    $detailIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="3"/></svg>';
@endphp

<article class="product-card {{ $cardClass ?? '' }}">
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
        @endif
    </a>

    <h3><a href="{{ $productUrl }}">{{ $product->name }}</a></h3>

    <div class="price-row">
        @if($showsOfferPricing)
            <span class="price-stack">
                <span class="price-before">${{ number_format((float) $product->offer_original_price, 0, ',', '.') }}</span>
                <span class="price">${{ number_format((float) $product->price, 0, ',', '.') }}</span>
            </span>
        @elseif($usesPriceListPrice)
            <span class="price-stack">
                <span class="price-before">${{ number_format((float) $product->price, 0, ',', '.') }}</span>
                <span class="price">${{ number_format((float) $cardPrice, 0, ',', '.') }}</span>
            </span>
        @else
            <span class="price">${{ number_format((float) $product->price, 0, ',', '.') }}</span>
        @endif
    </div>
    @if($usesPriceListPrice)
        <span class="store-price-list-badge">Lista: {{ $cardPriceList->name }}</span>
    @endif

    @if($stockLabel)
        <span class="product-stock-badge {{ $isSoldOut ? 'is-sold-out' : '' }}">{{ $stockLabel }}</span>
    @endif

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
                @if($cardPriceList)
                    <input type="hidden" name="lista" value="{{ $cardPriceList->access_code ?: $cardPriceList->slug }}">
                @endif
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
</article>
