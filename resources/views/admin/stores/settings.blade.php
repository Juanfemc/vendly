@extends('layouts.admin')

@section('content')
@php
    $storefrontHost = parse_url(config('app.url'), PHP_URL_HOST) ?: request()->getHost();
    $customDomain = old('custom_domain', $store->custom_domain);
    $colombiaLocations = collect($colombiaLocations ?? []);
    $announcementItems = old('announcement_items', $store->announcement_items ?? []);
    $announcementTexts = collect($announcementItems)->pluck('text')->values();
    $shippingMethods = old('shipping_methods', $store->shipping_methods ?? []);
    $selectedReservationDays = old('reservation_available_days', $store->reservation_available_days ?? []);
@endphp

<style>
    .catalog-settings-shell {
        width: min(100%, 1040px);
        max-width: 100%;
        margin: 0 auto;
        padding-bottom: 92px;
        overflow-x: clip;
        box-sizing: border-box;
    }

    .catalog-settings-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        margin: 0 0 24px;
    }

    .catalog-settings-title {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .catalog-settings-back {
        width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        color: #006857;
        text-decoration: none;
        transition: transform .18s ease, background .18s ease;
    }

    .catalog-settings-back:hover {
        transform: translateX(-2px);
        background: #e9fff6;
    }

    .catalog-settings-back svg,
    .catalog-card-icon svg,
    .catalog-link-action svg,
    .catalog-social-icon svg,
    .catalog-upload-card svg,
    .catalog-url-card svg {
        width: 20px;
        height: 20px;
    }

    .catalog-settings-title h2 {
        margin: 0;
        color: #073241;
        font-size: clamp(26px, 3vw, 34px);
        letter-spacing: 0;
    }

    .catalog-settings-title p {
        margin: 4px 0 0;
        color: #0f766e;
        font-size: 14px;
        line-height: 1.45;
    }

    .catalog-plan-pill,
    .catalog-pro-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 24px;
        padding: 4px 10px;
        border-radius: 999px;
        background: #8b7cff;
        color: #ffffff;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: .02em;
        text-transform: uppercase;
    }

    .catalog-settings-form {
        display: grid;
        gap: 22px;
        min-width: 0;
        max-width: 100%;
    }

    .catalog-settings-card {
        min-width: 0;
        max-width: 100%;
        box-sizing: border-box;
        padding: 24px;
        border: 1px solid #d9e4e7;
        border-radius: 18px;
        background: #ffffff;
        box-shadow: 0 14px 34px rgba(15, 23, 42, .055);
    }

    .catalog-card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 22px;
    }

    .catalog-card-title {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        min-width: 0;
    }

    .catalog-card-title > div {
        min-width: 0;
    }

    .catalog-card-icon,
    .catalog-social-icon {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        border-radius: 10px;
        background: #dffdec;
        color: #00a889;
    }

    .catalog-card-title h3 {
        margin: 0;
        color: #062f3e;
        font-size: 20px;
        line-height: 1.2;
    }

    .catalog-card-title p {
        margin: 4px 0 0;
        color: #6c9d9c;
        font-size: 13px;
        line-height: 1.45;
    }

    .catalog-settings-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .catalog-settings-grid--three {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .catalog-field {
        display: grid;
        gap: 8px;
        min-width: 0;
    }

    .catalog-field--full {
        grid-column: 1 / -1;
    }

    .catalog-field label,
    .catalog-field > span,
    .catalog-upload-label {
        color: #073241;
        font-size: 14px;
        font-weight: 800;
    }

    .catalog-field input,
    .catalog-field select,
    .catalog-field textarea,
    .catalog-url-card,
    .catalog-social-input {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        min-height: 50px;
        margin: 0;
        padding: 13px 16px;
        border: 1px solid #d6e2e7;
        border-radius: 14px;
        background: #ffffff;
        color: #102a43;
        font-size: 14px;
        box-shadow: none;
        transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
    }

    .catalog-field textarea {
        min-height: 118px;
        resize: vertical;
    }

    .catalog-field input:focus,
    .catalog-field select:focus,
    .catalog-field textarea:focus {
        border-color: #22e1a8;
        box-shadow: 0 0 0 4px rgba(34, 225, 168, .14);
        outline: none;
    }

    .catalog-field small,
    .catalog-help,
    .catalog-tip {
        color: #6c9d9c;
        font-size: 13px;
        line-height: 1.45;
    }

    .catalog-readonly {
        background: #f8fafb !important;
        color: #577177 !important;
    }

    .catalog-url-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto auto;
        gap: 12px;
        align-items: stretch;
        min-width: 0;
        max-width: 100%;
    }

    .catalog-url-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #f8fafb;
    }

    .catalog-url-card span {
        min-width: 0;
        overflow: hidden;
        color: #073241;
        font-weight: 800;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .catalog-copy-button,
    .catalog-visit-button,
    .catalog-link-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 50px;
        padding: 0 18px;
        border: 1px solid #d6e2e7;
        border-radius: 14px;
        background: #ffffff;
        color: #006857;
        font-weight: 900;
        text-decoration: none;
        cursor: pointer;
    }

    .catalog-copy-button {
        border-color: #006857;
        background: #006857;
        color: #ffffff;
    }

    .catalog-link-list {
        display: flex;
        flex-wrap: wrap;
        gap: 18px;
        margin-top: 16px;
        padding-top: 14px;
        border-top: 1px solid #edf2f4;
    }

    .catalog-link-action {
        min-height: auto;
        padding: 0;
        border: 0;
        background: transparent;
        color: #006857;
        font-size: 13px;
    }

    .catalog-link-action.is-muted {
        color: #8b7cff;
    }

    .catalog-media-layout {
        display: grid;
        grid-template-columns: minmax(170px, 220px) minmax(0, 1fr);
        gap: 22px;
        align-items: start;
    }

    .catalog-upload-group {
        display: grid;
        gap: 10px;
    }

    .catalog-upload-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }

    .catalog-upload-card {
        width: 78px;
        height: 78px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1.5px dashed #c9d4dc;
        border-radius: 999px;
        background: #fbfdff;
        color: #94a3b8;
        cursor: pointer;
        overflow: hidden;
        transition: border-color .18s ease, background .18s ease, transform .18s ease;
    }

    .catalog-upload-card:hover {
        transform: translateY(-1px);
        border-color: #22e1a8;
        background: #edfff8;
    }

    .catalog-upload-card--wide {
        width: 100%;
        max-width: 100%;
        min-height: 88px;
        border-radius: 16px;
    }

    .catalog-upload-card input[type="file"] {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        border: 0;
        opacity: 0;
        pointer-events: none;
    }

    .catalog-upload-preview {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .catalog-ai-button {
        min-height: 44px;
        border-color: #22e1a8;
        color: #006857;
    }

    .catalog-text-count {
        justify-self: end;
        color: #8eb8b6;
        font-size: 12px;
    }

    .catalog-color-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
    }

    .catalog-palette-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .catalog-palette-option {
        position: relative;
        display: grid;
        gap: 10px;
        min-height: 104px;
        padding: 14px;
        border: 1px solid #dce5ea;
        border-radius: 16px;
        background: #ffffff;
        color: #073241;
        cursor: pointer;
        text-align: left;
        transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
    }

    .catalog-palette-option:hover,
    .catalog-palette-option.is-selected {
        border-color: #22e1a8;
        box-shadow: 0 14px 34px rgba(0, 104, 87, .12);
        transform: translateY(-1px);
    }

    .catalog-palette-option.is-selected::after {
        content: "";
        position: absolute;
        top: 12px;
        right: 12px;
        width: 12px;
        height: 12px;
        border-radius: 999px;
        background: #22e1a8;
        box-shadow: 0 0 0 4px rgba(34, 225, 168, .16);
    }

    .catalog-palette-swatches {
        display: flex;
        gap: 6px;
    }

    .catalog-palette-swatch {
        width: 24px;
        height: 24px;
        border: 1px solid rgba(7, 50, 65, .12);
        border-radius: 999px;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .44);
    }

    .catalog-palette-option strong,
    .catalog-palette-option span {
        display: block;
    }

    .catalog-palette-option strong {
        font-size: 14px;
        line-height: 1.2;
    }

    .catalog-palette-option span {
        color: #6c9d9c;
        font-size: 12px;
        line-height: 1.35;
    }

    .catalog-color-item {
        display: grid;
        grid-template-columns: 58px minmax(0, 1fr);
        gap: 12px;
        align-items: center;
        min-width: 0;
    }

    .catalog-color-swatch {
        width: 58px;
        height: 58px;
        padding: 0;
        border: 1px solid #d6e2e7;
        border-radius: 14px;
        background: #ffffff;
        cursor: pointer;
    }

    .catalog-color-item strong {
        display: block;
        color: #073241;
        font-size: 14px;
    }

    .catalog-color-item span {
        display: block;
        margin-top: 2px;
        color: #6c9d9c;
        font-size: 12px;
        line-height: 1.35;
    }

    .catalog-font-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
    }

    .catalog-font-option {
        position: relative;
        min-height: 78px;
        padding: 16px;
        border: 2px solid #dce5ea;
        border-radius: 14px;
        background: #ffffff;
        color: #073241;
        cursor: pointer;
        text-align: left;
    }

    .catalog-font-option.is-selected {
        border-color: #22e1a8;
        background: #effff8;
    }

    .catalog-font-option.is-selected::after {
        content: "";
        position: absolute;
        top: 14px;
        right: 14px;
        width: 16px;
        height: 16px;
        border-radius: 999px;
        background: #22e1a8;
        box-shadow: inset 0 0 0 4px #ffffff;
    }

    .catalog-font-option strong {
        display: block;
        margin-bottom: 5px;
        font-size: 16px;
    }

    .catalog-font-option span {
        color: #6c9d9c;
        font-size: 12px;
    }

    .catalog-font-select {
        display: none;
    }

    .catalog-social-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .catalog-social-input {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 12px;
        min-height: 72px;
        align-items: center;
        background: #f8fafb;
        min-width: 0;
    }

    .catalog-social-input > span:last-child,
    .catalog-toggle-row > div,
    .catalog-option-row > div,
    .catalog-checkout-item > div {
        min-width: 0;
    }

    .catalog-social-input input {
        min-height: auto;
        padding: 0;
        border: 0;
        border-radius: 0;
        background: transparent;
    }

    .catalog-social-label {
        color: #73a6a5;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .catalog-toggle-row,
    .catalog-option-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding: 18px;
        border: 1px solid #edf2f4;
        border-radius: 16px;
        background: #f8fafb;
    }

    .catalog-option-row {
        align-items: flex-start;
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
    }

    .catalog-switch {
        position: relative;
        width: 50px;
        height: 30px;
        flex: 0 0 auto;
    }

    .catalog-switch input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .catalog-switch i {
        position: absolute;
        inset: 0;
        border-radius: 999px;
        background: #dbe2ea;
        transition: background .18s ease;
    }

    .catalog-switch i::after {
        content: "";
        position: absolute;
        top: 4px;
        left: 4px;
        width: 22px;
        height: 22px;
        border-radius: 999px;
        background: #ffffff;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .18);
        transition: transform .18s ease;
    }

    .catalog-switch input:checked + i {
        background: #22e1a8;
    }

    .catalog-switch input:checked + i::after {
        transform: translateX(20px);
    }

    .catalog-number-list,
    .catalog-shipping-list {
        display: grid;
        gap: 10px;
    }

    .catalog-check-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 10px;
    }

    .catalog-check {
        display: flex;
        align-items: center;
        gap: 9px;
        min-height: 46px;
        padding: 10px 12px;
        border: 1px solid #d9e4e7;
        border-radius: 12px;
        background: #f8fafb;
        color: #073241;
        font-size: 14px;
        font-weight: 700;
    }

    .catalog-check input {
        width: auto;
        margin: 0;
        accent-color: #22e1a8;
    }

    .catalog-checkout-grid {
        display: grid;
        gap: 12px;
    }

    .catalog-checkout-item {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto auto;
        gap: 12px;
        align-items: center;
        padding: 14px;
        border: 1px solid #edf2f4;
        border-radius: 16px;
        background: #f8fafb;
        min-width: 0;
    }

    .catalog-checkout-item strong {
        display: block;
        color: #073241;
        font-size: 14px;
    }

    .catalog-checkout-item small {
        display: block;
        margin-top: 3px;
        color: #6c9d9c;
        line-height: 1.35;
    }

    .catalog-checkout-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #073241;
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
    }

    .catalog-checkout-toggle input {
        width: 16px;
        height: 16px;
        margin: 0;
        accent-color: #22e1a8;
    }

    .catalog-number-row,
    .catalog-shipping-row {
        display: grid;
        grid-template-columns: 34px minmax(0, 1fr);
        gap: 10px;
        align-items: center;
    }

    .catalog-shipping-row {
        grid-template-columns: 34px minmax(0, 1fr) minmax(130px, 180px);
    }

    .catalog-number-badge {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: #e9fff6;
        color: #006857;
        font-size: 13px;
        font-weight: 900;
    }

    .catalog-actions {
        position: sticky;
        bottom: 0;
        z-index: 20;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        margin-top: 4px;
        padding: 16px 0 0;
        background: linear-gradient(180deg, rgba(245, 246, 250, 0), #f5f6fa 32%);
    }

    .catalog-actions-inner {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding: 14px;
        border: 1px solid #d9e4e7;
        border-radius: 18px 18px 0 0;
        background: rgba(255, 255, 255, .96);
        box-shadow: 0 -14px 34px rgba(15, 23, 42, .08);
        backdrop-filter: blur(14px);
    }

    .catalog-actions-buttons {
        display: flex;
        gap: 10px;
    }

    @media (max-width: 820px) {
        .catalog-settings-shell {
            padding-bottom: 190px;
        }

        .catalog-settings-hero,
        .catalog-card-head,
        .catalog-url-row,
        .catalog-actions-inner {
            display: grid;
        }

        .catalog-settings-grid,
        .catalog-settings-grid--three,
        .catalog-media-layout,
        .catalog-palette-grid,
        .catalog-color-grid,
        .catalog-font-grid,
        .catalog-social-grid,
        .catalog-checkout-item,
        .catalog-shipping-row {
            grid-template-columns: 1fr;
        }

        .catalog-settings-card {
            padding: 18px;
            border-radius: 16px;
        }

        .catalog-actions {
            position: fixed;
            left: 12px;
            right: 12px;
            bottom: calc(82px + env(safe-area-inset-bottom, 0px));
            padding: 0;
            background: transparent;
        }

        .catalog-actions-inner {
            border-radius: 18px;
        }

        .catalog-actions-buttons,
        .catalog-actions-buttons .btn,
        .catalog-copy-button,
        .catalog-visit-button {
            width: 100%;
        }

        .catalog-card-title {
            align-items: flex-start;
        }
    }

    .catalog-settings-shell {
        --catalog-panel-ink: #151515;
        --catalog-panel-muted: #64748b;
        --catalog-panel-subtle: #f8fafc;
        --catalog-panel-accent-soft: #fff7ed;
        --catalog-panel-accent-border: #fed7aa;
    }

    .catalog-settings-shell .catalog-settings-back,
    .catalog-settings-shell .catalog-link-action,
    .catalog-settings-shell .catalog-ai-button {
        color: var(--vendly-brand);
    }

    .catalog-settings-shell .catalog-settings-back:hover,
    .catalog-settings-shell .catalog-card-icon,
    .catalog-settings-shell .catalog-social-icon,
    .catalog-settings-shell .catalog-number-badge {
        background: var(--catalog-panel-accent-soft);
        color: var(--vendly-brand);
    }

    .catalog-settings-shell .catalog-settings-title h2,
    .catalog-settings-shell .catalog-card-title h3,
    .catalog-settings-shell .catalog-field label,
    .catalog-settings-shell .catalog-field > span,
    .catalog-settings-shell .catalog-url-card span,
    .catalog-settings-shell .catalog-color-item strong,
    .catalog-settings-shell .catalog-font-option strong {
        color: var(--catalog-panel-ink);
    }

    .catalog-settings-shell .catalog-settings-title p,
    .catalog-settings-shell .catalog-card-title p,
    .catalog-settings-shell .catalog-field small,
    .catalog-settings-shell .catalog-help,
    .catalog-settings-shell .catalog-tip,
    .catalog-settings-shell .catalog-color-item span,
    .catalog-settings-shell .catalog-font-option span,
    .catalog-settings-shell .catalog-social-label {
        color: var(--catalog-panel-muted);
    }

    .catalog-settings-shell .catalog-copy-button {
        border-color: var(--catalog-panel-ink);
        background: var(--catalog-panel-ink);
        color: #ffffff;
        box-shadow: 0 12px 24px rgba(15, 23, 42, .12);
    }

    .catalog-settings-shell .catalog-visit-button {
        color: var(--catalog-panel-ink);
    }

    .catalog-settings-shell .catalog-ai-button,
    .catalog-settings-shell .catalog-upload-card:hover,
    .catalog-settings-shell .catalog-font-option.is-selected {
        border-color: var(--vendly-brand);
    }

    .catalog-settings-shell .catalog-upload-card:hover,
    .catalog-settings-shell .catalog-font-option.is-selected {
        background: var(--catalog-panel-accent-soft);
        color: var(--catalog-panel-ink);
    }

    .catalog-settings-shell .catalog-font-option.is-selected::after,
    .catalog-settings-shell .catalog-switch input:checked + i {
        background: var(--vendly-brand);
    }

    .catalog-settings-shell .catalog-field input:focus,
    .catalog-settings-shell .catalog-field select:focus,
    .catalog-settings-shell .catalog-field textarea:focus {
        border-color: var(--vendly-brand);
        box-shadow: 0 0 0 4px var(--vendly-brand-focus);
    }
</style>

<div class="catalog-settings-shell">
    <div class="catalog-settings-hero">
        <div class="catalog-settings-title">
            <a href="/dashboard" class="catalog-settings-back" aria-label="Volver al panel">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5"></path>
                    <path d="m12 19-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h2>Apariencia del Catálogo</h2>
                <p>Personaliza el diseño, información y contenido público de tu tienda online.</p>
            </div>
        </div>
        <span class="catalog-plan-pill">{{ $store->planLabel() }}</span>
    </div>

    @if (session('success'))
        <div class="flash success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="flash error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="/admin/store-settings" enctype="multipart/form-data" class="catalog-settings-form">
        @csrf
        <input type="hidden" name="business_type" value="{{ old('business_type', $store->business_type ?? 'store') }}">

        <section class="catalog-settings-card">
            <div class="catalog-card-head">
                <div class="catalog-card-title">
                    <span class="catalog-card-icon" aria-hidden="true">Aa</span>
                    <div>
                        <h3>Identidad de tu tienda</h3>
                        <p>Nombre visible en tu catálogo público.</p>
                    </div>
                </div>
            </div>

            <div class="catalog-field">
                <label for="store_name">Nombre de la tienda</label>
                <input id="store_name" type="text" name="name" value="{{ old('name', $store->name) }}" placeholder="Nombre tienda" required>
                <small>Este nombre aparece en el encabezado, footer y vista previa. Cambiarlo no modifica el enlace de tu tienda.</small>
            </div>
        </section>

        <section class="catalog-settings-card" id="store-domain">
            <div class="catalog-card-head">
                <div class="catalog-card-title">
                    <span class="catalog-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.07 0l2.12-2.12a5 5 0 0 0-7.07-7.07L11 4.93"></path><path d="M14 11a5 5 0 0 0-7.07 0L4.81 13.12a5 5 0 0 0 7.07 7.07L13 19.07"></path></svg>
                    </span>
                    <div>
                        <h3>Enlace de tu tienda</h3>
                        <p>Comparte esta URL con tus clientes.</p>
                    </div>
                </div>
            </div>

            <div class="catalog-url-row">
                <div class="catalog-url-card">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.07 0l2.12-2.12a5 5 0 0 0-7.07-7.07L11 4.93"></path><path d="M14 11a5 5 0 0 0-7.07 0L4.81 13.12a5 5 0 0 0 7.07 7.07L13 19.07"></path></svg>
                    <span>{{ $storeUrl }}</span>
                </div>
                <button type="button" class="catalog-copy-button" data-copy-store-url="{{ $storeUrl }}">Copiar</button>
                <a class="catalog-visit-button" href="{{ $storeUrl }}" target="_blank" rel="noopener">Visitar</a>
            </div>

            <div class="catalog-link-list">
                @if($store->allowsSubdomain())
                    <span class="catalog-link-action">Cambiar enlace Vendly</span>
                    <div class="catalog-settings-grid catalog-settings-grid--three" style="width:100%; margin-top:4px;">
                        <div class="catalog-field">
                            <label for="store_subdomain">Subdominio</label>
                            <input id="store_subdomain" type="text" name="subdomain" value="{{ old('subdomain', $store->subdomain) }}" placeholder="mitienda">
                            <small>Tu tienda podrá usarse como {{ old('subdomain', $store->subdomain) ?: 'mitienda' }}.{{ $storefrontHost }}.</small>
                        </div>

                        @if($store->allowsCustomDomain())
                            <div class="catalog-field catalog-field--full">
                                <label for="store_custom_domain">Dominio personalizado <span class="catalog-pro-pill">Premium</span></label>
                                <input id="store_custom_domain" type="text" name="custom_domain" value="{{ $customDomain }}" placeholder="www.tudominio.com">
                                <small>Guarda el dominio y apunta un CNAME hacia {{ $storefrontHost }}. Estado: {{ ($store->custom_domain_status ?? 'pending') === 'verified' ? 'verificado' : 'pendiente de verificación' }}.</small>
                            </div>
                        @else
                            <div class="catalog-field">
                                <label for="store_custom_domain_locked">Dominio personalizado <span class="catalog-pro-pill">Premium</span></label>
                                <input id="store_custom_domain_locked" class="catalog-readonly" type="text" value="Disponible desde Premium" readonly>
                            </div>
                        @endif
                    </div>
                @else
                    <span class="catalog-link-action">Subdominio disponible desde Pro</span>
                    <span class="catalog-link-action is-muted">Conectar dominio <span class="catalog-pro-pill">Premium</span></span>
                @endif
            </div>
        </section>

        <section class="catalog-settings-card">
            <div class="catalog-card-head">
                <div class="catalog-card-title">
                    <span class="catalog-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="3"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="m21 15-5-5L5 21"></path></svg>
                    </span>
                    <div>
                        <h3>Identidad visual</h3>
                        <p>Logo, banner y descripción de tu tienda.</p>
                    </div>
                </div>
            </div>

            @include('admin.partials.ai-content-tools', ['aiStore' => $store, 'aiContext' => 'store_images'])

            <div class="catalog-media-layout">
                <div class="catalog-upload-group">
                    <span class="catalog-upload-label">Logo</span>
                    <small class="catalog-help">400 x 400px</small>
                    <div class="catalog-upload-row">
                        <label class="catalog-upload-card" for="store_logo_image">
                            @if ($store->logo_image)
                                <img class="catalog-upload-preview" src="{{ asset('storage/' . $store->logo_image) }}" alt="{{ $store->name }}">
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 16V4"></path><path d="m7 9 5-5 5 5"></path><path d="M20 16v4H4v-4"></path></svg>
                            @endif
                            <input id="store_logo_image" type="file" name="logo_image" accept="image/*" data-optimize-image data-max-width="720" data-max-height="720" data-quality="0.86" data-output="webp" data-max-size="4194304">
                        </label>
                    </div>
                </div>

                <div class="catalog-upload-group">
                    <span class="catalog-upload-label">Banner</span>
                    <small class="catalog-help">1536 x 512px recomendado</small>
                    <div class="catalog-upload-row">
                        <label class="catalog-upload-card catalog-upload-card--wide" for="store_cover_image">
                            @if ($store->cover_image)
                                <img class="catalog-upload-preview" src="{{ asset('storage/' . $store->cover_image) }}" alt="{{ $store->name }}">
                            @else
                                <span>Subir banner</span>
                            @endif
                            <input id="store_cover_image" type="file" name="cover_image" accept="image/*" data-optimize-image data-max-width="1920" data-max-height="1080" data-quality="0.82" data-output="webp" data-max-size="4194304">
                        </label>
                    </div>
                </div>

                @if(\App\Models\Store::supportsHeroOverlayColumns())
                    <div class="catalog-field">
                        <label for="hero_overlay_title">Texto sobre portada</label>
                        <input id="hero_overlay_title" type="text" name="hero_overlay_title" value="{{ old('hero_overlay_title', $store->hero_overlay_title) }}" maxlength="120" placeholder="Nueva colección">
                        <small>Se muestra centrado sobre el banner.</small>
                    </div>
                    <div class="catalog-field">
                        <label for="hero_overlay_button_text">Texto del botón</label>
                        <input id="hero_overlay_button_text" type="text" name="hero_overlay_button_text" value="{{ old('hero_overlay_button_text', $store->hero_overlay_button_text) }}" maxlength="60" placeholder="Comprar ahora">
                        <small>Si lo dejas vacío se usará "Comprar ahora".</small>
                    </div>
                    <div class="catalog-field catalog-field--full">
                        <label for="hero_overlay_button_url">Enlace del botón</label>
                        <input id="hero_overlay_button_url" type="text" name="hero_overlay_button_url" value="{{ old('hero_overlay_button_url', $store->hero_overlay_button_url) }}" maxlength="255" placeholder="/productos o https://...">
                        <small>Si lo dejas vacío llevará a todos los productos.</small>
                    </div>
                @endif

                <div class="catalog-field catalog-field--full">
                    <label for="shop_copy">Descripción</label>
                    <textarea id="shop_copy" name="shop_copy" maxlength="1000" placeholder="Ej: Tienda de ropa deportiva con envíos a todo el país...">{{ old('shop_copy', $store->shop_copy) }}</textarea>
                    <span class="catalog-text-count">Max. 1000</span>
                </div>
            </div>
        </section>

        @if($store->allowsFullCustomization())
            <section class="catalog-settings-card">
                <div class="catalog-card-head">
                    <div class="catalog-card-title">
                        <span class="catalog-card-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13.5" cy="6.5" r=".5"></circle><circle cx="17.5" cy="10.5" r=".5"></circle><circle cx="8.5" cy="7.5" r=".5"></circle><circle cx="6.5" cy="12.5" r=".5"></circle><path d="M12 22a10 10 0 1 1 10-10c0 3-2 3-4 3h-1.5a2.5 2.5 0 0 0 0 5H18c1 0 1 2-6 2Z"></path></svg>
                        </span>
                        <div>
                            <h3>Colores</h3>
                            <p>Personaliza los colores de tu catálogo.</p>
                        </div>
                    </div>
                    <button type="button" class="catalog-link-action" data-reset-theme>Restaurar</button>
                </div>

	                @php
	                    $selectedBrandColor = \App\Support\BrandTheme::normalizeColor(old('brand_color', $store->brand_color), '#111111');
	                    $selectedBackgroundColor = \App\Support\BrandTheme::normalizeColor(old('background_color', $store->background_color), '#ffffff');
	                    $selectedTextColor = \App\Models\Store::automaticTextColorFor($selectedBackgroundColor);
	                    $selectedFontFamily = old('font_family', $store->font_family ?: 'system');
	                    $selectedFontFamily = array_key_exists($selectedFontFamily, \App\Models\Store::FONT_FAMILIES) ? $selectedFontFamily : 'system';
	                    $catalogPalettes = [
	                        ['name' => 'Clasica', 'description' => 'Negro, blanco y contraste limpio.', 'brand' => '#111111', 'background' => '#ffffff'],
	                        ['name' => 'Vendly', 'description' => 'Naranja comercial con fondo claro.', 'brand' => '#ff6b00', 'background' => '#ffffff'],
	                        ['name' => 'Natural', 'description' => 'Verde suave para marcas cercanas.', 'brand' => '#007c68', 'background' => '#f7fbf8'],
	                        ['name' => 'Boutique', 'description' => 'Elegante para moda y belleza.', 'brand' => '#7c3aed', 'background' => '#fbf8ff'],
	                        ['name' => 'Tecnologia', 'description' => 'Azul profundo para productos tech.', 'brand' => '#0f4c81', 'background' => '#f5f8ff'],
	                        ['name' => 'Energia', 'description' => 'Rojo vibrante para promociones.', 'brand' => '#dc2626', 'background' => '#fff7f4'],
	                        ['name' => 'Premium', 'description' => 'Dorado sobrio para tiendas finas.', 'brand' => '#b7791f', 'background' => '#fffaf0'],
	                        ['name' => 'Minimal', 'description' => 'Gris moderno y muy neutral.', 'brand' => '#334155', 'background' => '#f8fafc'],
	                    ];
	                @endphp

	                <div class="catalog-palette-grid" data-palette-picker>
	                    @foreach($catalogPalettes as $palette)
	                        @php
	                            $paletteText = \App\Models\Store::automaticTextColorFor($palette['background']);
	                            $isSelectedPalette = strcasecmp($selectedBrandColor, $palette['brand']) === 0
	                                && strcasecmp($selectedBackgroundColor, $palette['background']) === 0;
	                        @endphp
	                        <button
	                            type="button"
	                            class="catalog-palette-option @if($isSelectedPalette) is-selected @endif"
	                            data-palette-brand="{{ $palette['brand'] }}"
	                            data-palette-background="{{ $palette['background'] }}"
	                            data-palette-text="{{ $paletteText }}"
	                        >
	                            <span class="catalog-palette-swatches" aria-hidden="true">
	                                <span class="catalog-palette-swatch" style="background: {{ $palette['brand'] }}"></span>
	                                <span class="catalog-palette-swatch" style="background: {{ $palette['background'] }}"></span>
	                                <span class="catalog-palette-swatch" style="background: {{ $paletteText }}"></span>
	                            </span>
	                            <span>
	                                <strong>{{ $palette['name'] }}</strong>
	                                <span>{{ $palette['description'] }}</span>
	                            </span>
	                        </button>
	                    @endforeach
	                </div>

	                <div class="catalog-color-grid">
                    <label class="catalog-color-item">
                        <input class="catalog-color-swatch" type="color" name="brand_color" value="{{ $selectedBrandColor }}" data-theme-color-input="brand">
                        <span><strong>Acento</strong><span>Precios, botónes, carrito y enlaces.</span></span>
                    </label>
                    <label class="catalog-color-item">
                        <input class="catalog-color-swatch" type="color" name="background_color" value="{{ $selectedBackgroundColor }}" data-theme-color-input="background">
                        <span><strong>Fondo</strong><span>Fondo de páginas y secciones.</span></span>
                    </label>
                    <label class="catalog-color-item">
                        <input class="catalog-color-swatch" type="color" value="{{ $selectedTextColor }}" disabled>
                        <input type="hidden" name="text_color" value="{{ $selectedTextColor }}" data-theme-color-input="text">
                        <span><strong>Texto</strong><span>Se ajusta automáticamente para contraste.</span></span>
                    </label>
                </div>
            </section>

            <section class="catalog-settings-card">
                <div class="catalog-card-head">
                    <div class="catalog-card-title">
                        <span class="catalog-card-icon" aria-hidden="true">Aa</span>
                        <div>
                            <h3>Tipografía</h3>
                            <p>Elige el estilo tipográfico de tu catálogo.</p>
                        </div>
                    </div>
                </div>

                <div class="catalog-font-grid" data-font-picker>
                    @foreach(\App\Models\Store::FONT_FAMILIES as $value => $font)
                        <button type="button" class="catalog-font-option @if($selectedFontFamily === $value) is-selected @endif" data-font-value="{{ $value }}" style="font-family: {{ $font['css'] }};">
                            <strong>{{ $font['label'] }}</strong>
                            <span>{{ $font['css'] }}</span>
                        </button>
                    @endforeach
                </div>
                <select id="font_family" class="catalog-font-select" name="font_family">
                    @foreach(\App\Models\Store::fontFamilyOptions() as $value => $label)
                        <option value="{{ $value }}" @selected($selectedFontFamily === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </section>
        @else
            <section class="catalog-settings-card">
                <div class="catalog-card-head">
                    <div class="catalog-card-title">
                        <span class="catalog-card-icon" aria-hidden="true">Aa</span>
                        <div>
                            <h3>Apariencia avanzada</h3>
                            <p>Colores, fuentes y vistas completas están disponibles desde Pro.</p>
                        </div>
                    </div>
                    <span class="catalog-pro-pill">Pro</span>
                </div>
                <p class="catalog-help">Tu plan actual mantiene logo, portada y datos principales.</p>
            </section>
        @endif

        <section class="catalog-settings-card">
            <div class="catalog-card-head">
                <div class="catalog-card-title">
                    <span class="catalog-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 3.1 5.18 2 2 0 0 1 5.11 3h3a2 2 0 0 1 2 1.72c.12.9.32 1.78.59 2.63a2 2 0 0 1-.45 2.11L9 10.7a16 16 0 0 0 4.3 4.3l1.24-1.25a2 2 0 0 1 2.11-.45c.85.27 1.73.47 2.63.59A2 2 0 0 1 22 16.92z"></path></svg>
                    </span>
                    <div>
                        <h3>WhatsApp de contacto</h3>
                        <p>Número público para compras, contacto y confirmaciones.</p>
                    </div>
                </div>
            </div>
            <div class="catalog-settings-grid">
                <div class="catalog-field">
                    <label for="store_whatsapp">WhatsApp</label>
                    <input id="store_whatsapp" type="text" name="whatsapp" value="{{ old('whatsapp', $store->whatsapp) }}" placeholder="300 123 4567" required>
                    <small>Tip: tus clientes usarán este número para dudas, compras y confirmaciones.</small>
                </div>
                <div class="catalog-field">
                    <label for="store_location">Dirección / ubicación</label>
                    <input id="store_location" type="text" name="location" value="{{ old('location', $store->location) }}" placeholder="Calle 123 #45-67, Bogota">
                </div>
            </div>
        </section>

        <section class="catalog-settings-card">
            <div class="catalog-card-head">
                <div class="catalog-card-title">
                    <span class="catalog-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6Z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
                    </span>
                    <div>
                        <h3>Redes sociales</h3>
                        <p>Conecta tus redes con el catálogo.</p>
                    </div>
                </div>
            </div>
            <div class="catalog-social-grid">
                <label class="catalog-social-input">
                    <span class="catalog-social-icon">IG</span>
                    <span>
                        <span class="catalog-social-label">Instagram</span>
                        <input type="url" name="instagram_url" value="{{ old('instagram_url', $store->instagram_url) }}" placeholder="https://instagram.com/usuario">
                    </span>
                </label>
                <label class="catalog-social-input">
                    <span class="catalog-social-icon">FB</span>
                    <span>
                        <span class="catalog-social-label">Facebook</span>
                        <input type="url" name="facebook_url" value="{{ old('facebook_url', $store->facebook_url) }}" placeholder="https://facebook.com/...">
                    </span>
                </label>
                <label class="catalog-social-input">
                    <span class="catalog-social-icon">TT</span>
                    <span>
                        <span class="catalog-social-label">TikTok</span>
                        <input type="url" name="tiktok_url" value="{{ old('tiktok_url', $store->tiktok_url) }}" placeholder="https://tiktok.com/@usuario">
                    </span>
                </label>
                <label class="catalog-social-input">
                    <span class="catalog-social-icon">WEB</span>
                    <span>
                        <span class="catalog-social-label">Web</span>
                        <input class="catalog-readonly" type="text" value="{{ $storeUrl }}" readonly>
                    </span>
                </label>
            </div>
        </section>

        @if($store->allowsCommercialNotices())
            <section class="catalog-settings-card">
                <div class="catalog-card-head">
                    <div class="catalog-card-title">
                        <span class="catalog-card-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 11 18-5v12L3 14v-3Z"></path><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"></path></svg>
                        </span>
                        <div>
                            <h3>Barra de anuncios</h3>
                            <p>Muestra mensajes destacados en la parte superior.</p>
                        </div>
                    </div>
                </div>

                @include('admin.partials.ai-content-tools', ['aiStore' => $store, 'aiContext' => 'announcements'])

                <div class="catalog-settings-grid">
                    <div class="catalog-field">
                        <label for="free_shipping_minimum">Envío gratis desde</label>
                        <input id="free_shipping_minimum" type="number" name="free_shipping_minimum" value="{{ old('free_shipping_minimum', $store->free_shipping_minimum) }}" min="0" step="1000" placeholder="Ej: 150000">
                        <small>Si agregas un monto, se mostrará automáticamente como aviso superior.</small>
                    </div>
                    <div class="catalog-field catalog-field--full">
                        <span>Avisos rotativos</span>
                        <div class="catalog-number-list">
                            @for($announcementIndex = 0; $announcementIndex < 5; $announcementIndex++)
                                <label class="catalog-number-row">
                                    <span class="catalog-number-badge">{{ $announcementIndex + 1 }}</span>
                                    <input
                                        type="text"
                                        name="announcement_items[{{ $announcementIndex }}][text]"
                                        value="{{ old('announcement_items.' . $announcementIndex . '.text', $announcementTexts[$announcementIndex] ?? '') }}"
                                        maxlength="140"
                                        placeholder="{{ ['10% OFF pagando por transferencia', 'Entregas hoy hasta las 6:00 p.m.', 'Recoge en tienda sin costo adicional', 'Aceptamos Nequi, Daviplata y efectivo', 'Pedidos personalizados por WhatsApp'][$announcementIndex] }}"
                                    >
                                </label>
                            @endfor
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <section class="catalog-settings-card">
            <div class="catalog-card-head">
                <div class="catalog-card-title">
                    <span class="catalog-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21v-7"></path><path d="M4 10V3"></path><path d="M12 21v-9"></path><path d="M12 8V3"></path><path d="M20 21v-5"></path><path d="M20 12V3"></path><path d="M2 14h4"></path><path d="M10 8h4"></path><path d="M18 16h4"></path></svg>
                    </span>
                    <div>
                        <h3>Opciones</h3>
                        <p>Configura el comportamiento de tu tienda.</p>
                    </div>
                </div>
            </div>

            @if($store->allowsFullCustomization())
                <div class="catalog-option-row">
                    <span class="catalog-card-icon" aria-hidden="true">↕</span>
                    <div class="catalog-field">
                        <label for="responsive_product_columns">Columnas de productos en responsive</label>
                        <select id="responsive_product_columns" name="responsive_product_columns">
                            <option value="1" @selected((int) old('responsive_product_columns', $store->responsive_product_columns ?? 2) === 1)>1 columna</option>
                            <option value="2" @selected((int) old('responsive_product_columns', $store->responsive_product_columns ?? 2) === 2)>2 columnas</option>
                            <option value="3" @selected((int) old('responsive_product_columns', $store->responsive_product_columns ?? 2) === 3)>3 columnas</option>
                        </select>
                        <small>Elige cómo se acomodan los productos en pantallas pequeñas.</small>
                    </div>
                </div>
                <div class="catalog-toggle-row" style="margin-top:12px;">
                    <div>
                        <strong>Botón sobre la portada</strong>
                        <p class="catalog-help" style="margin:4px 0 0;">Muestra un llamado a la acción hacia todos los productos.</p>
                    </div>
                    <label class="catalog-switch">
                        <input type="checkbox" name="show_hero_products_action" value="1" @checked((bool) old('show_hero_products_action', $store->show_hero_products_action ?? false))>
                        <i></i>
                    </label>
                </div>
            @endif

            <div class="catalog-toggle-row" style="margin-top:12px;">
                <div>
                    <strong>Mostrar horario de atención</strong>
                    <p class="catalog-help" style="margin:4px 0 0;">Activa este campo si quieres mostrar horarios en la tienda.</p>
                </div>
                <label class="catalog-switch">
                    <input type="checkbox" data-toggle-target="business_hours">
                    <i></i>
                </label>
            </div>
            <div class="catalog-field" style="margin-top:12px;" data-optional-block="business_hours">
                <label for="business_hours">Horario de atención</label>
                <textarea id="business_hours" name="business_hours" placeholder="Ej: Lunes a viernes 8:00 AM - 6:00 PM">{{ old('business_hours', $store->business_hours) }}</textarea>
            </div>
        </section>

        @if(\App\Models\Store::supportsShippingMethodsColumn())
            <section class="catalog-settings-card">
                <div class="catalog-card-head">
                    <div class="catalog-card-title">
                        <span class="catalog-card-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 17h4V5H2v12h3"></path><path d="M14 17h1m4 0h3v-6h-3l-2-4h-3v10h1"></path><circle cx="7.5" cy="17.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle></svg>
                        </span>
                        <div>
                            <h3>Envíos</h3>
                            <p>Costos por ciudad y métodos manuales.</p>
                        </div>
                    </div>
                    @unless($store->allowsShippingMethods())
                        <span class="catalog-pro-pill">Pro</span>
                    @endunless
                </div>

                @if($store->allowsShippingMethods())
                    @if(\App\Models\Store::supportsLocalDeliveryColumns())
                        <div class="catalog-settings-grid catalog-settings-grid--three">
                            <div class="catalog-field">
                                <label for="local_delivery_area">Ciudad local</label>
                                @if($colombiaLocations->isNotEmpty())
                                    <select id="local_delivery_area" name="local_delivery_city_code">
                                        <option value="">Selecciona ciudad</option>
                                        @foreach($colombiaLocations->groupBy('department_name') as $departmentName => $locations)
                                            <optgroup label="{{ $departmentName }}">
                                                @foreach($locations as $location)
                                                    <option value="{{ $location->city_code }}" @selected(old('local_delivery_city_code', $store->local_delivery_city_code) === $location->city_code || old('local_delivery_area', $store->local_delivery_area) === $location->city_name)>{{ $location->city_name }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                @else
                                    <input id="local_delivery_area" type="text" name="local_delivery_area" value="{{ old('local_delivery_area', $store->local_delivery_area) }}" maxlength="120" placeholder="Ej: Cali">
                                @endif
                            </div>
                            <div class="catalog-field">
                                <label for="local_delivery_cost">Precio local</label>
                                <input id="local_delivery_cost" type="number" name="local_delivery_cost" value="{{ old('local_delivery_cost', $store->local_delivery_cost) }}" min="0" step="1" placeholder="5000">
                            </div>
                            <div class="catalog-field">
                                <label for="outside_delivery_cost">Precio fuera de ciudad</label>
                                <input id="outside_delivery_cost" type="number" name="outside_delivery_cost" value="{{ old('outside_delivery_cost', $store->outside_delivery_cost) }}" min="0" step="1" placeholder="10000">
                            </div>
                        </div>
                    @endif

                    <div class="catalog-field catalog-field--full" style="margin-top:18px;">
                        <span>Métodos manuales</span>
                        <div class="catalog-shipping-list">
                            @for($shippingIndex = 0; $shippingIndex < 5; $shippingIndex++)
                                @php($shippingMethod = $shippingMethods[$shippingIndex] ?? [])
                                <div class="catalog-shipping-row">
                                    <span class="catalog-number-badge">{{ $shippingIndex + 1 }}</span>
                                    <input type="text" name="shipping_methods[{{ $shippingIndex }}][name]" value="{{ old('shipping_methods.' . $shippingIndex . '.name', $shippingMethod['name'] ?? '') }}" maxlength="80" placeholder="{{ ['Domicilio local', 'Envío nacional', 'Recoger en tienda', 'Mensajería express', 'Contra entrega'][$shippingIndex] }}">
                                    <input type="number" name="shipping_methods[{{ $shippingIndex }}][cost]" value="{{ old('shipping_methods.' . $shippingIndex . '.cost', $shippingMethod['cost'] ?? '') }}" min="0" step="1" placeholder="Costo">
                                </div>
                            @endfor
                        </div>
                    </div>
                @else
                    <p class="catalog-help">Actualiza a Pro o Premium para ofrecer costos de envío y opciones como domicilio local, envío nacional o recogida en tienda.</p>
                @endif
            </section>
        @endif

        <?php
            $supportsCheckoutFields = \App\Models\Store::supportsCheckoutFieldsColumn();
            $checkoutFieldDefinitions = $supportsCheckoutFields ? \App\Models\Store::checkoutFieldDefinitions() : [];
            $checkoutFieldsInput = $supportsCheckoutFields ? old('checkout_fields', $store->checkoutFields()) : [];
        ?>
        <?php if ($supportsCheckoutFields): ?>
            <section class="catalog-settings-card">
                <div class="catalog-card-head">
                    <div class="catalog-card-title">
                        <span class="catalog-card-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                        </span>
                        <div>
                            <h3>Campos del checkout</h3>
                            <p>Reduce fricción mostrando solo los datos que necesitas para procesar pedidos.</p>
                        </div>
                    </div>
                </div>

                <div class="catalog-toggle-row" style="margin-bottom:12px;">
                    <div>
                        <strong>Campos fijos</strong>
                        <p class="catalog-help" style="margin:4px 0 0;">Nombre y WhatsApp siempre se solicitan para poder confirmar el pedido. Si tienes envío local, ciudad y departamento también se exigen para calcular el costo.</p>
                    </div>
                </div>

                <div class="catalog-checkout-grid">
                    <?php foreach ($checkoutFieldDefinitions as $fieldKey => $fieldDefinition): ?>
                        <?php
                            $fieldState = $checkoutFieldsInput[$fieldKey] ?? $store->checkoutField($fieldKey);
                            $fieldEnabled = (bool) ($fieldState['enabled'] ?? false);
                            $fieldRequired = $fieldEnabled && (bool) ($fieldState['required'] ?? false);
                            $forcedByShipping = $fieldKey === 'city' && $store->localDeliveryEnabled();
                        ?>
                        <div class="catalog-checkout-item">
                            <div>
                                <strong>{{ $fieldDefinition['label'] }}</strong>
                                <small>{{ $forcedByShipping ? 'Obligatorio porque tienes envío local/fuera de ciudad activo.' : $fieldDefinition['description'] }}</small>
                            </div>
                            <label class="catalog-checkout-toggle">
                                <input type="hidden" name="checkout_fields[{{ $fieldKey }}][enabled]" value="{{ $forcedByShipping ? '1' : '0' }}">
                                <input type="checkbox" name="checkout_fields[{{ $fieldKey }}][enabled]" value="1" {{ ($fieldEnabled || $forcedByShipping) ? 'checked' : '' }} {{ $forcedByShipping ? 'disabled' : '' }}>
                                Mostrar
                            </label>
                            <label class="catalog-checkout-toggle">
                                <input type="hidden" name="checkout_fields[{{ $fieldKey }}][required]" value="{{ $forcedByShipping ? '1' : '0' }}">
                                <input type="checkbox" name="checkout_fields[{{ $fieldKey }}][required]" value="1" {{ ($fieldRequired || $forcedByShipping) ? 'checked' : '' }} {{ $forcedByShipping ? 'disabled' : '' }}>
                                Obligatorio
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        @if($store->isReservationStore())
            <section class="catalog-settings-card">
                <div class="catalog-card-head">
                    <div class="catalog-card-title">
                        <span class="catalog-card-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4"></path><path d="M8 2v4"></path><path d="M3 10h18"></path></svg>
                        </span>
                        <div>
                            <h3>Reservas</h3>
                            <p>Define días y horas disponibles.</p>
                        </div>
                    </div>
                </div>
                <div class="catalog-check-grid">
                    @foreach(\App\Models\Store::reservationDayOptions() as $dayValue => $dayLabel)
                        <label class="catalog-check">
                            <input type="checkbox" name="reservation_available_days[]" value="{{ $dayValue }}" @checked(in_array($dayValue, $selectedReservationDays, true))>
                            {{ $dayLabel }}
                        </label>
                    @endforeach
                </div>
                <div class="catalog-settings-grid" style="margin-top:16px;">
                    <div class="catalog-field">
                        <label for="reservation_time_start">Hora inicial</label>
                        <input id="reservation_time_start" type="time" name="reservation_time_start" value="{{ old('reservation_time_start', $store->reservation_time_start) }}">
                    </div>
                    <div class="catalog-field">
                        <label for="reservation_time_end">Hora final</label>
                        <input id="reservation_time_end" type="time" name="reservation_time_end" value="{{ old('reservation_time_end', $store->reservation_time_end) }}">
                    </div>
                </div>
            </section>
        @endif

        @if(\App\Models\Store::supportsTermsAcceptanceColumns())
            <section class="catalog-settings-card">
                <div class="catalog-card-head">
                    <div class="catalog-card-title">
                        <span class="catalog-card-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><path d="M14 2v6h6"></path><path d="M8 13h8"></path><path d="M8 17h8"></path></svg>
                        </span>
                        <div>
                            <h3>Páginas legales</h3>
                            <p>Información legal para tus clientes.</p>
                        </div>
                    </div>
                </div>

                <div class="catalog-toggle-row">
                    <div>
                        <strong>Aceptación en checkout</strong>
                        <p class="catalog-help" style="margin:4px 0 0;">Solicita que el cliente acepte condiciones antes de enviar el pedido o pagar.</p>
                    </div>
                    <label class="catalog-switch">
                        <input type="checkbox" name="require_terms_acceptance" value="1" @checked(old('require_terms_acceptance', $store->require_terms_acceptance))>
                        <i></i>
                    </label>
                </div>

                <div class="catalog-settings-grid" style="margin-top:18px;">
                    <div class="catalog-field">
                        <label for="terms_title">Texto del checkbox</label>
                        <input id="terms_title" type="text" name="terms_title" value="{{ old('terms_title', $store->terms_title) }}" maxlength="120" placeholder="Acepto los términos y condiciones">
                    </div>
                    <div class="catalog-field">
                        <label for="terms_version">Versión</label>
                        <input id="terms_version" type="text" name="terms_version" value="{{ old('terms_version', $store->terms_version) }}" maxlength="80" placeholder="v1">
                    </div>
                    <div class="catalog-field catalog-field--full">
                        <label for="terms_content">Términos y condiciones</label>
                        <textarea id="terms_content" name="terms_content" maxlength="5000" placeholder="Escribe aquí tus términos y condiciones...">{{ old('terms_content', $store->terms_content) }}</textarea>
                    </div>
                    <div class="catalog-field catalog-field--full">
                        <label for="terms_url">Enlace externo opcional</label>
                        <input id="terms_url" type="url" name="terms_url" value="{{ old('terms_url', $store->terms_url) }}" maxlength="255" placeholder="https://tu-dominio.com/términos">
                    </div>
                </div>
            </section>
        @endif

        <section class="catalog-settings-card">
            <div class="catalog-card-head">
                <div class="catalog-card-title">
                    <span class="catalog-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9Z"></path><path d="M13 2v7h7"></path></svg>
                    </span>
                    <div>
                        <h3>Información del negocio</h3>
                        <p>Textos para la página de nosotros.</p>
                    </div>
                </div>
            </div>
            <div class="catalog-settings-grid">
                <div class="catalog-field">
                    <label for="mission">Misión</label>
                    <textarea id="mission" name="mission" placeholder="Que hace hoy la tienda y para que existe">{{ old('mission', $store->mission) }}</textarea>
                </div>
                <div class="catalog-field">
                    <label for="vision">Visión</label>
                    <textarea id="vision" name="vision" placeholder="Hacia donde quiere crecer la tienda">{{ old('vision', $store->vision) }}</textarea>
                </div>
            </div>
        </section>

        @if(\App\Models\Store::supportsMetaPixelColumn())
            <section class="catalog-settings-card">
                <div class="catalog-card-head">
                    <div class="catalog-card-title">
                        <span class="catalog-card-icon" aria-hidden="true">PX</span>
                        <div>
                            <h3>Meta Pixel</h3>
                            <p>Medición de visitas y eventos de la tienda.</p>
                        </div>
                    </div>
                    <span class="catalog-pro-pill">Premium</span>
                </div>
                @if($store->allowsMetaPixel())
                    <div class="catalog-field">
                        <label for="meta_pixel_id">Pixel ID</label>
                        <input id="meta_pixel_id" type="text" inputmode="numeric" pattern="[0-9]*" name="meta_pixel_id" value="{{ old('meta_pixel_id', $store->meta_pixel_id) }}" maxlength="50" placeholder="Ej: 123456789012345">
                        <small>Pega solo el ID numerico del pixel.</small>
                    </div>
                @else
                    <p class="catalog-help">Actualiza a Premium para activar Meta Pixel.</p>
                @endif
            </section>
        @endif

        <div class="catalog-actions">
            <div class="catalog-actions-inner">
                <span class="catalog-help">Los cambios pueden tardar unos segundos en verse en tu tienda.</span>
                <div class="catalog-actions-buttons">
                    <a href="/dashboard" class="btn btn-secondary">Cancelar</a>
                    <button class="btn" type="submit">Guardar cambios</button>
                </div>
            </div>
        </div>
    </form>
</div>

@if($store->allowsAiContent())
    <script src="{{ asset('js/admin-ai-content.js') }}?v={{ filemtime(public_path('js/admin-ai-content.js')) }}" defer></script>
@endif

<script>
    (() => {
        document.querySelectorAll('[data-copy-store-url]').forEach((button) => {
            button.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(button.dataset.copyStoreUrl || '');
                    const original = button.textContent;
                    button.textContent = 'Copiado';
                    window.setTimeout(() => button.textContent = original, 1600);
                } catch (error) {
                    button.textContent = 'Copia manual';
                }
            });
        });

        const fontSelect = document.getElementById('font_family');
	        document.querySelectorAll('[data-font-value]').forEach((button) => {
	            button.addEventListener('click', () => {
	                if (fontSelect) {
	                    fontSelect.value = button.dataset.fontValue;
	                }

                document.querySelectorAll('[data-font-value]').forEach((item) => {
                    item.classList.toggle('is-selected', item === button);
                });
	            });
	        });

	        const brandInput = document.querySelector('[data-theme-color-input="brand"]');
	        const backgroundInput = document.querySelector('[data-theme-color-input="background"]');
	        const textInput = document.querySelector('[data-theme-color-input="text"]');
	        const paletteButtons = document.querySelectorAll('[data-palette-brand]');

	        const normalizeColor = (value) => String(value || '').trim().toLowerCase();
	        const syncSelectedPalette = () => {
	            paletteButtons.forEach((button) => {
	                const isSelected = normalizeColor(button.dataset.paletteBrand) === normalizeColor(brandInput?.value)
	                    && normalizeColor(button.dataset.paletteBackground) === normalizeColor(backgroundInput?.value);

	                button.classList.toggle('is-selected', isSelected);
	            });
	        };

	        paletteButtons.forEach((button) => {
	            button.addEventListener('click', () => {
	                if (brandInput) brandInput.value = button.dataset.paletteBrand;
	                if (backgroundInput) backgroundInput.value = button.dataset.paletteBackground;
	                if (textInput) textInput.value = button.dataset.paletteText;

	                syncSelectedPalette();
	            });
	        });

	        brandInput?.addEventListener('input', syncSelectedPalette);
	        backgroundInput?.addEventListener('input', syncSelectedPalette);
	        syncSelectedPalette();

	        document.querySelectorAll('[data-reset-theme]').forEach((button) => {
	            button.addEventListener('click', () => {
	                if (brandInput) brandInput.value = '#111111';
	                if (backgroundInput) backgroundInput.value = '#ffffff';
	                if (textInput) textInput.value = '#111111';
	                syncSelectedPalette();
	            });
	        });

        document.querySelectorAll('[data-toggle-target]').forEach((toggle) => {
            const block = document.querySelector(`[data-optional-block="${toggle.dataset.toggleTarget}"]`);
            const textarea = block?.querySelector('textarea');

            if (! block) {
                return;
            }

            const sync = () => {
                block.hidden = !toggle.checked;
                if (! toggle.checked && textarea) {
                    textarea.value = '';
                }
            };

            toggle.checked = Boolean(textarea?.value?.trim());
            toggle.addEventListener('change', sync);
            sync();
        });
    })();
</script>
@endsection
