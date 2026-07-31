@php
    $subtotal = (float) $total;
    $tax = 0;
    $cartItems = collect($cart);
    $discountAmount = (float) ($discount['amount'] ?? 0);
    $checkoutTotal = max(0, $subtotal + (float) $shippingCost - $discountAmount);
    $checkoutFieldEnabled = $checkoutFieldEnabled ?? fn (string $field): bool => $store?->checkoutFieldEnabled($field) ?? true;
    $checkoutFieldRequired = $checkoutFieldRequired ?? fn (string $field): bool => $store?->checkoutFieldRequired($field) ?? false;
    $checkoutRequired = $checkoutRequired ?? fn (string $field): string => $checkoutFieldRequired($field) ? 'required' : '';
    $checkoutLocationEnabled = $checkoutLocationEnabled ?? $checkoutFieldEnabled('city');
    $checkoutLocationRequired = $checkoutLocationRequired ?? $checkoutFieldRequired('city');
    $storeHomeUrl = app(\App\Services\StorefrontUrlService::class)->publicHome($store);
@endphp

<section class="fashion-checkout">
    <nav class="fashion-checkout-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ $storeHomeUrl }}">Home</a>
        <span aria-hidden="true">&rsaquo;</span>
        <span>Checkout</span>
    </nav>

    <ol class="fashion-checkout-steps" aria-label="Checkout steps">
        <li class="is-active"><span>1</span><strong>Shipping</strong></li>
        <li><span>2</span><strong>Payment</strong></li>
        <li><span>3</span><strong>Review</strong></li>
        <li><span>4</span><strong>Confirmation</strong></li>
    </ol>

    @if (session('error'))
        <div class="flash-error">{{ session('error') }}</div>
    @endif

    @if (session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="errors">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="fashion-checkout-grid">
        <form class="fashion-checkout-form" action="{{ route('cart.whatsapp', ['store' => $store->slug]) }}" method="POST">
            @csrf

            <section class="fashion-checkout-section">
                <div class="fashion-checkout-section-head">
                    <h1>Contact Information</h1>
                    <p>Already have an account? <a href="{{ route('login') }}">Log in</a></p>
                </div>

                @if($checkoutFieldEnabled('email'))
                    <label class="fashion-field fashion-field--full">
                        <span>Email address{{ $checkoutFieldRequired('email') ? '' : ' (optional)' }}</span>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" {{ $checkoutRequired('email') }}>
                    </label>
                @endif

                <label class="fashion-checkbox">
                    <input type="checkbox" name="accepts_marketing" value="1" checked>
                    <span>Email me with news and offers</span>
                </label>
            </section>

            <section class="fashion-checkout-section">
                <h2>Shipping Address</h2>

                @if($usesColombiaLocations)
                    <label class="fashion-field fashion-field--full">
                        <span>Country / Region</span>
                        <strong>Colombia</strong>
                    </label>

                    <div class="fashion-field-row">
                        <label class="fashion-field">
                            <span>First name</span>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="John" required>
                        </label>

                        <label class="fashion-field">
                            <span>Last name</span>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Doe" required>
                        </label>
                    </div>

                    <label class="fashion-field fashion-field--full">
                        <span>Phone</span>
                        <input type="text" name="phone" value="{{ old('phone') }}" placeholder="(555) 123-4567" required>
                    </label>

                    @if($checkoutFieldEnabled('address'))
                        <label class="fashion-field fashion-field--full">
                            <span>Address{{ $checkoutFieldRequired('address') ? '' : ' (optional)' }}</span>
                            <input type="text" name="address" value="{{ old('address') }}" placeholder="123 Main Street" {{ $checkoutRequired('address') }}>
                        </label>
                    @endif

                    @if($checkoutFieldEnabled('apartment'))
                        <label class="fashion-field fashion-field--full">
                            <span>Apartment, suite, etc.{{ $checkoutFieldRequired('apartment') ? '' : ' (optional)' }}</span>
                            <input type="text" name="apartment" value="{{ old('apartment') }}" placeholder="Apt 4B, Floor 2, etc." {{ $checkoutRequired('apartment') }}>
                        </label>
                    @endif

                    @if($checkoutLocationEnabled)
                    <div class="fashion-field-row fashion-field-row--three">
                        <label class="fashion-field">
                            <span>Department{{ $checkoutLocationRequired ? '' : ' (optional)' }}</span>
                            <select name="department_code" {{ $checkoutLocationRequired ? 'required' : '' }} data-department-select>
                                <option value="">Select department</option>
                                @foreach($colombiaDepartments as $department)
                                    <option value="{{ $department->department_code }}" @selected(old('department_code') === $department->department_code)>{{ $department->department_name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="fashion-field">
                            <span>City{{ $checkoutLocationRequired ? '' : ' (optional)' }}</span>
                            <select name="city_code" {{ $checkoutLocationRequired ? 'required' : '' }} data-city-select data-city-input disabled>
                                <option value="">Select city</option>
                                @foreach($colombiaLocations as $location)
                                    <option
                                        value="{{ $location->city_code }}"
                                        data-department="{{ $location->department_code }}"
                                        data-city-name="{{ $location->city_name }}"
                                        @selected(old('city_code') === $location->city_code)
                                    >{{ $location->city_name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="fashion-field">
                            <span>ZIP Code</span>
                            <input type="text" name="postal_code" value="{{ old('postal_code') }}" placeholder="000000">
                        </label>
                    </div>
                    @endif
                @else
                    <label class="fashion-field fashion-field--full">
                        <span>Country / Region</span>
                        <strong>Colombia</strong>
                    </label>

                    <div class="fashion-field-row">
                        <label class="fashion-field">
                            <span>First name</span>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="John" required>
                        </label>

                        <label class="fashion-field">
                            <span>Last name</span>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Doe" required>
                        </label>
                    </div>

                    <label class="fashion-field fashion-field--full">
                        <span>Phone</span>
                        <input type="text" name="phone" value="{{ old('phone') }}" placeholder="(555) 123-4567" required>
                    </label>

                    @if($checkoutFieldEnabled('address'))
                        <label class="fashion-field fashion-field--full">
                            <span>Address{{ $checkoutFieldRequired('address') ? '' : ' (optional)' }}</span>
                            <input type="text" name="address" value="{{ old('address') }}" placeholder="123 Main Street" {{ $checkoutRequired('address') }}>
                        </label>
                    @endif

                    @if($checkoutFieldEnabled('apartment'))
                        <label class="fashion-field fashion-field--full">
                            <span>Apartment, suite, etc.{{ $checkoutFieldRequired('apartment') ? '' : ' (optional)' }}</span>
                            <input type="text" name="apartment" value="{{ old('apartment') }}" placeholder="Apt 4B, Floor 2, etc." {{ $checkoutRequired('apartment') }}>
                        </label>
                    @endif

                    @if($checkoutLocationEnabled)
                        <div class="fashion-field-row fashion-field-row--three">
                            <label class="fashion-field">
                                <span>City{{ $checkoutLocationRequired ? '' : ' (optional)' }}</span>
                                <input type="text" name="city" value="{{ old('city') }}" placeholder="New York" {{ $checkoutLocationRequired ? 'required' : '' }} data-city-input>
                            </label>

                            <label class="fashion-field">
                                <span>State</span>
                                <input type="text" name="region" value="{{ old('region') }}" placeholder="State">
                            </label>

                            <label class="fashion-field">
                                <span>ZIP Code</span>
                                <input type="text" name="postal_code" value="{{ old('postal_code') }}" placeholder="10001">
                            </label>
                        </div>
                    @endif
                @endif

                @if($checkoutFieldEnabled('neighborhood'))
                    <label class="fashion-field fashion-field--full">
                        <span>Barrio{{ $checkoutFieldRequired('neighborhood') ? '' : ' (opcional)' }}</span>
                        <input type="text" name="neighborhood" value="{{ old('neighborhood') }}" placeholder="Barrio" {{ $checkoutRequired('neighborhood') }}>
                    </label>
                @endif

                @if($checkoutFieldEnabled('document'))
                    <label class="fashion-field fashion-field--full">
                        <span>Documento{{ $checkoutFieldRequired('document') ? '' : ' (opcional)' }}</span>
                        <input type="text" name="document" value="{{ old('document') }}" placeholder="Cédula" {{ $checkoutRequired('document') }}>
                    </label>
                @endif

                <label class="fashion-checkbox">
                    <input type="checkbox" name="save_information" value="1" checked>
                    <span>Guardar esta información para la próxima vez</span>
                </label>
            </section>

            <section class="fashion-checkout-section">
                <h2>Método de envío</h2>

                @if($hasLocalDelivery)
                    <div class="fashion-delivery-preview" data-local-delivery-preview>
                        <span data-local-delivery-label>Envío por ciudad</span>
                        <strong data-local-delivery-price>Por calcular</strong>
                    </div>
                @elseif($shippingMethods->isNotEmpty())
                    <fieldset class="fashion-shipping-options">
                        @foreach($shippingMethods as $method)
                            <label class="fashion-shipping-option">
                                <input
                                    type="radio"
                                    name="shipping_method"
                                    value="{{ $method['key'] }}"
                                    data-shipping-option
                                    data-shipping-cost="{{ $method['cost'] }}"
                                    @checked((string) $selectedShippingKey === (string) $method['key'])
                                    required
                                >
                                <span aria-hidden="true"></span>
                                <strong>{{ $method['name'] }}</strong>
                                <em>{{ ((float) $method['cost']) > 0 ? '1-3 días hábiles' : '3-5 días hábiles' }}</em>
                                <b data-shipping-price>{{ ((float) $method['checkout_cost']) > 0 ? '$ ' . number_format((float) $method['checkout_cost'], 0, ',', '.') : 'Gratis' }}</b>
                            </label>
                        @endforeach
                    </fieldset>
                @else
                    <p class="fashion-checkout-muted">El vendedor coordinará el envío por WhatsApp.</p>
                @endif
            </section>

            <section class="fashion-checkout-section">
                <h2>Método de pago</h2>
                <p class="fashion-checkout-muted">Todas las transacciones son seguras.</p>

                <div class="fashion-payment-options">
                    <label class="fashion-payment-option">
                        <input
                            type="radio"
                            name="checkout_payment_choice"
                            value="whatsapp"
                            data-payment-choice
                            data-payment-action="{{ route('cart.whatsapp', ['store' => $store->slug]) }}"
                                        data-payment-label="Finalizar pedido por WhatsApp"
                            checked
                        >
                        <span>Pedido por WhatsApp</span>
                        <b>WA</b>
                    </label>

                    @if($mercadoPagoAvailable)
                        <label class="fashion-payment-option">
                            <input
                                type="radio"
                                name="checkout_payment_choice"
                                value="mercadopago"
                                data-payment-choice
                                data-payment-action="{{ route('cart.mercadopago', ['store' => $store->slug]) }}"
                                data-payment-label="Pagar con Mercado Pago"
                            >
                            <span>Mercado Pago</span>
                            <b>MP</b>
                        </label>
                    @endif
                </div>
            </section>

            @if($checkoutFieldEnabled('notes'))
                <label class="fashion-field fashion-field--full">
                    <span>Notas del pedido{{ $checkoutFieldRequired('notes') ? '' : ' (opcional)' }}</span>
                    <textarea name="notes" placeholder="Agrega notas de entrega o detalles del producto" {{ $checkoutRequired('notes') }}>{{ old('notes') }}</textarea>
                </label>
            @endif

            @if($store?->allowsDiscountCoupons())
                <section class="fashion-checkout-section">
                    <h2>Cupón de descuento</h2>
                    <div class="checkout-coupon">
                        <div class="checkout-coupon-row">
                            <input type="text" name="discount_code" value="{{ old('discount_code', $discount['code'] ?? '') }}" placeholder="Ingresa el código" data-discount-code>
                            <button type="button" data-discount-apply>Aplicar</button>
                        </div>
                        <p class="checkout-coupon-message" data-discount-message>{{ $discountAmount > 0 ? 'Descuento aplicado.' : '' }}</p>
                    </div>
                </section>
            @endif

            @include('storefront.partials.checkout-terms', ['store' => $store, 'mode' => 'fashion'])

            <div class="fashion-checkout-actions">
                <a href="{{ route('cart.index', ['store' => $store->slug]) }}">&lsaquo; Volver al carrito</a>
                <button type="submit" data-payment-submit>Finalizar pedido</button>
            </div>
        </form>

        <aside class="fashion-checkout-summary">
            <div class="fashion-checkout-summary-head">
                <h2>Resumen del pedido ({{ $cartCount }})</h2>
                <a href="{{ route('cart.index', ['store' => $store->slug]) }}">Editar carrito</a>
            </div>

            <div class="fashion-summary-items">
                @foreach ($cartItems as $productId => $item)
                    <article class="fashion-summary-item" data-cart-item="{{ $productId }}">
                        <div class="fashion-summary-media">
                            @if (!empty($item['image']))
                                <img src="{{ asset('storage/' . $item['image']) }}" alt="{{ $item['name'] }}">
                            @else
                                <span>{{ substr($item['name'], 0, 1) }}</span>
                            @endif
                            <b data-role="quantity-badge">{{ $item['quantity'] }}</b>
                        </div>

                        <div>
                            <h3>{{ $item['name'] }}</h3>
                            @if (!empty($item['color']) || !empty($item['size']))
                                <p>
                                    {{ $item['color'] ?? 'Navy' }}
                                    @if (!empty($item['size']))
                                        / {{ $item['size'] }}
                                    @endif
                                </p>
                            @else
                                <p>{{ $store->name }}</p>
                            @endif
                            <div class="fashion-summary-qty">
                                <button type="button" data-action="decrease" data-product-id="{{ $productId }}">-</button>
                                <span data-role="quantity">{{ $item['quantity'] }}</span>
                                <button type="button" data-action="increase" data-product-id="{{ $productId }}">+</button>
                                <button type="button" data-action="remove" data-product-id="{{ $productId }}" aria-label="Eliminar producto">&times;</button>
                            </div>
                        </div>

                        <strong data-role="item-total">$ {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}</strong>
                    </article>
                @endforeach
            </div>

            <div class="fashion-summary-totals">
                <p><span>Subtotal</span><strong data-role="total">$ {{ number_format($subtotal, 0, ',', '.') }}</strong></p>
                <p class="{{ $discountAmount > 0 ? '' : 'is-hidden' }}" data-discount-row>
                    <span>Discount <small data-discount-code-label>{{ $discount['code'] ?? '' }}</small></span>
                    <strong data-role="discount-total">- $ {{ number_format($discountAmount, 0, ',', '.') }}</strong>
                </p>
                @if($hasShippingCost)
                    <p><span>Shipping</span><strong data-role="shipping-total">{{ $hasLocalDelivery && ! $hasSelectedDeliveryCity ? 'Por calcular' : ($shippingCost > 0 ? '$ ' . number_format($shippingCost, 0, ',', '.') : 'Free') }}</strong></p>
                @endif
                <p><span>Estimated Tax</span><strong>$ {{ number_format($tax, 0, ',', '.') }}</strong></p>
                <p class="fashion-summary-grand">
                    <span>Total</span>
                    <small>COP</small>
                    <strong data-role="grand-total">$ {{ number_format($checkoutTotal, 0, ',', '.') }}</strong>
                </p>
            </div>

            <div class="fashion-summary-benefits">
                <article>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h11v10H3z"/><path d="M14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>
                    <div><strong>Free Shipping</strong><span>On all orders over $100</span></div>
                </article>
                <article>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/><path d="M3 21v-5h5"/></svg>
                    <div><strong>Easy Returns</strong><span>30-day return policy</span></div>
                </article>
                <article>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="6" y="10" width="12" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                    <div><strong>Secure Payment</strong><span>100% secure checkout</span></div>
                </article>
            </div>
        </aside>
    </div>
</section>


