<section class="landing-hero" id="producto">
    <div class="landing-shell hero-layout">
        <div class="hero-content">
            <div class="hero-badge">
                <span>NUEVO</span>
                IA integrada para potenciar tus ventas
            </div>
            <h1>Crea tu tienda online gratis en menos de 2 minutos.</h1>
            <p>Sin tarjeta de crédito. Sin conocimientos técnicos. Lista para vender hoy.</p>

            <div class="action-row">
                <a href="{{ route('trial-signup.create') }}" class="btn btn--primary" data-meta-event="Lead">Crear mi tienda gratis</a>
                <span class="sr-only">Quiero mi tienda</span>
                <a href="{{ $hasPortfolio ? '#portafolio' : '#funciones' }}" class="btn btn--ghost">Ver tiendas reales</a>
            </div>

            <div class="hero-trust" aria-label="Beneficios de inicio">
                <span>Gratis 7 días</span>
                <span>Cancela cuando quieras</span>
            </div>
        </div>

        @include('landing.partials.hero-phone-demo')
    </div>
</section>
