@php
    $storeWhatsappUrl = $store->whatsappInfoUrl();
    $storeFooterCopy = trim((string) $store->shop_copy);
    $footerDescription = $storeFooterCopy !== ''
        ? $storeFooterCopy
        : 'Estilos modernos para cada estilo de vida. Descubre calidad y comodidad en un solo lugar.';
    $termsContent = trim(strip_tags((string) $store->terms_content));
    $termsUrl = trim((string) $store->terms_url);
    $hasTerms = $termsContent !== '' || $termsUrl !== '';
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
