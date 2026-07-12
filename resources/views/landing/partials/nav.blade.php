<input class="landing-mobile-toggle" type="checkbox" id="landing-mobile-menu" aria-hidden="true">

<header class="landing-nav">
    <div class="landing-shell landing-nav-inner">
        <label class="landing-menu-open" for="landing-mobile-menu" aria-label="Abrir menú">
            <span></span>
        </label>

        <a href="/" class="brand-link" aria-label="Vendly">
            <img src="{{ asset('images/vendly-whatsapp-dark.png') }}" alt="Vendly">
            <span>vendly</span>
        </a>

        <nav class="landing-menu" aria-label="Navegación principal">
            <a href="#producto">Producto</a>
            <a href="#funciones">Funciones</a>
            <a href="#planes">Precios</a>
            <a href="#portafolio">Portafolio</a>
            <a href="#contacto">Contacto</a>
        </nav>

        <div class="landing-nav-actions" aria-label="Acciones principales">
            <a href="{{ route('login') }}" class="btn btn--ghost btn--sm login-link">Iniciar sesión</a>
            <a href="{{ route('trial-signup.create') }}" class="btn btn--primary btn--sm" data-meta-event="Lead">Probar gratis</a>
        </div>
    </div>
</header>

<label class="landing-mobile-backdrop" for="landing-mobile-menu" aria-label="Cerrar menú"></label>

<aside class="landing-mobile-drawer" aria-label="Menú móvil">
    <div class="mobile-drawer-head">
        <a href="/" class="brand-link" data-menu-close>
            <img src="{{ asset('images/vendly-whatsapp-dark.png') }}" alt="Vendly">
            <span>vendly</span>
        </a>
        <label class="landing-menu-close" for="landing-mobile-menu" aria-label="Cerrar menú"></label>
    </div>

    <nav class="mobile-drawer-menu">
        <a href="#producto" class="is-active" data-menu-close>
            <span class="drawer-icon" aria-hidden="true">&#8962;</span>
            <strong>Inicio</strong>
        </a>
        <a href="#producto" data-menu-close>
            <span class="drawer-icon" aria-hidden="true">&#9713;</span>
            <strong>Producto</strong>
            <em aria-hidden="true">&rsaquo;</em>
        </a>
        <a href="#funciones" data-menu-close>
            <span class="drawer-icon" aria-hidden="true">&#9734;</span>
            <strong>Funciones</strong>
        </a>
        <a href="#planes" data-menu-close>
            <span class="drawer-icon" aria-hidden="true">&#9671;</span>
            <strong>Precios</strong>
        </a>
        <a href="#portafolio" data-menu-close>
            <span class="drawer-icon" aria-hidden="true">&#9635;</span>
            <strong>Ejemplos</strong>
        </a>
        <a href="#contacto" data-menu-close>
            <span class="drawer-icon" aria-hidden="true">&#9636;</span>
            <strong>Contacto</strong>
            <em aria-hidden="true">&rsaquo;</em>
        </a>
    </nav>
</aside>