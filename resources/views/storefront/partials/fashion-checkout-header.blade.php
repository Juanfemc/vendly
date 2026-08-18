@php
    $storefrontUrls = $storefrontUrls ?? app(\App\Services\StorefrontUrlService::class);
    $storeHomeUrl = $storeHomeUrl ?? $storefrontUrls->publicHome($store);
    $cartCount = $cartCount ?? 0;
    $fashionBrandWords = preg_split('/\s+/', trim((string) $store->name)) ?: [];
    $fashionBrandInitials = collect($fashionBrandWords)
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_substr($word, 0, 1))
        ->implode('');
    $fashionBrandInitials = mb_strtoupper($fashionBrandInitials !== '' ? $fashionBrandInitials : 'T');
@endphp

<header class="fashion-checkout-header" aria-label="Checkout">
    <div class="fashion-checkout-header-shell">
        <a class="fashion-checkout-back" href="{{ $storeHomeUrl }}">
            <span aria-hidden="true">&larr;</span>
            <span>Volver a la tienda</span>
        </a>

        <a class="fashion-checkout-logo" href="{{ $storeHomeUrl }}" aria-label="{{ $store->name }}">
            @if($store->logo_image)
                <img src="{{ asset('storage/' . $store->logo_image) }}" alt="{{ $store->name }}" loading="eager" decoding="async">
            @else
                <span>{{ $fashionBrandInitials }}</span>
            @endif
        </a>

    </div>
</header>
