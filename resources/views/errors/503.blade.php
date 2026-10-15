@php
    $supportPhone = preg_replace('/\D+/', '', (string) config('services.support.whatsapp'));
    $supportUrl = $supportPhone !== ''
        ? 'https://wa.me/' . $supportPhone . '?text=' . rawurlencode('Hola, necesito ayuda con Vendly.')
        : null;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#111111">
    <title>Mantenimiento | Vendly</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/vendly-logo.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/errors.css') }}?v={{ filemtime(public_path('css/errors.css')) }}">
</head>
<body class="error-page maintenance-page">
    <main class="maintenance-shell">
        <div class="maintenance-brand" aria-label="Vendly">
            <span class="maintenance-brand-mark">
                <img src="{{ asset('images/vendly-logo.svg') }}" alt="">
            </span>
            <span>Vendly</span>
        </div>

        <section class="maintenance-content" aria-labelledby="maintenance-title">
            <div class="maintenance-icon" aria-hidden="true">
                <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M29.5 9.5a9 9 0 0 0 10.9 11.1L24 37a5 5 0 0 1-7-7l16.4-16.4A9 9 0 0 0 29.5 9.5Z"></path>
                    <path d="m31 17 4 4"></path>
                    <circle cx="36" cy="36" r="7"></circle>
                    <path d="M36 32.5V36l2.3 1.6"></path>
                </svg>
            </div>

            <div class="maintenance-eyebrow">
                <span></span>
                Mantenimiento programado
            </div>

            <h1 id="maintenance-title">Estamos mejorando Vendly</h1>
            <p>Volvemos en unos minutos. Gracias por tu paciencia.</p>

            <div class="maintenance-actions">
                <a href="{{ url()->current() }}" class="maintenance-primary-link">Volver a intentar</a>
                @if($supportUrl)
                    <a href="{{ $supportUrl }}" class="maintenance-support-link" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11.5a8 8 0 0 1-11.8 7L4 20l1.5-4.1A8 8 0 1 1 20 11.5Z"></path><path d="M8.5 9.5c.2 2.2 1.8 4 4 4.3"></path></svg>
                        ¿Necesitas ayuda? Escríbenos por WhatsApp
                    </a>
                @endif
            </div>
        </section>

        <div class="maintenance-footer">Vendly Suite · Catálogo, carrito y pedidos por WhatsApp</div>
    </main>
</body>
</html>
