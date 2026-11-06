@extends('layouts.admin')

@section('meta_title', 'Configuración | Vendly')

@section('content')
<div class="configuration-page">
    <style>
        .configuration-page { max-width:1040px; margin:0 auto; padding-bottom:90px; }
        .configuration-hero { display:flex; align-items:center; justify-content:space-between; gap:20px; margin-bottom:20px; }
        .configuration-title { display:flex; align-items:center; gap:14px; }
        .configuration-title h1 { margin:0; color:#172033; font-size:clamp(1.55rem, 3vw, 2rem); }
        .configuration-title p { margin:6px 0 0; color:#718096; }
        .configuration-back { display:grid; place-items:center; width:40px; height:40px; border:1px solid #dce3ec; border-radius:12px; color:#172033; text-decoration:none; }
        .configuration-back svg { width:20px; height:20px; }
        .configuration-card { margin-bottom:18px; padding:24px; background:#fff; border:1px solid #e4e9f0; border-radius:16px; box-shadow:0 8px 24px rgba(23,32,51,.05); }
        .configuration-card h2 { margin:0; color:#172033; font-size:1.1rem; }
        .configuration-card > p { margin:6px 0 20px; color:#718096; }
        .configuration-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:16px; }
        .configuration-field { display:grid; gap:7px; }
        .configuration-field--full { grid-column:1/-1; }
        .configuration-field label { color:#263449; font-size:.9rem; font-weight:700; }
        .configuration-field input, .configuration-field textarea { width:100%; min-height:44px; padding:10px 12px; border:1px solid #d7e0ea; border-radius:10px; box-sizing:border-box; color:#172033; font:inherit; }
        .configuration-field textarea { min-height:130px; resize:vertical; }
        .configuration-field small, .configuration-help { color:#718096; font-size:.88rem; }
        .configuration-url { display:flex; align-items:center; gap:10px; margin-bottom:20px; padding:12px 14px; border:1px solid #e4e9f0; border-radius:10px; color:#263449; overflow-wrap:anywhere; }
        .configuration-url strong { flex:1; }
        .configuration-status { display:inline-flex; padding:4px 8px; border-radius:999px; background:#eaf8f2; color:#008b5a; font-size:.78rem; font-weight:800; }
        .configuration-status.is-pending { background:#fff5e8; color:#b86600; }
        .configuration-checkout-list { display:grid; gap:10px; }
        .configuration-checkout-item { display:grid; grid-template-columns:minmax(0,1fr) auto auto; gap:18px; align-items:center; padding:14px 0; border-bottom:1px solid #edf1f5; }
        .configuration-checkout-item:last-child { border-bottom:0; }
        .configuration-checkout-item strong, .configuration-checkout-item small { display:block; }
        .configuration-checkout-item small { margin-top:4px; color:#718096; }
        .configuration-checkout-toggle { display:flex; align-items:center; gap:7px; color:#263449; font-size:.9rem; white-space:nowrap; }
        .configuration-checkout-toggle input { width:18px; height:18px; accent-color:#008b5a; }
        .configuration-actions { display:flex; justify-content:flex-end; margin-top:20px; }
        .configuration-submit { min-height:46px; padding:0 22px; border:0; border-radius:10px; background:#008b5a; color:#fff; font-weight:800; cursor:pointer; }
        @media (max-width:760px) {
            .configuration-page { padding:0 14px 90px; }
            .configuration-hero { align-items:flex-start; }
            .configuration-card { padding:18px; border-radius:12px; }
            .configuration-grid { grid-template-columns:1fr; }
            .configuration-field--full { grid-column:auto; }
            .configuration-checkout-item { grid-template-columns:1fr auto; gap:10px; }
            .configuration-checkout-toggle:last-child { grid-column:2; }
            .configuration-submit { width:100%; }
        }
    </style>

    <div class="configuration-hero">
        <div class="configuration-title">
            <a href="/dashboard" class="configuration-back" aria-label="Volver al panel">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"></path><path d="m12 19-7-7 7-7"></path></svg>
            </a>
            <div>
                <h1>Configuración</h1>
                <p>Administra el acceso a tu tienda y la información que solicitas en el checkout.</p>
            </div>
        </div>
        <span class="plan-pill">{{ $store->planLabel() }}</span>
    </div>

    @if (session('success'))<div class="flash success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="flash error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <form method="POST" action="{{ route('admin.configuration.update') }}">
        @csrf
        <section class="configuration-card" id="connect-domain">
            <h2>Conectar dominio</h2>
            <p>Usa el enlace de Vendly o conecta un dominio propio para compartir tu tienda.</p>
            <div class="configuration-url"><strong>{{ $storeUrl }}</strong><span class="configuration-status">Activo</span></div>
            <div class="configuration-grid">
                @if($store->allowsSubdomain())
                    <div class="configuration-field">
                        <label for="subdomain">Subdominio Vendly</label>
                        <input id="subdomain" type="text" name="subdomain" value="{{ old('subdomain', $store->subdomain) }}" placeholder="mitienda">
                        <small>Quedará como {{ old('subdomain', $store->subdomain) ?: 'mitienda' }}.{{ $storefrontHost }}.</small>
                    </div>
                @else
                    <div class="configuration-field"><label>Subdominio Vendly</label><input value="Disponible desde Pro" readonly><small>Actualiza tu plan para personalizar este enlace.</small></div>
                @endif
                @if($store->allowsCustomDomain())
                    <div class="configuration-field">
                        <label for="custom_domain">Dominio personalizado</label>
                        <input id="custom_domain" type="text" name="custom_domain" value="{{ old('custom_domain', $store->custom_domain) }}" placeholder="www.tudominio.com">
                        <small>Apunta el registro DNS del dominio a tu servidor. Estado: <span class="configuration-status {{ ($store->custom_domain_status ?? 'pending') === 'verified' ? '' : 'is-pending' }}">{{ ($store->custom_domain_status ?? 'pending') === 'verified' ? 'Verificado' : 'Pendiente' }}</span></small>
                    </div>
                @else
                    <div class="configuration-field"><label>Dominio personalizado</label><input value="Disponible desde Premium" readonly><small>Conecta un dominio propio con el plan Premium.</small></div>
                @endif
            </div>
        </section>

        @if($checkoutFieldDefinitions !== [])
            <section class="configuration-card" id="checkout-fields">
                <h2>Campos del checkout</h2>
                <p>Elige qué información adicional solicitas al recibir un pedido.</p>
                <div class="configuration-checkout-list">
                    @foreach($checkoutFieldDefinitions as $fieldKey => $fieldDefinition)
                        @php
                            $fieldState = old('checkout_fields.' . $fieldKey, $store->checkoutField($fieldKey));
                            $fieldEnabled = (bool) ($fieldState['enabled'] ?? false);
                            $fieldRequired = $fieldEnabled && (bool) ($fieldState['required'] ?? false);
                            $forcedByShipping = $fieldKey === 'city' && $store->localDeliveryEnabled();
                        @endphp
                        <div class="configuration-checkout-item">
                            <div><strong>{{ $fieldDefinition['label'] }}</strong><small>{{ $forcedByShipping ? 'Obligatorio porque tienes envío local/fuera de ciudad activo.' : $fieldDefinition['description'] }}</small></div>
                            <label class="configuration-checkout-toggle"><input type="hidden" name="checkout_fields[{{ $fieldKey }}][enabled]" value="{{ $forcedByShipping ? '1' : '0' }}"><input type="checkbox" name="checkout_fields[{{ $fieldKey }}][enabled]" value="1" @checked($fieldEnabled || $forcedByShipping) @disabled($forcedByShipping)>Mostrar</label>
                            <label class="configuration-checkout-toggle"><input type="hidden" name="checkout_fields[{{ $fieldKey }}][required]" value="{{ $forcedByShipping ? '1' : '0' }}"><input type="checkbox" name="checkout_fields[{{ $fieldKey }}][required]" value="1" @checked($fieldRequired || $forcedByShipping) @disabled($forcedByShipping)>Obligatorio</label>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if(\App\Models\Store::supportsTermsAcceptanceColumns())
            <section class="configuration-card" id="legal-pages">
                <h2>Páginas legales</h2>
                <p>Información legal para tus clientes y aceptación de condiciones en el checkout.</p>
                <div class="configuration-checkout-item" style="margin-bottom:18px;">
                    <div><strong>Aceptación en checkout</strong><small>Solicita que el cliente acepte condiciones antes de enviar el pedido o pagar.</small></div>
                    <label class="configuration-checkout-toggle"><input type="hidden" name="require_terms_acceptance" value="0"><input type="checkbox" name="require_terms_acceptance" value="1" @checked(old('require_terms_acceptance', $store->require_terms_acceptance))>Activar</label>
                </div>
                <div class="configuration-grid">
                    <div class="configuration-field">
                        <label for="terms_title">Texto del checkbox</label>
                        <input id="terms_title" type="text" name="terms_title" value="{{ old('terms_title', $store->terms_title) }}" maxlength="120" placeholder="Acepto los términos y condiciones">
                    </div>
                    <div class="configuration-field">
                        <label for="terms_version">Versión</label>
                        <input id="terms_version" type="text" name="terms_version" value="{{ old('terms_version', $store->terms_version) }}" maxlength="80" placeholder="v1">
                    </div>
                    <div class="configuration-field configuration-field--full">
                        <label for="terms_content">Términos y condiciones</label>
                        <textarea id="terms_content" name="terms_content" maxlength="5000" placeholder="Escribe aquí tus términos y condiciones...">{{ old('terms_content', $store->terms_content) }}</textarea>
                    </div>
                    <div class="configuration-field configuration-field--full">
                        <label for="terms_url">Enlace externo opcional</label>
                        <input id="terms_url" type="url" name="terms_url" value="{{ old('terms_url', $store->terms_url) }}" maxlength="255" placeholder="https://tu-dominio.com/términos">
                    </div>
                </div>
            </section>
        @endif

        <div class="configuration-actions"><button type="submit" class="configuration-submit">Guardar configuración</button></div>
    </form>
</div>
@endsection
