@php
    $cartBackdropClass = $cartBackdropClass ?? 'store-cart-backdrop';
    $cartDrawerClass = $cartDrawerClass ?? 'store-cart-drawer';
    $cartShowTax = $cartShowTax ?? false;
    $cartShowSecure = $cartShowSecure ?? true;
    $cartHideItemMeta = $cartHideItemMeta ?? false;
    $drawerCart = app(\App\Services\CartService::class)->cartForStore($store);
    $drawerSubtotal = collect($drawerCart)->sum(fn ($item) => (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 1));
    $drawerShipping = 0;
    $drawerTax = 0;
    $drawerTotal = $drawerSubtotal + $drawerShipping;
@endphp

<label class="{{ $cartBackdropClass }}" for="minimalShopCartToggle" aria-hidden="true"></label>

<aside
    class="{{ $cartDrawerClass }}{{ $cartCount < 1 ? ' is-empty' : '' }}"
    aria-label="{{ $cartLabel }}"
    data-cart-drawer
    data-cart-subtotal="{{ $drawerSubtotal }}"
    data-cart-shipping="{{ $drawerShipping }}"
    data-cart-tax="{{ $drawerTax }}"
    data-cart-hide-item-meta="{{ $cartHideItemMeta ? '1' : '0' }}"
    data-store-url="{{ $storefrontUrls->home($store) }}"
>
    <div class="minimal-shop-cart-head">
        <h2>Tu carrito (<span data-cart-drawer-count>{{ $cartCount }}</span>)</h2>
        <label for="minimalShopCartToggle" aria-label="Cerrar carrito">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M18 6 6 18"></path>
                <path d="m6 6 12 12"></path>
            </svg>
        </label>
    </div>

    <div class="minimal-shop-cart-items" data-cart-drawer-items>
        @forelse($drawerCart as $cartKey => $item)
            @php
                $drawerItemImage = trim((string) ($item['image'] ?? ''));
                $drawerItemPrice = (float) ($item['price'] ?? 0);
                $drawerItemQuantity = (int) ($item['quantity'] ?? 1);
                $drawerVariant = trim(collect([$item['color'] ?? null, $item['size'] ?? null])->filter()->implode(' / '));
            @endphp
            <article class="minimal-shop-cart-item" data-cart-drawer-item data-cart-key="{{ $cartKey }}">
                <div class="minimal-shop-cart-thumb">
                    @if($drawerItemImage !== '')
                        <img src="{{ asset('storage/' . $drawerItemImage) }}" alt="{{ $item['name'] ?? 'Producto' }}">
                    @else
                        <span>{{ strtoupper(substr((string) ($item['name'] ?? 'P'), 0, 1)) }}</span>
                    @endif
                </div>
                <div class="minimal-shop-cart-info">
                    <strong>{{ $item['name'] ?? 'Producto' }}</strong>
                    @unless($cartHideItemMeta)
                        <small>{{ $drawerVariant !== '' ? $drawerVariant : 'Sin variante' }}</small>
                    @endunless
                    <b data-cart-item-total>${{ number_format($drawerItemPrice * $drawerItemQuantity, 0, ',', '.') }}</b>
                </div>
                <div class="minimal-shop-cart-controls">
                    <button type="button" data-cart-drawer-minus aria-label="Restar">&minus;</button>
                    <span data-cart-drawer-quantity>{{ $drawerItemQuantity }}</span>
                    <button type="button" data-cart-drawer-plus aria-label="Sumar">+</button>
                    <button type="button" data-cart-drawer-remove aria-label="Eliminar">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 7h16"></path>
                            <path d="M10 11v6M14 11v6"></path>
                            <path d="M6 7l1 14h10l1-14"></path>
                            <path d="M9 7V4h6v3"></path>
                        </svg>
                    </button>
                </div>
            </article>
        @empty
            <div class="minimal-shop-cart-empty" data-cart-drawer-empty>
                <strong>Tu carrito está vacío</strong>
                <a href="{{ $storefrontUrls->home($store) }}">Volver a la tienda</a>
            </div>
        @endforelse
    </div>

    <div class="minimal-shop-cart-summary">
        <p><span>Subtotal</span><strong data-cart-drawer-subtotal>${{ number_format($drawerSubtotal, 0, ',', '.') }}</strong></p>
        <p><span>Envío</span><strong data-cart-drawer-shipping>Por calcular</strong></p>
        @if($cartShowTax)
            <p><span>Impuestos</span><strong data-cart-drawer-tax>$0</strong></p>
        @endif
        <p class="minimal-shop-cart-total"><span>Total</span>@if($cartShowTax)<small>COP</small>@endif<strong data-cart-drawer-total>${{ number_format($drawerTotal, 0, ',', '.') }}</strong></p>
    </div>

    <div class="minimal-shop-cart-actions">
        <a href="{{ route('cart.index', ['store' => $store->slug]) }}">Ver carrito</a>
        <a href="{{ route('cart.index', ['store' => $store->slug]) }}">Finalizar compra</a>
    </div>

    @if($cartShowSecure)
        <p class="minimal-shop-cart-secure">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"></path>
            </svg>
            Compra segura
        </p>
    @endif
</aside>
