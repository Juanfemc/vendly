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
@endphp

<article class="product-card {{ $cardClass ?? '' }}">
    <div class="product-image">
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
    </div>

    <h3>{{ $product->name }}</h3>

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

    @if($stockLabel)
        <span class="product-stock-badge {{ $isSoldOut ? 'is-sold-out' : '' }}">{{ $stockLabel }}</span>
    @endif

    <div class="product-card-actions">
        @if($isSoldOut)
            <span class="product-preview-link is-disabled">Agotado</span>
        @elseif($product->hasVariants())
            <a href="{{ $productUrl }}" class="product-card-add-link">
                {{ $isRestaurantCard ? 'Elegir opciones' : 'Agregar' }}
            </a>
        @else
            <form action="{{ route('cart.add', $product->id) }}" method="POST" class="add-to-cart-form">
                @csrf
                <button type="submit">{{ $isRestaurantCard ? 'Agregar pedido' : 'Agregar' }}</button>
            </form>
        @endif

        @unless($isSoldOut)
            <a href="{{ $productUrl }}" class="product-preview-link">
                {{ $isRestaurantCard ? 'Pedir ahora' : 'Comprar ahora' }}
            </a>
        @endunless
    </div>
</article>
