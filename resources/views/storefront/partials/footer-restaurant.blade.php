@php
    $restaurantFooterCopy = trim((string) ($store->shop_copy ?: $store->short_description));
    $restaurantWhatsappUrl = $store->whatsappInfoUrl();
    $restaurantFooterCategories = collect($activeCategories ?? [])->take(5)->values();
    $restaurantLogoUrl = $store->logo_image ? asset('storage/' . $store->logo_image) : null;
    $restaurantInitials = collect(explode(' ', trim((string) $store->name)))
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_substr($word, 0, 1))
        ->implode('');
    $restaurantInitials = $restaurantInitials !== '' ? mb_strtoupper($restaurantInitials) : 'R';
    $restaurantSocialLinks = collect([
        ['name' => 'Instagram', 'url' => trim((string) $store->instagram_url), 'label' => 'IG'],
        ['name' => 'Facebook', 'url' => trim((string) $store->facebook_url), 'label' => 'FB'],
        ['name' => 'TikTok', 'url' => trim((string) $store->tiktok_url), 'label' => 'TT'],
    ])->filter(fn ($social) => $social['url'] !== '')->values();
@endphp

<footer class="restaurant-footer" id="restaurantFooter">
    <div class="restaurant-footer-shell">
        <section class="restaurant-footer-cta" aria-label="Haz tu pedido">
            <div>
                <span>Menu digital</span>
                <h2>Listo para pedir tus favoritos?</h2>
                <p>{{ $restaurantFooterCopy !== '' ? $restaurantFooterCopy : 'Elige tus platos, confirma tu pedido y recibe atencion directa por WhatsApp.' }}</p>
            </div>
            <div class="restaurant-footer-cta-actions">
                <a href="{{ $storefrontUrls->products($store) }}">Ver menu</a>
                @if($restaurantWhatsappUrl)
                    <a href="{{ $restaurantWhatsappUrl }}" target="_blank" rel="noopener noreferrer">Pedir por WhatsApp</a>
                @endif
            </div>
        </section>

        <div class="restaurant-footer-main">
            <section class="restaurant-footer-brand" aria-label="{{ $store->name }}">
                <a href="{{ $storefrontUrls->home($store) }}" class="restaurant-footer-logo" aria-label="{{ $store->name }}">
                    @if($restaurantLogoUrl)
                        <img src="{{ $restaurantLogoUrl }}" alt="{{ $store->name }}" loading="lazy" decoding="async">
                    @else
                        <span>{{ $restaurantInitials }}</span>
                    @endif
                </a>
                <strong>{{ $store->name }}</strong>
                <p>{{ $restaurantFooterCopy !== '' ? $restaurantFooterCopy : 'Carta sencilla para vender y recibir pedidos por WhatsApp.' }}</p>

                @if($restaurantSocialLinks->isNotEmpty())
                    <div class="restaurant-footer-socials" aria-label="Redes sociales de {{ $store->name }}">
                        @foreach($restaurantSocialLinks as $social)
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $social['name'] }}">
                                {{ $social['label'] }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            <nav class="restaurant-footer-column" aria-label="Menu">
                <strong>Menu</strong>
                <a href="{{ $storefrontUrls->home($store) }}">Inicio</a>
                <a href="{{ $storefrontUrls->products($store) }}">Todos los platos</a>
                @if($restaurantFooterCategories->isNotEmpty())
                    @foreach($restaurantFooterCategories as $category)
                        <a href="{{ $storefrontUrls->category($store, $category) }}">{{ $category->name }}</a>
                    @endforeach
                @endif
            </nav>

            <nav class="restaurant-footer-column" aria-label="Ayuda">
                <strong>Ayuda</strong>
                @if($restaurantWhatsappUrl)
                    <a href="{{ $restaurantWhatsappUrl }}" target="_blank" rel="noopener noreferrer">WhatsApp</a>
                @endif
                @if($store->location)
                    <span>{{ $store->location }}</span>
                @endif
                @if($store->user?->email)
                    <a href="mailto:{{ $store->user->email }}">{{ $store->user->email }}</a>
                @endif
            </nav>
        </div>

        <div class="restaurant-footer-bottom">
            <p>Copyright © 2026 {{ $store->name }}. Todos los derechos reservados.</p>
            <p>Desarrollado por <a href="https://vendlysuite.com" target="_blank" rel="noopener noreferrer">VendlySuite.com</a></p>
        </div>
    </div>
</footer>

@if($restaurantWhatsappUrl)
    <a
        href="{{ $restaurantWhatsappUrl }}"
        class="store-whatsapp-float"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Contactar por WhatsApp"
    >
        <img src="{{ asset('images/icons/icon-whatsapp.png') }}" alt="" aria-hidden="true">
    </a>
@endif
