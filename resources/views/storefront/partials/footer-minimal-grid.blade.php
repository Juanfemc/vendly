@php
    $storeWhatsappUrl = $store->whatsappInfoUrl();
    $storeEmail = $store->user?->email;
    $storeFooterCopy = trim((string) $store->shop_copy);
    $termsContent = trim(strip_tags((string) $store->terms_content));
    $termsUrl = trim((string) $store->terms_url);
    $hasTerms = $termsContent !== '' || $termsUrl !== '';
    $minimalSocialLinks = collect([
        ['name' => 'Instagram', 'label' => 'Instagram', 'url' => trim((string) $store->instagram_url)],
        ['name' => 'Facebook', 'label' => 'Facebook', 'url' => trim((string) $store->facebook_url)],
        ['name' => 'TikTok', 'label' => 'TikTok', 'url' => trim((string) $store->tiktok_url)],
    ])->filter(fn ($social) => $social['url'] !== '')->values();
@endphp

<footer class="minimal-shop-footer" id="minimalShopFooter">
    <div class="shell">
        <div class="minimal-shop-footer-grid">
            <section class="minimal-shop-footer-brand" aria-label="{{ $store->name }}">
                <strong>{{ $store->name }}</strong>
                @if($storeFooterCopy !== '')
                    <p>{{ $storeFooterCopy }}</p>
                @endif
            </section>

            <section class="minimal-shop-footer-contact" aria-label="Contacto">
                <strong>Contacto</strong>
                @if($storeWhatsappUrl)
                    <a href="{{ $storeWhatsappUrl }}" target="_blank" rel="noopener noreferrer">
                        <span>WhatsApp</span>
                        <b>{{ $store->whatsapp }}</b>
                    </a>
                @endif
                @if($store->location)
                    <span>
                        <span>Ubicacion</span>
                        <b>{{ $store->location }}</b>
                    </span>
                @endif
                @if($storeEmail)
                    <a href="mailto:{{ $storeEmail }}">
                        <span>Correo</span>
                        <b>{{ $storeEmail }}</b>
                    </a>
                @endif
            </section>

            @if($minimalSocialLinks->isNotEmpty() || $hasTerms)
                <div class="minimal-shop-social-block">
                    @if($minimalSocialLinks->isNotEmpty())
                        <span>Redes sociales</span>
                        <div class="minimal-shop-socials">
                            @foreach($minimalSocialLinks as $social)
                                <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $social['name'] }}">{{ $social['label'] }}</a>
                            @endforeach
                        </div>
                    @endif

                    @if($hasTerms)
                        <div class="minimal-shop-footer-legal">
                            @if($termsUrl !== '')
                                <a href="{{ $termsUrl }}" target="_blank" rel="noopener noreferrer">Terminos y condiciones</a>
                            @else
                                <details>
                                    <summary>Terminos y condiciones</summary>
                                    <div>{!! nl2br(e($termsContent)) !!}</div>
                                </details>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>
        <p class="minimal-shop-footer-credit">
            Desarrollado por <a href="https://vendlysuite.com" target="_blank" rel="noopener noreferrer">Vendlysuite.com</a>
        </p>
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
