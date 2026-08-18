@php
    $isSoldOut = $product->isSoldOut();
    $productUrl = $storefrontUrls->product($store, $product);
    $productDescription = \App\Support\ProductText::plain($product->description) ?: 'Plato disponible para pedir por WhatsApp.';
    $productInitial = mb_substr($product->name, 0, 1);
@endphp

<article class="restaurant-menu-item">
    <a href="{{ $productUrl }}" class="restaurant-menu-item-image" aria-label="Ver {{ $product->name }}">
        @if($product->image)
            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
        @else
            <span>{{ $productInitial }}</span>
        @endif
    </a>

    <div class="restaurant-menu-item-copy">
        <div class="restaurant-menu-item-line">
            <h3><a href="{{ $productUrl }}">{{ $product->name }}</a></h3>
            <span></span>
            <strong>${{ number_format((float) $product->price, 0, ',', '.') }}</strong>
        </div>

        <p>{{ $productDescription }}</p>

        @if($product->hasVariants())
            <div class="restaurant-menu-tags">
                @if($product->hasSizes())
                    <span>Opciones</span>
                @endif
                @if($product->hasColors())
                    <span>Variantes</span>
                @endif
            </div>
        @endif
    </div>

    @if($isSoldOut)
        <span class="restaurant-menu-order-link is-disabled">Agotado</span>
    @elseif($product->hasVariants())
        <a href="{{ $productUrl }}" class="restaurant-menu-order-link">
            {!! $restaurantOptionIcon !!}
            <span>Ver opciones</span>
        </a>
    @else
        <form action="{{ route('cart.add', $product->id) }}" method="POST" class="restaurant-menu-order-form add-to-cart-form">
            @csrf
            <input type="hidden" name="quantity" value="1">
            <button type="submit" class="restaurant-menu-order-link">
                {!! $restaurantCartIcon !!}
                <span>Agregar</span>
            </button>
        </form>
    @endif
</article>
