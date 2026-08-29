@extends('layouts.admin')

@if(session('meta_complete_registration'))
    @push('scripts')
        @include('admin.partials.meta-pixel-event', ['event' => 'CompleteRegistration'])
    @endpush
@endif

@section('content')
@php
    use App\Models\Store;
    use App\Models\StorePaymentAccount;
    use App\Services\AiContentService;
    use App\Services\AiCreditService;

    $stepKeys = array_keys($steps);
    $totalSteps = count($steps);
    $displayStep = $currentStepIndex + 1;
    $wizardProgress = $totalSteps > 0 ? (int) round(($displayStep / $totalSteps) * 100) : 100;
    $currentMeta = $steps[$currentStep] ?? [];
    $previousStep = $stepKeys[$currentStepIndex - 1] ?? null;
    $nextStep = $stepKeys[$currentStepIndex + 1] ?? null;
    $storefrontHost = parse_url(config('app.url'), PHP_URL_HOST) ?: request()->getHost();
    $currentSubdomain = old('subdomain', $store->subdomain);
    $canUseSubdomain = Store::supportsSubdomainColumn() && $store->allowsSubdomain();
    $supportsHeroOverlay = Store::supportsHeroOverlayColumns();
    $supportsCheckoutFields = Store::supportsCheckoutFieldsColumn();
    $coverPreview = $store->cover_image ? asset('storage/' . $store->cover_image) : null;
    $heroOverlayTitle = old('hero_overlay_title', $store->hero_overlay_title ?: 'Nueva colección');
    $heroOverlayButtonText = old('hero_overlay_button_text', $store->hero_overlay_button_text ?: 'Ver productos');
    $heroOverlayButtonUrl = old('hero_overlay_button_url', $store->hero_overlay_button_url ?: '/productos');
    $shippingMethods = old('shipping_methods', $store->shipping_methods ?? []);
    $shippingRows = collect($shippingMethods)->pad(3, ['name' => '', 'cost' => ''])->take(5)->values();
    $checkoutFieldsInput = $supportsCheckoutFields ? old('checkout_fields', $store->checkoutFields()) : [];
    $aiCreditService = $store->allowsAiContent() ? app(AiCreditService::class) : null;
    $mercadoPagoAccount = $paymentAccounts[StorePaymentAccount::PROVIDER_MERCADOPAGO] ?? null;
    $wompiAccount = $paymentAccounts[StorePaymentAccount::PROVIDER_WOMPI] ?? null;
@endphp

<style>
    .onboarding-wizard {
        min-height: calc(100vh - 132px);
        display: grid;
        grid-template-columns: minmax(210px, 280px) minmax(0, 1fr);
        gap: 22px;
        padding: clamp(16px, 3vw, 30px);
        border-radius: 24px;
        background:
            radial-gradient(circle at top left, rgba(255, 107, 0, .16), transparent 28%),
            linear-gradient(135deg, #fff7ed, #ffffff 42%, #f8fafc);
    }

    .onboarding-rail,
    .onboarding-card {
        border: 1px solid #e5e7eb;
        border-radius: 22px;
        background: rgba(255, 255, 255, .86);
        box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
    }

    .onboarding-rail {
        position: sticky;
        top: 18px;
        align-self: start;
        display: grid;
        gap: 18px;
        padding: 18px;
    }

    .onboarding-brand {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .onboarding-brand__mark {
        width: 44px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: #111827;
        color: #ff6b00;
        font-weight: 900;
    }

    .onboarding-brand strong,
    .onboarding-card h1,
    .onboarding-section h2 {
        margin: 0;
        color: #111827;
        letter-spacing: 0;
    }

    .onboarding-brand span,
    .onboarding-card__lead,
    .onboarding-step small,
    .onboarding-help,
    .onboarding-field small,
    .onboarding-summary span {
        color: #64748b;
    }

    .onboarding-progress {
        display: grid;
        gap: 9px;
    }

    .onboarding-progress__top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        color: #111827;
        font-size: 13px;
        font-weight: 800;
    }

    .onboarding-progress__bar {
        height: 9px;
        overflow: hidden;
        border-radius: 999px;
        background: #e5e7eb;
    }

    .onboarding-progress__bar span {
        display: block;
        width: var(--wizard-progress, 0%);
        height: 100%;
        border-radius: inherit;
        background: #ff6b00;
    }

    .onboarding-steps {
        display: grid;
        gap: 8px;
    }

    .onboarding-step {
        display: grid;
        grid-template-columns: 32px minmax(0, 1fr);
        gap: 10px;
        padding: 10px;
        border-radius: 14px;
        color: inherit;
        text-decoration: none;
    }

    .onboarding-step.is-active {
        background: #fff7ed;
        box-shadow: inset 0 0 0 1px #fed7aa;
    }

    .onboarding-step.is-complete .onboarding-step__dot {
        background: #16a34a;
        color: #fff;
    }

    .onboarding-step__dot {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #f1f5f9;
        color: #64748b;
        font-size: 12px;
        font-weight: 900;
    }

    .onboarding-step strong {
        display: block;
        color: #111827;
        font-size: 13px;
    }

    .onboarding-step small {
        display: block;
        margin-top: 2px;
        font-size: 12px;
        line-height: 1.35;
    }

    .onboarding-card {
        min-width: 0;
        display: grid;
        align-content: start;
        gap: 22px;
        padding: clamp(18px, 4vw, 34px);
    }

    .onboarding-card__head {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
    }

    .onboarding-card h1 {
        font-size: clamp(28px, 4vw, 46px);
        line-height: 1.05;
    }

    .onboarding-card__lead {
        max-width: 650px;
        margin: 9px 0 0;
        line-height: 1.55;
    }

    .onboarding-pill {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        min-height: 34px;
        padding: 0 13px;
        border-radius: 999px;
        background: #111827;
        color: #fff;
        font-size: 12px;
        font-weight: 900;
    }

    .onboarding-form {
        display: grid;
        gap: 20px;
    }

    .onboarding-section {
        display: grid;
        gap: 16px;
    }

    .onboarding-section h2 {
        font-size: 18px;
    }

    .onboarding-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .onboarding-grid--three {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .onboarding-field {
        display: grid;
        gap: 7px;
        min-width: 0;
    }

    .onboarding-field--full {
        grid-column: 1 / -1;
    }

    .onboarding-field > span,
    .onboarding-field label {
        color: #111827;
        font-size: 13px;
        font-weight: 850;
    }

    .onboarding-field input,
    .onboarding-field select,
    .onboarding-field textarea {
        width: 100%;
        min-height: 48px;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #f8fafc;
        color: #111827;
        padding: 0 13px;
        font: inherit;
    }

    .onboarding-field textarea {
        min-height: 108px;
        padding: 12px 13px;
        resize: vertical;
    }

    .onboarding-field input[type="color"] {
        height: 48px;
        padding: 5px;
    }

    .onboarding-error {
        color: #b42318;
        font-size: 12px;
    }

    .onboarding-media {
        display: grid;
        grid-template-columns: minmax(150px, 210px) minmax(0, 1fr);
        gap: 14px;
        align-items: stretch;
    }

    .onboarding-logo-preview {
        width: 96px;
        height: 96px;
        border-radius: 20px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
    }

    .onboarding-cover-preview {
        position: relative;
        min-height: 260px;
        overflow: hidden;
        border-radius: 20px;
        background:
            linear-gradient(135deg, rgba(17, 24, 39, .48), rgba(255, 107, 0, .22)),
            linear-gradient(135deg, #111827, var(--onboarding-brand, #ff6b00));
        color: #fff;
    }

    .onboarding-cover-preview img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .onboarding-cover-preview::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(0,0,0,.48), rgba(0,0,0,.08));
    }

    .onboarding-cover-preview__copy {
        position: relative;
        z-index: 1;
        width: min(76%, 430px);
        padding: clamp(22px, 5vw, 42px);
    }

    .onboarding-cover-preview__copy strong {
        display: block;
        font-size: clamp(25px, 4vw, 44px);
        line-height: 1.05;
        letter-spacing: 0;
    }

    .onboarding-cover-preview__copy span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 42px;
        margin-top: 16px;
        padding: 0 18px;
        border-radius: 999px;
        background: #fff;
        color: #111827;
        font-size: 13px;
        font-weight: 900;
    }

    .onboarding-upload,
    .onboarding-option,
    .onboarding-summary,
    .onboarding-status-card,
    .onboarding-ai-tools {
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        background: #fff;
    }

    .onboarding-upload {
        display: grid;
        gap: 8px;
        align-content: center;
        padding: 16px;
        cursor: pointer;
    }

    .onboarding-option {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        gap: 12px;
        padding: 14px;
    }

    .onboarding-option:has(input:checked) {
        border-color: #ff6b00;
        box-shadow: 0 0 0 3px rgba(255, 107, 0, .12);
    }

    .onboarding-summary,
    .onboarding-status-card,
    .onboarding-ai-tools {
        display: grid;
        gap: 10px;
        padding: 16px;
    }

    .onboarding-summary strong,
    .onboarding-status-card strong {
        color: #111827;
    }

    .onboarding-status-card.is-complete {
        border-color: #bbf7d0;
        background: #f0fdf4;
    }

    .onboarding-actions {
        position: sticky;
        bottom: 0;
        z-index: 4;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 12px;
        margin: 8px -8px -8px;
        padding: 12px 8px 8px;
        background: linear-gradient(180deg, rgba(255,255,255,0), rgba(255,255,255,.96) 38%);
    }

    .onboarding-actions__group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .onboarding-help {
        margin: 0;
        font-size: 13px;
        line-height: 1.45;
    }

    .onboarding-inline-status {
        min-height: 18px;
        color: #64748b;
        font-size: 12px;
        line-height: 1.4;
    }

    .ai-assistant-status,
    .ai-assistant-preview p {
        margin: 0;
        color: #9a3412;
        font-size: 12px;
        line-height: 1.4;
    }

    .ai-assistant-status.is-error {
        color: #b42318;
    }

    .ai-assistant-preview {
        display: grid;
        gap: 8px;
    }

    .ai-assistant-preview img {
        width: 112px;
        height: 112px;
        border-radius: 18px;
        object-fit: cover;
        border: 1px solid #fed7aa;
    }

    @media (max-width: 920px) {
        .onboarding-wizard {
            grid-template-columns: 1fr;
            padding: 14px;
            border-radius: 0;
        }

        .onboarding-rail {
            position: static;
        }

        .onboarding-steps {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }
    }

    @media (max-width: 640px) {
        .onboarding-card,
        .onboarding-rail {
            border-radius: 18px;
            padding: 16px;
        }

        .onboarding-card__head,
        .onboarding-actions,
        .onboarding-actions__group {
            display: grid;
            grid-template-columns: 1fr;
        }

        .onboarding-pill {
            width: max-content;
        }

        .onboarding-grid,
        .onboarding-grid--three,
        .onboarding-media {
            grid-template-columns: 1fr;
        }

        .onboarding-cover-preview {
            min-height: 210px;
        }

        .onboarding-cover-preview__copy {
            width: 100%;
        }

        .onboarding-actions .btn,
        .onboarding-actions button {
            width: 100%;
        }
    }
</style>

<div class="onboarding-wizard">
    <aside class="onboarding-rail" aria-label="Progreso del onboarding">
        <div class="onboarding-brand">
            <span class="onboarding-brand__mark">V</span>
            <div>
                <strong>Vendly Suite</strong>
                <span>Configuración guiada</span>
            </div>
        </div>

        <div class="onboarding-progress">
            <div class="onboarding-progress__top">
                <span>Paso {{ $displayStep }} de {{ $totalSteps }}</span>
                <span>{{ $wizardProgress }}%</span>
            </div>
            <div class="onboarding-progress__bar" aria-hidden="true">
                <span style="--wizard-progress: {{ $wizardProgress }}%"></span>
            </div>
        </div>

        <nav class="onboarding-steps">
            @foreach($steps as $stepKey => $step)
                <a
                    href="{{ route('admin.store.onboarding', ['step' => $stepKey]) }}"
                    class="onboarding-step {{ $currentStep === $stepKey ? 'is-active' : '' }} {{ ($step['complete'] ?? false) ? 'is-complete' : '' }}"
                >
                    <span class="onboarding-step__dot">{{ ($step['complete'] ?? false) ? 'OK' : $loop->iteration }}</span>
                    <span>
                        <strong>{{ $step['label'] }}</strong>
                        <small>{{ $step['summary'] }}</small>
                    </span>
                </a>
            @endforeach
        </nav>

        <p class="onboarding-help">Puedes guardar y salir sin perder avances. El panel seguirá disponible para completar lo pendiente.</p>
    </aside>

    <main class="onboarding-card">
        @if (session('success'))
            <div class="flash success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="flash error">{{ $errors->first() }}</div>
        @endif

        <header class="onboarding-card__head">
            <div>
                <h1>{{ $currentMeta['label'] ?? 'Configura tu tienda' }}</h1>
                <p class="onboarding-card__lead">{{ $currentMeta['summary'] ?? 'Completa la información clave de tu tienda.' }}</p>
            </div>
            <span class="onboarding-pill">Paso {{ $displayStep }} de {{ $totalSteps }}</span>
        </header>

        <form method="POST" action="{{ route('admin.store.onboarding.update') }}" enctype="multipart/form-data" class="onboarding-form" data-onboarding-form>
            @csrf
            <input type="hidden" name="step" value="{{ $currentStep }}">
            <input type="hidden" name="intent" value="continue" data-onboarding-intent>

            @if($currentStep === 'basic')
                <section class="onboarding-section">
                    <h2>Datos principales</h2>
                    <div class="onboarding-grid">
                        <label class="onboarding-field">
                            <span>Nombre de tienda</span>
                            <input name="name" value="{{ old('name', $store->name) }}" required data-onboarding-store-name>
                            <small>Debe ser fácil de recordar.</small>
                            @error('name')<span class="onboarding-error">{{ $message }}</span>@enderror
                        </label>

                        @if($canUseSubdomain)
                            <label class="onboarding-field">
                                <span>URL de tienda</span>
                                <input name="subdomain" value="{{ $currentSubdomain }}" required placeholder="mitienda" inputmode="url" data-onboarding-subdomain>
                                <small>Será <b><span data-onboarding-subdomain-preview>{{ $currentSubdomain ?: 'mitienda' }}</span>.{{ $storefrontHost }}</b></small>
                                @error('subdomain')<span class="onboarding-error">{{ $message }}</span>@enderror
                            </label>
                        @else
                            <label class="onboarding-field">
                                <span>URL de tienda</span>
                                <input value="{{ $storeUrl }}" readonly>
                                <small>El subdominio editable no está disponible en este plan.</small>
                            </label>
                        @endif

                        <label class="onboarding-field">
                            <span>WhatsApp de pedidos</span>
                            <input name="whatsapp" value="{{ old('whatsapp', $store->whatsapp) }}" required inputmode="tel" autocomplete="tel">
                            <small>Si lo cambias tendrás que verificarlo otra vez.</small>
                            @error('whatsapp')<span class="onboarding-error">{{ $message }}</span>@enderror
                        </label>

                        <label class="onboarding-field onboarding-field--full">
                            <span>Ciudad o dirección</span>
                            <input name="location" value="{{ old('location', $store->location) }}" placeholder="Ej: Cali, Colombia">
                            @error('location')<span class="onboarding-error">{{ $message }}</span>@enderror
                        </label>
                    </div>
                </section>

                <section class="onboarding-status-card {{ $store->whatsapp_verified_at ? 'is-complete' : '' }}">
                    <strong>{{ $store->whatsapp_verified_at ? 'WhatsApp verificado' : 'Verifica tu WhatsApp' }}</strong>
                    @if($store->whatsapp_verified_at)
                        <span>Tu número {{ $store->whatsapp }} ya está verificado.</span>
                    @else
                        <p class="onboarding-help">La verificación protege la prueba gratis y evita que otro usuario use el mismo número.</p>
                        <div class="onboarding-grid">
                            <label class="onboarding-field">
                                <span>WhatsApp a verificar</span>
                                <input id="verify_whatsapp" value="{{ old('whatsapp', $store->whatsapp) }}" inputmode="tel">
                            </label>
                            <label class="onboarding-field">
                                <span>Código de 6 dígitos</span>
                                <input id="verify_whatsapp_code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="000000">
                                <input id="verify_whatsapp_token" type="hidden">
                            </label>
                        </div>
                        <div class="onboarding-actions__group">
                            <button type="button" class="btn btn-secondary" data-send-whatsapp-code>Enviar código</button>
                            <button type="button" class="btn btn-secondary" data-confirm-whatsapp-code>Verificar número</button>
                        </div>
                        <div class="onboarding-inline-status" data-whatsapp-status aria-live="polite"></div>
                    @endif
                </section>
            @elseif($currentStep === 'identity')
                <section class="onboarding-section">
                    <h2>Imagen de marca</h2>
                    <div class="onboarding-media">
                        <div class="onboarding-status-card">
                            <strong>Logo</strong>
                            @if($store->logo_image)
                                <img class="onboarding-logo-preview" src="{{ asset('storage/' . $store->logo_image) }}" alt="{{ $store->name }}">
                            @endif
                            <label class="onboarding-upload" for="onboarding_logo">
                                <strong>Subir logo</strong>
                                <span>Recomendado cuadrado. Máximo 4 MB.</span>
                                <input id="onboarding_logo" type="file" name="logo_image" accept="image/*" data-optimize-image data-max-width="720" data-max-height="720" data-quality="0.86" data-output="webp" data-max-size="4194304">
                            </label>
                            @error('logo_image')<span class="onboarding-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="onboarding-status-card">
                            <strong>Portada</strong>
                            <div class="onboarding-cover-preview" style="--onboarding-brand: {{ old('brand_color', $store->brand_color ?: '#ff6b00') }}" data-onboarding-cover-preview>
                                @if($coverPreview)
                                    <img src="{{ $coverPreview }}" alt="{{ $store->name }}" data-onboarding-cover-image>
                                @else
                                    <img src="" alt="" data-onboarding-cover-image hidden>
                                @endif
                                <div class="onboarding-cover-preview__copy">
                                    <strong data-onboarding-cover-title>{{ $heroOverlayTitle }}</strong>
                                    <span data-onboarding-cover-button>{{ $heroOverlayButtonText }}</span>
                                </div>
                            </div>
                            <label class="onboarding-upload" for="onboarding_cover">
                                <strong>Subir portada</strong>
                                <span>Recomendado horizontal. Máximo 4 MB.</span>
                                <input id="onboarding_cover" type="file" name="cover_image" accept="image/*" data-onboarding-cover-input data-optimize-image data-max-width="1920" data-max-height="1080" data-quality="0.82" data-output="webp" data-max-size="4194304">
                            </label>
                            @error('cover_image')<span class="onboarding-error">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </section>

                @if($store->allowsAiContent())
                    <section class="onboarding-section">
                        <h2>Generar con IA</h2>
                        <div class="onboarding-grid">
                            <div class="onboarding-ai-tools" data-ai-panel data-ai-context="store_logo" data-ai-endpoint="{{ route('admin.ai.content') }}" data-ai-image-endpoint="{{ route('admin.ai.images') }}" data-store-id="{{ $store->id }}">
                                <strong>Logo con IA</strong>
                                <span><span data-ai-credit-balance>{{ $aiCreditService->balance($store) }}</span> créditos disponibles</span>
                                <button type="button" class="btn btn-secondary" data-ai-image-type="store_logo_image">Generar logo</button>
                                <p class="ai-assistant-status" data-ai-status>Consume {{ $aiCreditService->cost(AiContentService::STORE_LOGO_IMAGE) }} créditos.</p>
                                <div class="ai-assistant-preview" data-ai-preview hidden></div>
                            </div>
                            <div class="onboarding-ai-tools" data-ai-panel data-ai-context="store_cover" data-ai-endpoint="{{ route('admin.ai.content') }}" data-ai-image-endpoint="{{ route('admin.ai.images') }}" data-store-id="{{ $store->id }}">
                                <strong>Portada con IA</strong>
                                <span><span data-ai-credit-balance>{{ $aiCreditService->balance($store) }}</span> créditos disponibles</span>
                                <button type="button" class="btn btn-secondary" data-ai-image-type="store_cover_image">Generar portada</button>
                                <p class="ai-assistant-status" data-ai-status>Consume {{ $aiCreditService->cost(AiContentService::STORE_COVER_IMAGE) }} créditos.</p>
                                <div class="ai-assistant-preview" data-ai-preview hidden></div>
                            </div>
                        </div>
                    </section>
                @endif

                <section class="onboarding-section">
                    <h2>Estilo y portada</h2>
                    <div class="onboarding-grid">
                        @if($store->allowsFullCustomization())
                            <label class="onboarding-field">
                                <span>Color principal</span>
                                <input id="onboarding_brand_color" type="color" name="brand_color" value="{{ old('brand_color', $store->brand_color ?: '#ff6b00') }}">
                            </label>
                            <label class="onboarding-field">
                                <span>Fondo de tienda</span>
                                <input type="color" name="background_color" value="{{ old('background_color', $store->background_color ?: '#ffffff') }}">
                            </label>
                            <label class="onboarding-field">
                                <span>Tipografía</span>
                                <select name="font_family">
                                    @foreach(Store::fontFamilyOptions() as $value => $label)
                                        <option value="{{ $value }}" @selected(old('font_family', $store->font_family ?: 'system') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif

                        @if($supportsHeroOverlay)
                            <label class="onboarding-field">
                                <span>Texto sobre portada</span>
                                <input name="hero_overlay_title" value="{{ $heroOverlayTitle }}" maxlength="120" data-onboarding-cover-title-input>
                            </label>
                            <label class="onboarding-field">
                                <span>Texto del botón</span>
                                <input name="hero_overlay_button_text" value="{{ $heroOverlayButtonText }}" maxlength="60" data-onboarding-cover-button-input>
                            </label>
                            <label class="onboarding-field">
                                <span>Enlace del botón</span>
                                <input name="hero_overlay_button_url" value="{{ $heroOverlayButtonUrl }}" maxlength="255" placeholder="/productos">
                            </label>
                            <label class="onboarding-option">
                                <input type="checkbox" name="show_hero_overlay" value="1" @checked(old('show_hero_overlay', $store->show_hero_overlay ?? true))>
                                <span>
                                    <strong>Mostrar texto en portada</strong>
                                    <small>Útil para destacar una colección o promoción.</small>
                                </span>
                            </label>
                        @endif

                        <label class="onboarding-field onboarding-field--full">
                            <span>Descripción corta</span>
                            <textarea name="shop_copy" maxlength="320" placeholder="Cuenta en una frase qué vendes y por qué deberían comprarte.">{{ old('shop_copy', $store->shop_copy) }}</textarea>
                        </label>
                    </div>
                </section>
            @elseif($currentStep === 'product')
                <section class="onboarding-section">
                    <h2>Primer producto</h2>
                    @if($store->products()->exists())
                        @php
                            $firstProduct = $store->products()->latest()->first();
                        @endphp
                        <div class="onboarding-status-card is-complete">
                            <strong>Ya tienes productos publicados</strong>
                            <span>{{ $firstProduct?->name }} está listo en tu catálogo.</span>
                            <a href="{{ route('admin.products.edit', $firstProduct) }}" class="btn btn-secondary">Revisar producto</a>
                        </div>
                    @else
                        <div class="onboarding-status-card">
                            <strong>Crea tu primer producto</strong>
                            <span>Agrega lo mínimo para abrir el catálogo. Luego puedes editar variantes, inventario o galería desde Productos.</span>
                        </div>
                        <div class="onboarding-grid">
                            <label class="onboarding-field">
                                <span>Nombre del producto</span>
                                <input name="product_name" value="{{ old('product_name') }}" required placeholder="Ej: Camiseta básica">
                                @error('product_name')<span class="onboarding-error">{{ $message }}</span>@enderror
                            </label>
                            <label class="onboarding-field">
                                <span>Precio</span>
                                <input type="number" step="0.01" min="0" name="product_price" value="{{ old('product_price') }}" required placeholder="0">
                                @error('product_price')<span class="onboarding-error">{{ $message }}</span>@enderror
                            </label>

                            @if($store->allowsCategories() && $categoryOptions)
                                <label class="onboarding-field">
                                    <span>Categoría</span>
                                    <select name="product_category">
                                        <option value="">Sin categoría</option>
                                        @foreach($categoryOptions as $categoryOption)
                                            @php
                                                $categoryOptionValue = is_array($categoryOption) ? ($categoryOption['value'] ?? '') : $categoryOption;
                                                $categoryOptionLabel = is_array($categoryOption) ? ($categoryOption['label'] ?? $categoryOptionValue) : $categoryOption;
                                            @endphp
                                            <option value="{{ $categoryOptionValue }}" @selected(old('product_category') === $categoryOptionValue)>{{ $categoryOptionLabel }}</option>
                                        @endforeach
                                    </select>
                                    @error('product_category')<span class="onboarding-error">{{ $message }}</span>@enderror
                                </label>
                            @endif

                            <label class="onboarding-field">
                                <span>Imagen</span>
                                <input type="file" name="image" accept="image/*" data-optimize-image data-max-width="1600" data-max-height="1600" data-quality="0.82" data-output="webp" data-max-size="2097152">
                                @error('image')<span class="onboarding-error">{{ $message }}</span>@enderror
                            </label>

                            <label class="onboarding-field onboarding-field--full">
                                <span>Descripción</span>
                                <textarea name="product_description" placeholder="Describe materiales, beneficios o detalles importantes.">{{ old('product_description') }}</textarea>
                            </label>

                            <label class="onboarding-field">
                                <span>Tallas</span>
                                <input name="product_sizes" value="{{ old('product_sizes') }}" placeholder="S, M, L">
                            </label>

                            <label class="onboarding-field">
                                <span>Colores</span>
                                <input name="product_colors" value="{{ old('product_colors') }}" placeholder="Negro, Blanco">
                            </label>
                        </div>
                        <a href="{{ route('admin.products.create') }}" class="btn btn-secondary">Abrir formulario completo</a>
                    @endif
                </section>
            @elseif($currentStep === 'orders')
                <section class="onboarding-section">
                    <h2>Entregas</h2>
                    <div class="onboarding-grid">
                        @foreach($shippingRows as $index => $method)
                            <label class="onboarding-field">
                                <span>Método {{ $index + 1 }}</span>
                                <input name="shipping_methods[{{ $index }}][name]" value="{{ $method['name'] ?? '' }}" maxlength="80" placeholder="{{ ['Recoger en tienda', 'Envío local', 'Envío nacional'][$index] ?? 'Método de entrega' }}">
                            </label>
                            <label class="onboarding-field">
                                <span>Costo</span>
                                <input type="number" name="shipping_methods[{{ $index }}][cost]" value="{{ $method['cost'] ?? '' }}" min="0" step="1" placeholder="0">
                            </label>
                        @endforeach

                        @if(Store::supportsLocalDeliveryColumns())
                            <label class="onboarding-field">
                                <span>Ciudad local</span>
                                <input name="local_delivery_area" value="{{ old('local_delivery_area', $store->local_delivery_area) }}" maxlength="120" placeholder="Ej: Cali">
                            </label>
                            <label class="onboarding-field">
                                <span>Precio local</span>
                                <input type="number" name="local_delivery_cost" value="{{ old('local_delivery_cost', $store->local_delivery_cost) }}" min="0" step="1" placeholder="5000">
                            </label>
                            <label class="onboarding-field">
                                <span>Precio fuera de ciudad</span>
                                <input type="number" name="outside_delivery_cost" value="{{ old('outside_delivery_cost', $store->outside_delivery_cost) }}" min="0" step="1" placeholder="12000">
                            </label>
                        @endif
                    </div>
                </section>

                @if($supportsCheckoutFields)
                    <section class="onboarding-section">
                        <h2>Campos del checkout</h2>
                        <div class="onboarding-grid">
                            @foreach(Store::checkoutFieldDefinitions() as $fieldKey => $fieldDefinition)
                                @php
                                    $fieldState = $checkoutFieldsInput[$fieldKey] ?? $store->checkoutField($fieldKey);
                                @endphp
                                <label class="onboarding-option">
                                    <input type="checkbox" name="checkout_fields[{{ $fieldKey }}][enabled]" value="1" @checked($fieldState['enabled'] ?? false)>
                                    <span>
                                        <strong>{{ $fieldDefinition['label'] }}</strong>
                                        <small>{{ $fieldDefinition['description'] }}</small>
                                    </span>
                                </label>
                                <input type="hidden" name="checkout_fields[{{ $fieldKey }}][required]" value="{{ ($fieldState['required'] ?? false) ? '1' : '0' }}">
                            @endforeach
                        </div>
                    </section>
                @endif
            @elseif($currentStep === 'payments')
                <section class="onboarding-section">
                    <h2>Métodos disponibles</h2>
                    <div class="onboarding-grid onboarding-grid--three">
                        <div class="onboarding-status-card is-complete">
                            <strong>WhatsApp</strong>
                            <span>Activo para pedidos manuales.</span>
                        </div>
                        <div class="onboarding-status-card {{ $mercadoPagoAccount?->isConnected() ? 'is-complete' : '' }}">
                            <strong>Mercado Pago</strong>
                            <span>{{ $mercadoPagoAccount?->isConnected() ? 'Conectado' : 'Pendiente de conexión' }}</span>
                        </div>
                        <div class="onboarding-status-card {{ $wompiAccount?->isWompiReady() ? 'is-complete' : '' }}">
                            <strong>Wompi</strong>
                            <span>{{ $wompiAccount?->isWompiReady() ? 'Activo' : 'No activo' }}</span>
                        </div>
                    </div>
                    <p class="onboarding-help">Las credenciales se configuran en el módulo de pagos para mantener seguridad y no duplicar formularios sensibles.</p>
                    <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary">Configurar pagos</a>
                </section>
            @else
                <section class="onboarding-section">
                    <h2>Resumen</h2>
                    <div class="onboarding-grid">
                        <div class="onboarding-summary">
                            <strong>{{ $store->name }}</strong>
                            <span>{{ $store->businessTypeLabel() }} · {{ $store->whatsapp ?: 'Sin WhatsApp' }}</span>
                        </div>
                        <div class="onboarding-summary">
                            <strong>Identidad</strong>
                            <span>{{ $store->logo_image ? 'Logo listo' : 'Sin logo' }} · {{ $store->cover_image ? 'Portada lista' : 'Sin portada' }}</span>
                        </div>
                        <div class="onboarding-summary">
                            <strong>Catálogo</strong>
                            <span>{{ $store->products()->count() }} producto(s)</span>
                        </div>
                        <div class="onboarding-summary">
                            <strong>Enlace público</strong>
                            <span>{{ $storeUrl }}</span>
                        </div>
                    </div>
                    <div class="onboarding-actions__group">
                        <a href="{{ $storeUrl }}" class="btn" target="_blank" rel="noopener noreferrer">Ver mi tienda</a>
                        <button type="button" class="btn btn-secondary" data-copy-store-url="{{ $storeUrl }}">Copiar enlace</button>
                        @if($store->whatsappNumber())
                            <a class="btn btn-secondary" href="https://wa.me/?text={{ rawurlencode('Mira mi tienda: ' . $storeUrl) }}" target="_blank" rel="noopener noreferrer">Compartir por WhatsApp</a>
                        @endif
                    </div>
                </section>
            @endif

            <footer class="onboarding-actions">
                <div class="onboarding-actions__group">
                    @if($previousStep)
                        <a href="{{ route('admin.store.onboarding', ['step' => $previousStep]) }}" class="btn btn-secondary">Atrás</a>
                    @endif
                    <button type="submit" class="btn btn-secondary" data-intent-submit="exit">Guardar y salir</button>
                </div>

                <div class="onboarding-actions__group">
                    @if($currentStep === 'review')
                        <button type="submit" class="btn" data-intent-submit="finish">Finalizar configuración</button>
                    @else
                        <button type="submit" class="btn" data-intent-submit="continue">Guardar y continuar</button>
                    @endif
                </div>
            </footer>
        </form>
    </main>
</div>

<script>
    (() => {
        const form = document.querySelector('[data-onboarding-form]');
        const intentInput = document.querySelector('[data-onboarding-intent]');

        form?.querySelectorAll('[data-intent-submit]').forEach((button) => {
            button.addEventListener('click', () => {
                intentInput.value = button.dataset.intentSubmit || 'continue';
            });
        });

        form?.addEventListener('submit', () => {
            form.querySelectorAll('[data-intent-submit]').forEach((button) => {
                button.disabled = true;
            });
        });

        document.querySelectorAll('[data-copy-store-url]').forEach((button) => {
            button.addEventListener('click', async () => {
                await navigator.clipboard?.writeText(button.dataset.copyStoreUrl || '');
                button.textContent = 'Enlace copiado';
            });
        });
    })();

    (() => {
        const coverInput = document.querySelector('[data-onboarding-cover-input]');
        const coverImage = document.querySelector('[data-onboarding-cover-image]');
        const titleInput = document.querySelector('[data-onboarding-cover-title-input]');
        const buttonInput = document.querySelector('[data-onboarding-cover-button-input]');
        const titlePreview = document.querySelector('[data-onboarding-cover-title]');
        const buttonPreview = document.querySelector('[data-onboarding-cover-button]');
        const brandColorInput = document.getElementById('onboarding_brand_color');
        const coverPreview = document.querySelector('[data-onboarding-cover-preview]');

        titleInput?.addEventListener('input', () => {
            if (titlePreview) titlePreview.textContent = titleInput.value.trim() || 'Nueva colección';
        });

        buttonInput?.addEventListener('input', () => {
            if (buttonPreview) buttonPreview.textContent = buttonInput.value.trim() || 'Ver productos';
        });

        brandColorInput?.addEventListener('input', () => {
            coverPreview?.style.setProperty('--onboarding-brand', brandColorInput.value || '#ff6b00');
        });

        coverInput?.addEventListener('change', () => {
            const file = coverInput.files?.[0];
            if (!file || !coverImage) return;
            coverImage.src = URL.createObjectURL(file);
            coverImage.hidden = false;
        });
    })();

    (() => {
        const nameInput = document.querySelector('[data-onboarding-store-name]');
        const subdomainInput = document.querySelector('[data-onboarding-subdomain]');
        const preview = document.querySelector('[data-onboarding-subdomain-preview]');

        if (!nameInput || !subdomainInput) return;

        const initialSubdomain = subdomainInput.value;
        let subdomainTouched = Boolean(@json((bool) old('subdomain')));
        const normalizeSubdomain = (value) => String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9-]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '')
            .slice(0, 63);
        const syncPreview = () => {
            if (preview) preview.textContent = subdomainInput.value || 'mitienda';
        };

        subdomainInput.addEventListener('input', () => {
            subdomainTouched = true;
            subdomainInput.value = normalizeSubdomain(subdomainInput.value);
            syncPreview();
        });

        nameInput.addEventListener('input', () => {
            if (subdomainTouched && subdomainInput.value !== initialSubdomain) return;
            subdomainInput.value = normalizeSubdomain(nameInput.value) || initialSubdomain || 'mitienda';
            syncPreview();
        });

        syncPreview();
    })();
</script>

@if($currentStep === 'basic' && ! $store->whatsapp_verified_at)
    <script>
        (() => {
            const status = document.querySelector('[data-whatsapp-status]');
            const sendButton = document.querySelector('[data-send-whatsapp-code]');
            const confirmButton = document.querySelector('[data-confirm-whatsapp-code]');
            const phoneInput = document.getElementById('verify_whatsapp');
            const codeInput = document.getElementById('verify_whatsapp_code');
            const tokenInput = document.getElementById('verify_whatsapp_token');

            const postJson = async (url, payload) => {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                    },
                    body: JSON.stringify(payload),
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const message = data.message
                        || data.errors?.whatsapp?.[0]
                        || data.errors?.whatsapp_verification_code?.[0]
                        || 'No pudimos completar la acción. Intenta nuevamente.';
                    throw new Error(message);
                }

                return data;
            };

            sendButton?.addEventListener('click', async () => {
                sendButton.disabled = true;
                status.textContent = 'Enviando código...';

                try {
                    const data = await postJson(@json(route('admin.store.onboarding.whatsapp.send')), { whatsapp: phoneInput.value });
                    tokenInput.value = data.verification_token || '';
                    status.textContent = data.message || 'Código enviado.';
                    if (!tokenInput.value) window.location.reload();
                    codeInput.focus();
                } catch (error) {
                    status.textContent = error.message;
                } finally {
                    sendButton.disabled = false;
                }
            });

            confirmButton?.addEventListener('click', async () => {
                confirmButton.disabled = true;
                status.textContent = 'Verificando código...';

                try {
                    const data = await postJson(@json(route('admin.store.onboarding.whatsapp.verify')), {
                        whatsapp: phoneInput.value,
                        whatsapp_verification_code: codeInput.value,
                        whatsapp_verification_token: tokenInput.value,
                    });
                    status.textContent = data.message || 'WhatsApp verificado.';
                    window.location.reload();
                } catch (error) {
                    status.textContent = error.message;
                } finally {
                    confirmButton.disabled = false;
                }
            });
        })();
    </script>
@endif

@if($currentStep === 'identity' && $store->allowsAiContent())
    <script src="{{ asset('js/admin-ai-content.js') }}?v={{ filemtime(public_path('js/admin-ai-content.js')) }}" defer></script>
@endif
@endsection
