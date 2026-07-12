@php
    $portfolioStores = $portfolioStores ?? collect();
    $proofStores = $proofStores ?? collect();
    $testimonials = $testimonials ?? collect();
    $hasPortfolio = $portfolioStores->isNotEmpty();
    $hasTestimonials = $testimonials->isNotEmpty();
    $landingTitle = 'Vendly | Tiendas online listas para vender';
    $landingDescription = 'Vendly crea tiendas online profesionales para negocios que quieren mostrar productos, recibir pedidos y vender por WhatsApp.';
    $landingImage = asset('images/vendly-whatsapp-dark.png');
    $landingWhatsappUrl = 'https://wa.me/573170613664?text=' . rawurlencode('Hola, vengo desde la landing page de Vendly y quiero m�s informaci�n para crear mi tienda.');
    $landingCssPath = public_path('css/landing.css');
    $landingJsPath = public_path('js/landing.js');
    $landingCssVersion = file_exists($landingCssPath) ? filemtime($landingCssPath) : null;
    $landingJsVersion = file_exists($landingJsPath) ? filemtime($landingJsPath) : null;
    $landingCssUrl = asset('css/landing.css') . ($landingCssVersion ? '?v=' . $landingCssVersion : '');

    $features = [
        ['icon' => 'catalog', 'title' => 'Cat�logo profesional', 'copy' => 'Muestra tus productos con estilo y genera confianza desde el primer vistazo.'],
        ['icon' => 'whatsapp', 'title' => 'Pedidos por WhatsApp', 'copy' => 'Tus clientes compran por el canal que ya usan todos los d�as.'],
        ['icon' => 'sparkles', 'title' => 'IA que te ayuda a vender', 'copy' => 'Genera descripciones, t�tulos, etiquetas y avisos para impulsar tu tienda.'],
        ['icon' => 'chart', 'title' => 'Panel inteligente', 'copy' => 'Gestiona pedidos, productos, clientes y estad�sticas desde un solo lugar.'],
        ['icon' => 'share', 'title' => 'Comparte sin l�mites', 'copy' => 'Comparte tu tienda en redes, WhatsApp, Instagram y campa�as.'],
    ];

    $steps = [
        ['number' => '1', 'title' => 'Crea tu tienda', 'copy' => 'Registra tu negocio y configura tu cat�logo en minutos.'],
        ['number' => '2', 'title' => 'Comparte por WhatsApp', 'copy' => 'Envia tu cat�logo a clientes y empieza a recibir pedidos claros.'],
        ['number' => '3', 'title' => 'Gestiona y vende m�s', 'copy' => 'Administra pedidos, productos y clientes desde tu panel.'],
    ];

    $plans = [
        [
            'eyebrow' => 'Plan 01',
            'name' => 'B�sico',
            'summary' => 'Ideal para empezar',
            'badge' => 'Inicio simple',
            'price' => '$0',
            'button' => 'Comenzar gratis',
            'features' => [
                '1 tienda',
                'Cat�logo b�sico',
                'Productos publicados',
                'Sin categor�as',
                'Pedidos por WhatsApp',
                'Carrito por WhatsApp',
                'Logo y portada',
                'Personalizaci�n b�sica',
                'Sin avisos superiores',
                'L�mite de 20 productos',
                'Soporte b�sico',
            ],
        ],
        [
            'eyebrow' => 'Plan 02',
            'name' => 'Pro',
            'summary' => 'Para negocios en crecimiento',
            'badge' => 'M�s popular',
            'price' => '$10.000',
            'button' => 'Probar Pro gratis',
            'features' => [
                'Todo lo del Starter',
                'Todo lo del B�sico',
                'Cat�logo ilimitado',
                'Categor�as',
                'Panel avanzado',
                'Env�os y m�todos de pago',
                'Varios avisos superiores rotativos',
                'Estad�stica de visitas',
                'Galer�a de im�genes por producto',
                'L�mite de 100 productos',
                'Personalizaci�n completa',
                'Soporte prioritario',
            ],
        ],
        [
            'eyebrow' => 'Plan 03',
            'name' => 'Premium',
            'summary' => 'Para marcas que escalan',
            'badge' => 'M�s completo',
            'price' => '$25.000',
            'button' => 'Probar Premium',
            'features' => [
                'Todo lo del plan Pro',
                'M�ltiples tiendas',
                'Integraciones avanzadas',
                'IA avanzada',
                'Dise�o personalizado',
                'Dominio personalizado',
                'Pixel / Analytics',
                'Cupones de descuento',
                'Promociones avanzadas',
                'Reportes avanzados',
                'Prioridad de soporte',
                'Primeros en ver actualizaciones del sistema',
            ],
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $landingTitle }}</title>
    <meta name="description" content="{{ $landingDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $landingTitle }}">
    <meta property="og:description" content="{{ $landingDescription }}">
    <meta property="og:image" content="{{ $landingImage }}">
    <meta property="og:url" content="{{ url('/') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $landingTitle }}">
    <meta name="twitter:description" content="{{ $landingDescription }}">
    <meta name="twitter:image" content="{{ $landingImage }}">
    <link rel="icon" type="image/png" href="{{ asset('images/vendly-whatsapp-dark.png') }}">
    <link rel="shortcut icon" href="{{ asset('images/vendly-whatsapp-dark.png') }}">
    @include('landing.partials.meta-pixel')
    @include('landing.partials.critical-css')
    <link rel="preload" href="{{ $landingCssUrl }}" as="style">
    <link rel="stylesheet" href="{{ $landingCssUrl }}">
</head>
<body class="landing-page">
    @include('landing.partials.meta-pixel-noscript')
    @include('landing.partials.nav')

    <main>
        @include('landing.partials.hero', ['hasPortfolio' => $hasPortfolio])
        @include('landing.partials.portfolio', ['portfolioStores' => $portfolioStores, 'proofStores' => $proofStores, 'hasPortfolio' => $hasPortfolio])
        @include('landing.partials.process', ['steps' => $steps])
        @include('landing.partials.features', ['features' => $features])
        @include('landing.partials.plans', ['plans' => $plans])
        @includeWhen($hasTestimonials, 'landing.partials.testimonials', ['testimonials' => $testimonials])
        @include('landing.partials.cta')
    </main>

    @include('landing.partials.footer')
    @include('landing.partials.whatsapp')
    <script src="{{ asset('js/landing.js') }}{{ $landingJsVersion ? '?v=' . $landingJsVersion : '' }}" defer></script>
</body>
</html>
