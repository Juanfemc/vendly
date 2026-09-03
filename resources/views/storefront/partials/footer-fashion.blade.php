@php
    $storeWhatsappUrl = $store->whatsappInfoUrl();
    $storeFooterCopy = trim((string) $store->shop_copy);
    $footerDescription = $storeFooterCopy !== ''
        ? $storeFooterCopy
        : 'Estilos modernos para cada estilo de vida. Descubre calidad y comodidad en un solo lugar.';
    $termsContent = trim(strip_tags((string) $store->terms_content));
    $termsUrl = trim((string) $store->terms_url);
    $hasTerms = $termsContent !== '' || $termsUrl !== '';
    $storeEmail = $store->user?->email;
@endphp

<footer class="fashion-footer">
    <div class="fashion-footer-shell">
        <div class="fashion-footer-main">
            <section class="fashion-footer-brand" aria-label="{{ $store->name }}">
                <h2>{{ $store->name }}</h2>
                <p>{{ $footerDescription }}</p>

                @include('storefront.partials.fashion-social-links', [
                    'class' => 'fashion-footer-socials',
                    'label' => 'Redes sociales de ' . $store->name,
                ])
            </section>

            <section class="fashion-footer-contact" aria-label="Contacto">
                @if($store->whatsapp)
                    <a href="{{ $storeWhatsappUrl ?: '#' }}" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11.7A8 8 0 0 1 8.2 18.8L4 20l1.2-4.1A8 8 0 1 1 20 11.7Z"></path><path d="M9 8.5c.2-.4.4-.5.7-.5h.5c.2 0 .4.1.5.4l.7 1.6c.1.3.1.5-.1.7l-.4.5c.6 1.1 1.5 2 2.7 2.6l.5-.5c.2-.2.4-.3.7-.2l1.5.7c.3.1.4.3.4.6v.4c0 .4-.2.7-.5.9-.5.3-1.2.5-2.2.2-2.8-.7-5.1-3-5.8-5.7-.2-.8 0-1.4.3-1.8Z"></path></svg>
                        <span>
                            <small>WhatsApp</small>
                            <strong>{{ $store->whatsapp }}</strong>
                        </span>
                    </a>
                @endif
                @if($store->location)
                    <span>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-5.2 7-11a7 7 0 0 0-14 0c0 5.8 7 11 7 11Z"></path><circle cx="12" cy="10" r="2.4"></circle></svg>
                        <span>
                            <small>Ubicación</small>
                            <strong>{{ $store->location }}</strong>
                        </span>
                    </span>
                @endif
                @if($storeEmail)
                    <a href="mailto:{{ $storeEmail }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4z"></path><path d="m4 7 8 6 8-6"></path></svg>
                        <span>
                            <small>Correo</small>
                            <strong>{{ $storeEmail }}</strong>
                        </span>
                    </a>
                @endif
            </section>
        </div>

        <div class="fashion-footer-bottom">
            <div>
                <p>&copy; 2026 {{ $store->name }}. Todos los derechos reservados.</p>
                <p class="fashion-footer-credit">
                    Desarrollado por <a href="https://vendlysuite.com" target="_blank" rel="noopener noreferrer">Vendlysuite.com</a>
                </p>
            </div>
            @if($hasTerms)
                <nav aria-label="Legal">
                    @if($termsUrl !== '')
                        <a href="{{ $termsUrl }}" target="_blank" rel="noopener noreferrer">Términos y condiciones</a>
                    @else
                        <details class="fashion-footer-terms">
                            <summary>Términos y condiciones</summary>
                            <div>{!! nl2br(e($termsContent)) !!}</div>
                        </details>
                    @endif
                </nav>
            @endif
        </div>
    </div>
</footer>

@if($storeWhatsappUrl)
    <a
        href="{{ $storeWhatsappUrl }}"
        class="store-whatsapp-float"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Contactar por WhatsApp"
    >
        <img src="{{ asset('images/icons/icon-whatsapp.png') }}" alt="" aria-hidden="true">
    </a>
@endif
