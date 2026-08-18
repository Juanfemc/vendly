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
@endphp

<section class="fashion-checkout">
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
        <form id="checkoutForm" class="fashion-checkout-form" action="{{ route('cart.whatsapp', ['store' => $store->slug]) }}" method="POST">
            @csrf

            <section class="fashion-checkout-block">
                @if($checkoutFieldEnabled('email'))
                    <label class="fashion-field fashion-field--full">
                        <span>Correo electrónico{{ $checkoutFieldRequired('email') ? '' : ' (opcional)' }}</span>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="correo@ejemplo.com" {{ $checkoutRequired('email') }}>
                    </label>
                @endif

            </section>

            <section class="fashion-checkout-block">
                <h2>Datos de entrega</h2>

                @if($usesColombiaLocations)
                    <label class="fashion-field fashion-field--full">
                        <span>País / Región</span>
                        <strong>Colombia</strong>
                    </label>

                    <div class="fashion-field-row">
                        <label class="fashion-field">
                            <span>Nombre</span>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="Juan" required>
                        </label>

                        <label class="fashion-field">
                            <span>Apellidos</span>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Pérez" required>
                        </label>
                    </div>

                    <label class="fashion-field fashion-field--full">
                        <span>WhatsApp</span>
                        <input type="text" name="phone" value="{{ old('phone') }}" placeholder="300 123 4567" required>
                    </label>

                    @if($checkoutFieldEnabled('address'))
                        <label class="fashion-field fashion-field--full">
                            <span>Dirección{{ $checkoutFieldRequired('address') ? '' : ' (opcional)' }}</span>
                            <input type="text" name="address" value="{{ old('address') }}" placeholder="Calle 123 #45-67" {{ $checkoutRequired('address') }}>
                        </label>
                    @endif

                    @if($checkoutFieldEnabled('apartment'))
                        <label class="fashion-field fashion-field--full">
                            <span>Apartamento, interior, torre, etc.{{ $checkoutFieldRequired('apartment') ? '' : ' (opcional)' }}</span>
                            <input type="text" name="apartment" value="{{ old('apartment') }}" placeholder="Apto 402, Torre 2, etc." {{ $checkoutRequired('apartment') }}>
                        </label>
                    @endif

                    @if($checkoutLocationEnabled)
                    <div class="fashion-field-row fashion-field-row--three">
                        <label class="fashion-field">
                            <span>Departamento{{ $checkoutLocationRequired ? '' : ' (opcional)' }}</span>
                            <select name="department_code" {{ $checkoutLocationRequired ? 'required' : '' }} data-department-select>
                                <option value="">Selecciona un departamento</option>
                                @foreach($colombiaDepartments as $department)
                                    <option value="{{ $department->department_code }}" @selected(old('department_code') === $department->department_code)>{{ $department->department_name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="fashion-field">
                            <span>Ciudad{{ $checkoutLocationRequired ? '' : ' (opcional)' }}</span>
                            <select name="city_code" {{ $checkoutLocationRequired ? 'required' : '' }} data-city-select data-city-input disabled>
                                <option value="">Selecciona una ciudad</option>
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

                    </div>
                    @endif
                @else
                    <label class="fashion-field fashion-field--full">
                        <span>País / Región</span>
                        <strong>Colombia</strong>
                    </label>

                    <div class="fashion-field-row">
                        <label class="fashion-field">
                            <span>Nombre</span>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="Juan" required>
                        </label>

                        <label class="fashion-field">
                            <span>Apellidos</span>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Pérez" required>
                        </label>
                    </div>

                    <label class="fashion-field fashion-field--full">
                        <span>WhatsApp</span>
                        <input type="text" name="phone" value="{{ old('phone') }}" placeholder="300 123 4567" required>
                    </label>

                    @if($checkoutFieldEnabled('address'))
                        <label class="fashion-field fashion-field--full">
                            <span>Dirección{{ $checkoutFieldRequired('address') ? '' : ' (opcional)' }}</span>
                            <input type="text" name="address" value="{{ old('address') }}" placeholder="Calle 123 #45-67" {{ $checkoutRequired('address') }}>
                        </label>
                    @endif

                    @if($checkoutFieldEnabled('apartment'))
                        <label class="fashion-field fashion-field--full">
                            <span>Apartamento, interior, torre, etc.{{ $checkoutFieldRequired('apartment') ? '' : ' (opcional)' }}</span>
                            <input type="text" name="apartment" value="{{ old('apartment') }}" placeholder="Apto 402, Torre 2, etc." {{ $checkoutRequired('apartment') }}>
                        </label>
                    @endif

                    @if($checkoutLocationEnabled)
                        <div class="fashion-field-row fashion-field-row--three">
                            <label class="fashion-field">
                                <span>Ciudad{{ $checkoutLocationRequired ? '' : ' (opcional)' }}</span>
                                <input type="text" name="city" value="{{ old('city') }}" placeholder="Bogotá" {{ $checkoutLocationRequired ? 'required' : '' }} data-city-input>
                            </label>

                            <label class="fashion-field">
                                <span>Departamento</span>
                                <input type="text" name="region" value="{{ old('region') }}" placeholder="Cundinamarca">
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

            </section>

            <section class="fashion-checkout-block">
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

            <section class="fashion-checkout-block">
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

                    @if($wompiAvailable ?? false)
                        <label class="fashion-payment-option">
                            <input
                                type="radio"
                                name="checkout_payment_choice"
                                value="wompi"
                                data-payment-choice
                                data-payment-action="{{ route('cart.wompi', ['store' => $store->slug]) }}"
                                data-payment-label="Pagar con Wompi"
                            >
                            <span>Wompi</span>
                            <b>W</b>
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
                <section class="fashion-checkout-block">
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
                <button type="submit" form="checkoutForm" data-payment-submit>
                    <span>Finalizar pedido por WhatsApp</span>
                </button>
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
                    <span>Descuento <small data-discount-code-label>{{ $discount['code'] ?? '' }}</small></span>
                    <strong data-role="discount-total">- $ {{ number_format($discountAmount, 0, ',', '.') }}</strong>
                </p>
                @if($hasShippingCost)
                    <p><span>Envío</span><strong data-role="shipping-total">{{ $hasLocalDelivery && ! $hasSelectedDeliveryCity ? 'Por calcular' : ($shippingCost > 0 ? '$ ' . number_format($shippingCost, 0, ',', '.') : 'Gratis') }}</strong></p>
                @endif
                <p><span>Impuestos estimados</span><strong>$ {{ number_format($tax, 0, ',', '.') }}</strong></p>
                <p class="fashion-summary-grand">
                    <span>Total</span>
                    <small>COP</small>
                    <strong data-role="grand-total">$ {{ number_format($checkoutTotal, 0, ',', '.') }}</strong>
                </p>
            </div>

            <div class="fashion-summary-benefits">
                <article>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h11v10H3z"/><path d="M14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>
                    <div><strong>Envío gratis</strong><span>En pedidos que cumplan el mínimo de la tienda</span></div>
                </article>
                <article>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/><path d="M3 21v-5h5"/></svg>
                    <div><strong>Cambios fáciles</strong><span>Según las políticas de la tienda</span></div>
                </article>
                <article>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="6" y="10" width="12" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                    <div><strong>Pago seguro</strong><span>Compra protegida</span></div>
                </article>
            </div>
        </aside>
    </div>
</section>


