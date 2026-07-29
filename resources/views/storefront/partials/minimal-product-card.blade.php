@php
    if (isset($store) && (int) $product->store_id === (int) $store->id && ! $product->relationLoaded('store')) {
        $product->setRelation('store', $store);
    }

    $isSoldOut = $product->isSoldOut();
    $productCategory = trim((string) $product->category) !== '' ? $product->category : 'Otros';
    $placeholderText = strtoupper(substr($product->name, 0, 2));
    $showsOfferBadge = isset($store) && $store->allowsOfferBadges() && $product->hasOfferBadge();
    $showsOfferPricing = $showsOfferBadge && $product->hasOfferPricing();
    $displayBadges = $product->displayBadges($store ?? null);
    $reviewsEnabled = isset($store) && $store->allowsProductReviews();
    $reviewCount = $reviewsEnabled ? $product->reviewCount() : 0;
    $reviewAverage = $reviewsEnabled ? $product->reviewAverage() : null;
    $reviewLabel = $reviewCount > 0
        ? number_format($reviewAverage, 1) . ' (' . $reviewCount . ' ' . \Illuminate\Support\Str::plural('resena', $reviewCount) . ')'
        : null;
    $cartIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="20" r="1.6"/><circle cx="18" cy="20" r="1.6"/><path d="M3 4h2.4l2.2 10.4a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 8H7"/></svg>';
    $detailIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="3"/></svg>';
@endphp

<article class="minimal-shop-product-card {{ ($isRecommendation ?? false) ? 'minimal-shop-product-card--recommendation' : '' }}">
    <a href="{{ $storefrontUrls->product($store, $product) }}" class="minimal-shop-card-media" aria-label="{{ $product->name }}">
        @if($displayBadges !== [])
            <div class="minimal-shop-badges">
                @foreach($displayBadges as $badge)
                    <span class="minimal-shop-offer-badge">{{ $badge }}</span>
                @endforeach
            </div>
        @endif
        <span class="minimal-shop-card-badge">{{ $productCategory }}</span>
        @if($product->image)
            <img
                class="minimal-shop-card-image"
                src="{{ asset('storage/' . $product->image) }}"
                alt="{{ $product->name }}"
                loading="lazy"
                decoding="async"
            >
            <span class="minimal-shop-card-placeholder" hidden>{{ $placeholderText }}</span>
        @else
            <span class="minimal-shop-card-placeholder">{{ $placeholderText }}</span>
        @endif
    </a>

    <div class="minimal-shop-card-info">
        <h3>{{ $product->name }}</h3>
        <div class="minimal-shop-card-meta">
            @if($reviewsEnabled && $reviewCount > 0)
                <span class="minimal-shop-rating" aria-label="{{ $reviewLabel }}">&#9733; {{ $reviewLabel }}</span>
            @endif
            <span class="minimal-shop-price-stack">
                @if($showsOfferPricing)
                    <span class="minimal-shop-price-before">${{ number_format((float) $product->offer_original_price, 2, '.', ',') }}</span>
                @endif
                <strong>${{ number_format((float) $product->price, 2, '.', ',') }}</strong>
            </span>
        </div>
    </div>

    <div class="minimal-shop-card-actions">
        @if($isSoldOut)
            <span class="minimal-shop-card-button minimal-shop-card-button--disabled">Agotado</span>
        @elseif($product->hasVariants())
            <a href="{{ $storefrontUrls->product($store, $product) }}" class="minimal-shop-card-button">
                {!! $cartIcon !!}
                <span>Agregar al carrito</span>
            </a>
        @else
            <form action="{{ route('cart.add', $product->id) }}" method="POST" class="add-to-cart-form">
                @csrf
                <button type="submit">
                    {!! $cartIcon !!}
                    <span>Agregar al carrito</span>
                </button>
            </form>
        @endif
        <a href="{{ $storefrontUrls->product($store, $product) }}">
            {!! $detailIcon !!}
            <span>Ver detalle</span>
        </a>
    </div>
</article>
