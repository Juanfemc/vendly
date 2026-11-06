@extends('layouts.admin')

@section('meta_title', 'Métodos de envío | Vendly')

@section('content')
<div class="shipping-settings-page">
    <style>
        .shipping-settings-page { max-width: 1040px; margin: 0 auto; padding-bottom: 90px; }
        .shipping-settings-hero { display:flex; align-items:center; justify-content:space-between; gap:20px; margin-bottom:20px; }
        .shipping-settings-title { display:flex; align-items:center; gap:14px; }
        .shipping-settings-title h1 { margin:0; color:#172033; font-size:clamp(1.55rem, 3vw, 2rem); }
        .shipping-settings-title p { margin:6px 0 0; color:#718096; }
        .shipping-settings-back { display:grid; place-items:center; width:40px; height:40px; border:1px solid #dce3ec; border-radius:12px; color:#172033; text-decoration:none; }
        .shipping-settings-back svg { width:20px; height:20px; }
        .shipping-settings-card { padding:24px; background:#fff; border:1px solid #e4e9f0; border-radius:16px; box-shadow:0 8px 24px rgba(23,32,51,.05); }
        .shipping-settings-card h2 { margin:0; color:#172033; font-size:1.1rem; }
        .shipping-settings-card .muted { margin:6px 0 22px; color:#718096; }
        .shipping-settings-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:16px; }
        .shipping-settings-field { display:grid; gap:7px; }
        .shipping-settings-field--full { margin-top:20px; }
        .shipping-settings-field label, .shipping-settings-field > span { color:#263449; font-size:.9rem; font-weight:700; }
        .shipping-settings-field input, .shipping-settings-field select { width:100%; min-height:44px; padding:10px 12px; border:1px solid #d7e0ea; border-radius:10px; background:#fff; color:#172033; box-sizing:border-box; }
        .shipping-settings-list { display:grid; gap:10px; margin-top:10px; }
        .shipping-settings-row { display:grid; grid-template-columns:28px minmax(0, 1fr) 160px 190px; gap:10px; align-items:center; }
        .shipping-settings-row input { min-width:0; }
        .shipping-settings-number { display:grid; place-items:center; width:28px; height:28px; border-radius:50%; background:#eaf8f2; color:#008b5a; font-weight:800; }
        .shipping-settings-toggle { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-top:20px; padding-top:18px; border-top:1px solid #edf1f5; }
        .shipping-settings-toggle strong { color:#263449; }
        .shipping-settings-toggle p { margin:4px 0 0; color:#718096; font-size:.9rem; }
        .shipping-settings-toggle input { width:20px; height:20px; accent-color:#008b5a; }
        .shipping-settings-actions { display:flex; justify-content:flex-end; margin-top:22px; }
        .shipping-settings-submit { min-height:46px; padding:0 22px; border:0; border-radius:10px; background:#008b5a; color:#fff; font-weight:800; cursor:pointer; }
        .shipping-settings-help { margin-top:18px; color:#718096; font-size:.9rem; }
        @media (max-width: 760px) {
            .shipping-settings-page { padding:0 14px 90px; }
            .shipping-settings-hero { align-items:flex-start; }
            .shipping-settings-hero .plan-pill { display:none; }
            .shipping-settings-card { padding:18px; border-radius:12px; }
            .shipping-settings-grid { grid-template-columns:1fr; }
            .shipping-settings-row { grid-template-columns:28px minmax(0, 1fr); }
            .shipping-settings-row input { grid-column:2; }
            .shipping-settings-submit { width:100%; }
        }
    </style>

    <div class="shipping-settings-hero">
        <div class="shipping-settings-title">
            <a href="{{ route('admin.store-settings') }}" class="shipping-settings-back" aria-label="Volver a apariencia">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"></path><path d="m12 19-7-7 7-7"></path></svg>
            </a>
            <div>
                <h1>Métodos de envío</h1>
                <p>Configura cómo entregas los pedidos y cuánto cuesta cada opción.</p>
            </div>
        </div>
        <span class="plan-pill">{{ $store->planLabel() }}</span>
    </div>

    @if (session('success'))
        <div class="flash success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash error">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="flash error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
    @endif

    <form method="POST" action="{{ route('admin.shipping-methods.update') }}">
        @csrf
        <section class="shipping-settings-card">
            @if($store->allowsShippingMethods())
                <h2>Opciones de entrega</h2>
                <p class="muted">Estas opciones aparecerán en el checkout cuando el cliente realice su pedido.</p>

                @if(\App\Models\Store::supportsLocalDeliveryColumns())
                    <div class="shipping-settings-grid">
                        <div class="shipping-settings-field">
                            <label for="local_delivery_area">Ciudad local</label>
                            @if($colombiaLocations->isNotEmpty())
                                <select id="local_delivery_area" name="local_delivery_city_code">
                                    <option value="">Selecciona ciudad</option>
                                    @foreach($colombiaLocations->groupBy('department_name') as $departmentName => $locations)
                                        <optgroup label="{{ $departmentName }}">
                                            @foreach($locations as $location)
                                                <option value="{{ $location->city_code }}" @selected(old('local_delivery_city_code', $store->local_delivery_city_code) === $location->city_code)>{{ $location->city_name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            @else
                                <input id="local_delivery_area" type="text" name="local_delivery_area" value="{{ old('local_delivery_area', $store->local_delivery_area) }}" placeholder="Ej: Cali">
                            @endif
                        </div>
                        <div class="shipping-settings-field"><label for="local_delivery_cost">Precio local</label><input id="local_delivery_cost" type="number" name="local_delivery_cost" value="{{ old('local_delivery_cost', $store->local_delivery_cost) }}" min="0" step="1" placeholder="5000"></div>
                        <div class="shipping-settings-field"><label for="outside_delivery_cost">Precio fuera de ciudad</label><input id="outside_delivery_cost" type="number" name="outside_delivery_cost" value="{{ old('outside_delivery_cost', $store->outside_delivery_cost) }}" min="0" step="1" placeholder="10000"></div>
                    </div>
                @endif

                <div class="shipping-settings-field shipping-settings-field--full">
                    <span>Métodos manuales</span>
                    <div class="shipping-settings-list">
                        @for($shippingIndex = 0; $shippingIndex < 5; $shippingIndex++)
                            @php($shippingMethod = $shippingMethods[$shippingIndex] ?? [])
                            <div class="shipping-settings-row">
                                <span class="shipping-settings-number">{{ $shippingIndex + 1 }}</span>
                                <input type="text" name="shipping_methods[{{ $shippingIndex }}][name]" value="{{ old('shipping_methods.' . $shippingIndex . '.name', $shippingMethod['name'] ?? '') }}" maxlength="80" placeholder="{{ ['Domicilio local', 'Envío nacional', 'Recoger en tienda', 'Mensajería express', 'Contra entrega'][$shippingIndex] }}" aria-label="Nombre del método {{ $shippingIndex + 1 }}">
                                <input type="number" name="shipping_methods[{{ $shippingIndex }}][cost]" value="{{ old('shipping_methods.' . $shippingIndex . '.cost', $shippingMethod['cost'] ?? '') }}" min="0" step="1" placeholder="Costo" aria-label="Costo del método {{ $shippingIndex + 1 }}">
                                @if($store->isFashionStore() && \App\Models\Store::supportsFashionDeliveryTimesColumn())
                                    <input type="text" name="shipping_methods[{{ $shippingIndex }}][delivery_time]" value="{{ old('shipping_methods.' . $shippingIndex . '.delivery_time', $shippingMethod['delivery_time'] ?? '') }}" maxlength="80" placeholder="Tiempo de entrega" aria-label="Tiempo de entrega {{ $shippingIndex + 1 }}">
                                @else
                                    <span></span>
                                @endif
                            </div>
                        @endfor
                    </div>
                </div>

                @if($store->isFashionStore() && \App\Models\Store::supportsFashionDeliveryTimesColumn())
                    <div class="shipping-settings-toggle">
                        <div><strong>Mostrar tiempos de entrega en ropa</strong><p>Ejemplo: “1-3 días hábiles”.</p></div>
                        <input type="hidden" name="show_fashion_delivery_times" value="0">
                        <input type="checkbox" name="show_fashion_delivery_times" value="1" @checked($showFashionDeliveryTimes) aria-label="Mostrar tiempos de entrega">
                    </div>
                @endif

                @if(\App\Models\Store::supportsCheckoutShippingOptionsCardColumn())
                    <div class="shipping-settings-toggle">
                        <div><strong>Mostrar opciones de envío al inicio del checkout</strong><p>El cliente verá una tarjeta con las opciones disponibles antes de llenar sus datos.</p></div>
                        <input type="hidden" name="show_shipping_options_at_checkout_start" value="0">
                        <input type="checkbox" name="show_shipping_options_at_checkout_start" value="1" @checked((bool) old('show_shipping_options_at_checkout_start', $store->show_shipping_options_at_checkout_start ?? false)) aria-label="Mostrar opciones de envío al inicio">
                    </div>
                @endif

                <div class="shipping-settings-actions"><button class="shipping-settings-submit" type="submit">Guardar métodos de envío</button></div>
            @else
                <h2>Métodos de envío</h2>
                <p class="shipping-settings-help">Actualiza a Pro o Premium para ofrecer costos de envío y opciones como domicilio local, envío nacional o recogida en tienda.</p>
            @endif
        </section>
    </form>
</div>
@endsection
