@php
    $aboutImage = $store->cover_image ?: $store->logo_image;
    $aboutImageUrl = $aboutImage ? asset('storage/' . $aboutImage) : null;
    $aboutInitial = mb_strtoupper(mb_substr($store->name, 0, 1));
    $aboutCopy = trim((string) $store->shop_copy);
    $mission = trim((string) $store->mission);
    $vision = trim((string) $store->vision);
    $location = trim((string) $store->location);
    $businessHours = trim((string) $store->business_hours);
    $storeEmail = $store->user?->email;
    $hasAboutValues = $mission !== '' || $vision !== '';
    $hasContact = trim((string) $store->whatsapp) !== '' || $location !== '' || $businessHours !== '' || $storeEmail;
@endphp

<section class="store-about store-about-page" id="quienes-somos">
    <div class="store-about-hero">
        <div class="store-about-media" aria-hidden="true">
            @if($aboutImageUrl)
                <img src="{{ $aboutImageUrl }}" alt="">
            @else
                <span>{{ $aboutInitial }}</span>
            @endif
        </div>

        <div class="store-about-copy">
            <h1>Nosotros</h1>
            <span>Conoce nuestra historia</span>
            <p>{{ $aboutCopy !== '' ? $aboutCopy : 'Somos una tienda creada para ofrecer productos de calidad, atención cercana y compras fáciles por WhatsApp.' }}</p>
        </div>
    </div>

    @if($hasAboutValues)
        <div class="store-about-values" aria-label="Mision y vision">
            @if($mission !== '')
                <article>
                    <span class="store-about-value-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"></circle><circle cx="12" cy="12" r="3"></circle><path d="m15 9 5-5"></path><path d="M17 4h3v3"></path></svg>
                    </span>
                    <div>
                        <h2>Misión</h2>
                        <p>{{ $mission }}</p>
                    </div>
                </article>
            @endif

            @if($vision !== '')
                <article>
                    <span class="store-about-value-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </span>
                    <div>
                        <h2>Visión</h2>
                        <p>{{ $vision }}</p>
                    </div>
                </article>
            @endif
        </div>
    @endif

    @if($hasContact)
        <div class="store-about-contact" aria-label="Contacto">
            <h2>Contacto</h2>

            <div class="store-about-contact-grid">
                @if($store->whatsapp)
                    <a href="{{ $store->whatsappInfoUrl() ?: '#' }}" target="_blank" rel="noopener noreferrer" class="store-about-contact-item">
                        <span class="store-about-contact-icon store-about-contact-icon--whatsapp" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M20 11.5a8 8 0 0 1-11.8 7L4 20l1.5-4.1A8 8 0 1 1 20 11.5Z"></path><path d="M9.5 8.5c.3 2 2 3.8 4 4.5l1.2-1.2 2 1.5c-.4 1.3-1.3 2-2.5 1.8-3.3-.5-5.8-3-6.5-6.1-.2-1.1.4-2 1.6-2.5l1.5 2-1.3 1.5Z"></path></svg>
                        </span>
                        <span>
                            <strong>WhatsApp</strong>
                            <small>{{ $store->whatsapp }}</small>
                        </span>
                    </a>
                @endif

                @if($location !== '')
                    <div class="store-about-contact-item">
                        <span class="store-about-contact-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 21s7-5.2 7-11a7 7 0 0 0-14 0c0 5.8 7 11 7 11Z"></path><circle cx="12" cy="10" r="2.4"></circle></svg>
                        </span>
                        <span>
                            <strong>Ubicación</strong>
                            <small>{{ $location }}</small>
                        </span>
                    </div>
                @endif

                @if($businessHours !== '')
                    <div class="store-about-contact-item">
                        <span class="store-about-contact-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"></circle><path d="M12 8v4l3 2"></path></svg>
                        </span>
                        <span>
                            <strong>Horario</strong>
                            <small>{{ $businessHours }}</small>
                        </span>
                    </div>
                @endif

                @if($storeEmail)
                    <a href="mailto:{{ $storeEmail }}" class="store-about-contact-item">
                        <span class="store-about-contact-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="4" y="6" width="16" height="12" rx="2"></rect><path d="m5 7 7 6 7-6"></path></svg>
                        </span>
                        <span>
                            <strong>Email</strong>
                            <small>{{ $storeEmail }}</small>
                        </span>
                    </a>
                @endif
            </div>
        </div>
    @endif
</section>
