@extends('layouts.admin')

@section('meta_title', 'Vendly - Métodos de pago.')

@section('content')
@php
    $whatsappCheckoutEnabled = $store->acceptsWhatsappCheckout();
    $whatsappUnavailable = blank($store->whatsapp);
    $mercadoPagoConnected = $mercadoPagoAccount?->isConnected();
    $mercadoPagoExpired = (($mercadoPagoAccount?->status) === \App\Models\StorePaymentAccount::STATUS_EXPIRED)
        || ($mercadoPagoAccount?->expires_at && $mercadoPagoAccount->expires_at->isPast());
    $mercadoPagoDisconnected = ($mercadoPagoAccount?->status) === \App\Models\StorePaymentAccount::STATUS_DISCONNECTED;
    $mercadoPagoCanToggle = $mercadoPagoAccount
        && ! $mercadoPagoExpired
        && (! $mercadoPagoAccount->expires_at || $mercadoPagoAccount->expires_at->isFuture());
    $wompiReady = $wompiAccount?->isWompiReady();
@endphp

<style>
    .payments-panel {
        display: grid;
        gap: 24px;
    }

    .payments-panel__hero {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(260px, 470px);
        gap: 24px;
        align-items: end;
    }

    .payments-panel__eyebrow {
        display: inline-flex;
        margin-bottom: 10px;
        color: #049d94;
        font-size: 13px;
        font-weight: 900;
        letter-spacing: 2px;
        text-transform: uppercase;
    }

    .payments-panel__title {
        margin: 0;
        color: #061126;
        font-size: clamp(34px, 5vw, 56px);
        line-height: 0.98;
        letter-spacing: 0;
    }

    .payments-panel__subtitle {
        margin: 12px 0 0;
        max-width: 720px;
        color: #61708a;
        font-size: 20px;
        line-height: 1.45;
    }

    .payments-panel__notice {
        display: grid;
        grid-template-columns: 58px 1fr;
        gap: 18px;
        align-items: center;
        padding: 22px;
        border: 1px solid rgba(15, 118, 110, 0.1);
        border-radius: 18px;
        background: linear-gradient(135deg, #e9fbff 0%, #eef8ff 100%);
        color: #20324c;
        box-shadow: 0 18px 42px rgba(14, 116, 144, 0.08);
    }

    .payments-panel__notice-icon,
    .payment-method__icon,
    .payment-method__action-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .payments-panel__notice-icon {
        width: 58px;
        height: 58px;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.72);
        color: #0ea5e9;
    }

    .payments-panel__notice strong,
    .payment-method__action-title {
        display: block;
        color: #071225;
        font-size: 17px;
        font-weight: 900;
    }

    .payments-panel__notice span,
    .payment-method__action-copy {
        display: block;
        margin-top: 4px;
        color: #60708b;
        line-height: 1.45;
    }

    .payment-methods {
        display: grid;
        gap: 22px;
    }

    .payment-method {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(250px, 330px);
        gap: 28px;
        align-items: stretch;
        padding: 30px 36px 30px 30px;
        border: 1px solid #e3e8f0;
        border-radius: 18px;
        background:
            radial-gradient(circle at 96% 12%, rgba(6, 182, 212, 0.08), transparent 28%),
            #ffffff;
        box-shadow: 0 22px 52px rgba(15, 23, 42, 0.08);
    }

    .payment-method__main {
        display: grid;
        grid-template-columns: 116px minmax(0, 1fr);
        gap: 28px;
        align-items: center;
        min-width: 0;
    }

    .payment-method__icon {
        width: 116px;
        height: 116px;
        border-radius: 20px;
        background: #ecfdf5;
        color: #22c55e;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.8);
    }

    .payment-method__icon--mercadopago {
        background: #e8f7ff;
        color: #168fd4;
    }

    .payment-method__icon--wompi {
        background: #f4ecff;
        color: #111827;
    }

    .payment-method__icon img,
    .payment-method__icon svg {
        width: 62px;
        height: 62px;
    }

    .payment-method__icon img {
        object-fit: contain;
    }

    .payment-method__heading {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }

    .payment-method__title {
        margin: 0;
        color: #071225;
        font-size: 28px;
        line-height: 1.1;
        letter-spacing: 0;
    }

    .payment-method__copy {
        margin: 14px 0 0;
        color: #60708b;
        font-size: 16px;
        line-height: 1.5;
    }

    .payment-method__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 14px;
    }

    .payment-method__meta-item {
        display: inline-flex;
        align-items: center;
        min-height: 30px;
        padding: 6px 11px;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        background: #f8fafc;
        color: #334155;
        font-size: 13px;
        font-weight: 800;
    }

    .payment-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 32px;
        padding: 7px 14px;
        border-radius: 999px;
        background: #e6f8ef;
        color: #047857;
        font-size: 14px;
        font-weight: 900;
    }

    .payment-status::before {
        content: "";
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: currentColor;
    }

    .payment-status--muted {
        background: #eef2f7;
        color: #64748b;
    }

    .payment-status--warning {
        background: #fff3d6;
        color: #c45b00;
    }

    .payment-method__chips {
        display: flex;
        flex-wrap: wrap;
        gap: 18px 24px;
        margin-top: 28px;
    }

    .payment-chip {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: #42506a;
        font-size: 15px;
        font-weight: 700;
    }

    .payment-chip svg {
        width: 22px;
        height: 22px;
        color: #34435d;
    }

    .payment-method__side {
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 14px;
        min-width: 0;
        padding-left: 34px;
        border-left: 1px solid #dfe6ef;
    }

    .payment-method__state {
        display: flex;
        gap: 16px;
        align-items: center;
    }

    .payment-method__action-icon {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background: #e8fbf8;
        color: #049d94;
    }

    .payment-method__action-icon--inactive {
        background: #edf2f7;
        color: #64748b;
    }

    .payment-method__action-icon--warning {
        background: #fff0df;
        color: #f97316;
    }

    .payment-toggle-form {
        display: grid;
        gap: 14px;
    }

    .payment-switch {
        position: relative;
        display: inline-flex;
        align-items: center;
        width: 88px;
        height: 42px;
        border-radius: 999px;
        background: #99a3b2;
        cursor: default;
        transition: background 0.2s ease, opacity 0.2s ease;
    }

    .payment-switch input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .payment-switch__thumb {
        position: absolute;
        left: 5px;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #ffffff;
        color: #7a8798;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.18);
        transition: transform 0.2s ease, color 0.2s ease;
    }

    .payment-switch input:checked + .payment-switch__thumb {
        transform: translateX(44px);
        color: #049d94;
    }

    .payment-switch:has(input:checked) {
        background: linear-gradient(135deg, #0fbaa9 0%, #00a99d 100%);
    }

    .payment-switch--disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }

    .payment-method__sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    .payment-action-button {
        width: 100%;
        min-height: 50px;
        border: 1px solid #b7c4d7;
        border-radius: 12px;
        background: #ffffff;
        color: #263550;
        font-weight: 900;
        font-size: 15px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        text-decoration: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .payment-action-button:hover {
        transform: translateY(-1px);
        border-color: #049d94;
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.1);
    }

    .payment-action-button--primary {
        border-color: rgba(4, 157, 148, 0.24);
        background: #e9fbfa;
        color: #047f78;
    }

    .payment-action-button--orange {
        border-color: #ff6a00;
        background: #ff6a00;
        color: #ffffff;
    }

    .payment-action-button:disabled {
        opacity: 0.58;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .payment-method__details {
        grid-column: 1 / -1;
        margin-top: 4px;
        padding-top: 24px;
        border-top: 1px solid #e7edf4;
    }

    .payment-config {
        margin: 0;
    }

    .payment-config summary {
        cursor: pointer;
        color: #047f78;
        font-weight: 900;
    }

    .payment-config[open] summary {
        margin-bottom: 18px;
    }

    .payment-config__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .payment-config__actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 16px;
    }

    .payment-config .field-wrap {
        margin: 0;
    }

    .payment-config .input {
        margin: 0;
    }

    @media (max-width: 1100px) {
        .payments-panel__hero,
        .payment-method {
            grid-template-columns: 1fr;
        }

        .payment-method__side {
            padding: 22px 0 0;
            border-left: 0;
            border-top: 1px solid #dfe6ef;
        }
    }

    @media (max-width: 760px) {
        .payments-panel__hero {
            gap: 16px;
        }

        .payments-panel__subtitle {
            font-size: 16px;
        }

        .payments-panel__notice,
        .payment-method__main {
            grid-template-columns: 1fr;
        }

        .payments-panel__notice {
            padding: 18px;
        }

        .payment-method {
            padding: 22px;
            border-radius: 16px;
        }

        .payment-method__main {
            gap: 18px;
        }

        .payment-method__icon {
            width: 82px;
            height: 82px;
            border-radius: 18px;
        }

        .payment-method__icon img,
        .payment-method__icon svg {
            width: 46px;
            height: 46px;
        }

        .payment-method__title {
            font-size: 24px;
        }

        .payment-method__chips {
            gap: 14px;
            margin-top: 20px;
        }

        .payment-method__state {
            align-items: flex-start;
        }

        .payment-config__grid {
            grid-template-columns: 1fr;
        }

        .payment-config__actions .payment-action-button {
            width: 100%;
        }
    }
</style>

<div class="payments-panel">
    <div class="payments-panel__hero">
        <div>
            <span class="payments-panel__eyebrow">Pagos y checkout</span>
            <h2 class="payments-panel__title">Métodos de pago</h2>
            <p class="payments-panel__subtitle">Activa los medios que tus clientes podrán usar en checkout.</p>
        </div>

        <div class="payments-panel__notice">
            <span class="payments-panel__notice-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 3l7 3v5c0 4.6-2.8 8.5-7 10-4.2-1.5-7-5.4-7-10V6l7-3Z" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M8.5 12l2.2 2.2 4.8-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <span>
                <strong>Pagos seguros, más ventas</strong>
                <span>Ofrece medios de pago confiables y da más opciones a tus clientes.</span>
            </span>
        </div>
    </div>

    @if (session('success'))
        <div class="flash success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="flash error">{{ session('error') }}</div>
    @endif

    <section class="payment-methods" aria-label="Métodos de pago disponibles">
        <article class="payment-method">
            <div class="payment-method__main">
                <span class="payment-method__icon" aria-hidden="true">
                    <img src="{{ asset('images/icons/payment-whatsapp.svg') }}" alt="">
                </span>

                <div>
                    <div class="payment-method__heading">
                        <h3 class="payment-method__title">Pedido por WhatsApp</h3>
                        @if($whatsappCheckoutEnabled)
                            <span class="payment-status">Activo</span>
                        @elseif($whatsappUnavailable)
                            <span class="payment-status payment-status--warning">Sin número</span>
                        @else
                            <span class="payment-status payment-status--muted">Inactivo</span>
                        @endif
                    </div>
                    <p class="payment-method__copy">Transferencia, Nequi, Daviplata o efectivo según la tienda.</p>
                    <div class="payment-method__meta" aria-label="Datos de WhatsApp">
                        <span class="payment-method__meta-item">Número: {{ $store->whatsapp ?: 'Sin WhatsApp' }}</span>
                        <span class="payment-method__meta-item">{{ $whatsappCheckoutEnabled ? 'Pedido manual activo' : 'Oculto en checkout' }}</span>
                        <span class="payment-method__sr-only">Activar WhatsApp en el checkout</span>
                    </div>

                    <div class="payment-method__chips" aria-label="Medios aceptados por WhatsApp">
                        <span class="payment-chip">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M4 20h16M6 20V9l6-4 6 4v11M9 20v-6h6v6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Transferencia
                        </span>
                        <span class="payment-chip">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M12 3l8 8-8 10-8-10 8-8Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                            Nequi
                        </span>
                        <span class="payment-chip">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M8 3h8a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm2 15h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            Daviplata
                        </span>
                        <span class="payment-chip">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16v10H4V7Zm4 5h.01M16 12h.01M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Efectivo
                        </span>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.payments.whatsapp.update') }}" class="payment-method__side payment-toggle-form">
                @csrf
                <input type="hidden" name="enabled" value="{{ $whatsappCheckoutEnabled ? '0' : '1' }}">
                <div class="payment-method__state">
                    <span class="payment-switch {{ $whatsappUnavailable ? 'payment-switch--disabled' : '' }}" aria-hidden="true">
                        <input type="checkbox" @checked($whatsappCheckoutEnabled) disabled tabindex="-1">
                        <span class="payment-switch__thumb">
                            @if($whatsappCheckoutEnabled)
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M5 12.5l4 4L19 6.5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            @else
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M7 7l10 10M17 7 7 17" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                            @endif
                        </span>
                    </span>
                    <span>
                        <strong class="payment-method__action-title">{{ $whatsappCheckoutEnabled ? 'Activo' : ($whatsappUnavailable ? 'Agrega tu número' : 'Inactivo') }}</strong>
                        <span class="payment-method__action-copy">{{ $whatsappCheckoutEnabled ? 'Tus clientes pueden hacer pedidos por WhatsApp.' : ($whatsappUnavailable ? 'Configura un WhatsApp antes de mostrarlo.' : 'No aparecerá como opción en checkout.') }}</span>
                    </span>
                </div>

                <button type="submit" class="payment-action-button" @disabled($whatsappUnavailable)>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 3v8M7 7a7 7 0 1 0 10 0" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
                    {{ $whatsappCheckoutEnabled ? 'Desactivar' : 'Activar' }}
                </button>

                @if($whatsappUnavailable)
                    <a href="/admin/store-settings" class="payment-action-button payment-action-button--primary">Editar WhatsApp</a>
                @endif
            </form>
        </article>

        <article class="payment-method">
            <div class="payment-method__main">
                <span class="payment-method__icon payment-method__icon--mercadopago" aria-hidden="true">
                    <img src="{{ asset('images/icons/payment-mercadopago.svg') }}" alt="">
                </span>

                <div>
                    <div class="payment-method__heading">
                        <h3 class="payment-method__title">Mercado Pago</h3>
                        @if($mercadoPagoConnected)
                            <span class="payment-status">Activo</span>
                        @elseif($mercadoPagoExpired)
                            <span class="payment-status payment-status--warning">Requiere revisión</span>
                        @elseif($mercadoPagoDisconnected)
                            <span class="payment-status payment-status--muted">Oculto</span>
                        @else
                            <span class="payment-status payment-status--muted">No conectado</span>
                        @endif
                    </div>
                    <p class="payment-method__copy">Tarjetas, PSE y cuenta Mercado Pago.</p>
                    <div class="payment-method__meta" aria-label="Datos de Mercado Pago">
                        <span class="payment-method__meta-item">Estado: {{ $mercadoPagoConnected ? 'Conectado y visible' : ($mercadoPagoExpired ? 'Token vencido' : ($mercadoPagoDisconnected ? 'Oculto en checkout' : 'No conectado')) }}</span>
                        <span class="payment-method__meta-item">{{ $mercadoPagoAccount?->provider_user_id ? 'Cuenta ID ' . $mercadoPagoAccount->provider_user_id : 'Sin cuenta conectada' }}</span>
                        <span class="payment-method__meta-item">Tokens encriptados</span>
                    </div>

                    <div class="payment-method__chips" aria-label="Medios aceptados por Mercado Pago">
                        <span class="payment-chip">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M3 7h18v10H3V7Zm0 3h18M7 15h3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            Tarjetas
                        </span>
                        <span class="payment-chip">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M4 20h16M6 10l6-5 6 5M7 10h10M8 10v7M12 10v7M16 10v7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            PSE
                        </span>
                        <span class="payment-chip">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M5 8h14v10H5V8Zm3 0V6h8v2" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                            Cuenta Mercado Pago
                        </span>
                    </div>
                </div>
            </div>

            <div class="payment-method__side">
                <div class="payment-method__state">
                    <span class="payment-method__action-icon {{ $mercadoPagoConnected ? '' : 'payment-method__action-icon--inactive' }}" aria-hidden="true">
                        @if($mercadoPagoConnected)
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M5 12.5l4 4L19 6.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @else
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M7 7l10 10M17 7 7 17" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                        @endif
                    </span>
                    <span>
                        <strong class="payment-method__action-title">{{ $mercadoPagoConnected ? 'Activo' : ($mercadoPagoExpired ? 'Reconecta tu cuenta' : ($mercadoPagoDisconnected ? 'Oculto' : 'Inactivo')) }}</strong>
                        <span class="payment-method__action-copy">{{ $mercadoPagoConnected ? 'Recibes pagos con tu cuenta conectada.' : ($mercadoPagoExpired ? 'La conexión venció y debe autorizarse de nuevo.' : ($mercadoPagoDisconnected ? 'La cuenta sigue conectada, pero no aparece en checkout.' : 'Actívalo para recibir pagos con Mercado Pago.')) }}</span>
                    </span>
                </div>

                @if($mercadoPagoCanToggle)
                    <form method="POST" action="{{ route('admin.payments.mercadopago.update') }}" class="payment-toggle-form">
                        @csrf
                        <input type="hidden" name="enabled" value="{{ $mercadoPagoConnected ? '0' : '1' }}">
                        <button type="submit" class="payment-action-button {{ $mercadoPagoConnected ? '' : 'payment-action-button--primary' }}">
                            {{ $mercadoPagoConnected ? 'Desactivar' : 'Activar' }}
                        </button>
                    </form>
                @elseif($mercadoPagoAccount)
                    <a href="{{ route('admin.payments.mercadopago.connect') }}" class="payment-action-button payment-action-button--primary">
                        Reconectar Mercado Pago
                    </a>
                @else
                    <a href="{{ route('admin.payments.mercadopago.connect') }}" class="payment-action-button payment-action-button--primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                        Conectar Mercado Pago
                    </a>
                @endif
            </div>
        </article>

        <article class="payment-method">
            <div class="payment-method__main">
                <span class="payment-method__icon payment-method__icon--wompi" aria-hidden="true">
                    <img src="{{ asset('images/icons/payment-wompi.svg') }}" alt="">
                </span>

                <div>
                    <div class="payment-method__heading">
                        <h3 class="payment-method__title">Wompi</h3>
                        @if($wompiReady)
                            <span class="payment-status">Activo</span>
                        @else
                            <span class="payment-status payment-status--warning">Requiere configuración</span>
                        @endif
                    </div>
                    <p class="payment-method__copy">Tarjetas, PSE, Nequi y Bancolombia.</p>

                    <div class="payment-method__chips" aria-label="Medios aceptados por Wompi">
                        <span class="payment-chip">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M3 7h18v10H3V7Zm0 3h18M7 15h3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            Tarjetas
                        </span>
                        <span class="payment-chip">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M4 20h16M6 10l6-5 6 5M7 10h10M8 10v7M12 10v7M16 10v7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            PSE
                        </span>
                        <span class="payment-chip">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M12 3l8 8-8 10-8-10 8-8Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                            Nequi
                        </span>
                        <span class="payment-chip">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M5 18h14M7 9h10M8 9v7M12 9v7M16 9v7M4 9l8-5 8 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Bancolombia
                        </span>
                    </div>
                </div>
            </div>

            <div class="payment-method__side">
                <div class="payment-method__state">
                    <span class="payment-method__action-icon {{ $wompiReady ? '' : 'payment-method__action-icon--warning' }}" aria-hidden="true">
                        @if($wompiReady)
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M5 12.5l4 4L19 6.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @else
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M14.7 6.3a4 4 0 0 0-5.1 5.1L4 17v3h3l5.6-5.6a4 4 0 0 0 5.1-5.1l-2.6 2.6-3-3 2.6-2.6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @endif
                    </span>
                    <span>
                        <strong class="payment-method__action-title">{{ $wompiReady ? 'Activo' : 'Conecta tu cuenta' }}</strong>
                        <span class="payment-method__action-copy">{{ $wompiReady ? 'Wompi aparece como opción de pago.' : 'Configura Wompi para comenzar a recibir pagos.' }}</span>
                    </span>
                </div>
            </div>

            <div class="payment-method__details">
                <details class="payment-config" @if($errors->any()) open @endif>
                    <summary>{{ $wompiReady ? 'Editar configuración de Wompi' : 'Conectar Wompi' }}</summary>

                    <form method="POST" action="{{ route('admin.payments.wompi.update') }}" class="settings-form">
                        @csrf

                        <label class="settings-toggle">
                            <input type="checkbox" name="enabled" value="1" @checked($wompiReady)>
                            <span>Activar Wompi en el checkout</span>
                        </label>

                        <div class="payment-config__grid">
                            <label class="field-wrap">
                                <span class="field-label">Modo</span>
                                <select class="input" name="mode">
                                    <option value="sandbox" @selected(($wompiAccount?->mode ?? 'sandbox') === 'sandbox')>Pruebas</option>
                                    <option value="production" @selected(($wompiAccount?->mode ?? 'sandbox') === 'production')>Producción</option>
                                </select>
                            </label>

                            <label class="field-wrap">
                                <span class="field-label">Llave pública</span>
                                <input class="input" type="text" name="public_key" value="{{ old('public_key', $wompiAccount?->public_key) }}" placeholder="pub_test_...">
                            </label>

                            <label class="field-wrap">
                                <span class="field-label">Llave privada</span>
                                <input class="input" type="password" name="private_key" value="" placeholder="{{ $wompiAccount?->private_key ? 'Guardada. Escribe una nueva para cambiarla.' : 'prv_test_...' }}">
                            </label>

                            <label class="field-wrap">
                                <span class="field-label">Secreto de eventos</span>
                                <input class="input" type="password" name="events_secret" value="" placeholder="{{ $wompiAccount?->events_secret ? 'Guardado. Escribe uno nuevo para cambiarlo.' : 'Secreto de eventos' }}">
                            </label>

                            <label class="field-wrap">
                                <span class="field-label">Secreto de integridad</span>
                                <input class="input" type="password" name="integrity_secret" value="" placeholder="{{ $wompiAccount?->integrity_secret ? 'Guardado. Escribe uno nuevo para cambiarlo.' : 'Secreto de integridad' }}">
                            </label>
                        </div>

                        @if ($errors->any())
                            <div class="flash error">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <div class="payment-config__actions">
                            <button type="submit" class="payment-action-button payment-action-button--orange">
                                {{ $wompiReady ? 'Guardar Wompi' : 'Conectar' }}
                            </button>
                        </div>
                    </form>
                </details>
            </div>
        </article>
    </section>
</div>
@endsection
