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
    $isFashionAbout = $store->isFashionStore();
    $isTechnologyAbout = $store->isTechnologyStore();
    $technologyProductCount = $store->products()->count();
    $technologyCategories = isset($activeCategories) ? collect($activeCategories) : collect();
    $technologyShippingMethods = collect($store->shippingMethods())
        ->filter(fn ($method) => trim((string) ($method['name'] ?? '')) !== '')
        ->values();
    $hasTechnologyShipping = $store->localDeliveryEnabled() || $technologyShippingMethods->isNotEmpty();
@endphp

@if($isTechnologyAbout)
<section class="minimal-about-layout tech-about" id="quienes-somos">
    <section class="tech-about-hero">
        @if($aboutImageUrl)
            <img src="{{ $aboutImageUrl }}" alt="" aria-hidden="true">
        @endif
        <div class="tech-about-hero-copy">
            <span>{{ $store->name }}</span>
            <h1>Nosotros</h1>
            <p>{{ $aboutCopy !== '' ? $aboutCopy : 'Somos una tienda creada para ofrecer productos de calidad, atención cercana y compras fáciles por WhatsApp.' }}</p>
            <div class="tech-about-actions">
                <a href="{{ $storefrontUrls->products($store) }}">Ver productos</a>
                @if($store->whatsapp)
                    <a href="{{ $store->whatsappInfoUrl() ?: '#' }}" target="_blank" rel="noopener noreferrer">Contactar</a>
                @elseif($storeEmail)
                    <a href="mailto:{{ $storeEmail }}">Contactar</a>
                @endif
            </div>
        </div>
    </section>

    <section class="tech-about-story" aria-label="Información de la tienda">
        <div class="tech-about-story-copy">
            <span>Conoce nuestra historia</span>
            <h2>Quiénes somos</h2>
            <p>{{ $aboutCopy !== '' ? $aboutCopy : 'Somos una tienda creada para ofrecer productos de calidad, atención cercana y compras fáciles por WhatsApp.' }}</p>
        </div>

        <div class="tech-about-facts">
            <article>
                <span aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"></path><path d="m4 7.5 8 4.5 8-4.5"></path><path d="M12 12v9"></path></svg>
                </span>
                <strong>{{ $technologyProductCount }}</strong>
                <small>{{ \Illuminate\Support\Str::plural('producto', $technologyProductCount) }}</small>
            </article>

            @if($technologyCategories->isNotEmpty())
                <article>
                    <span aria-hidden="true">
                        <svg viewBox="0 0 24 24"><rect x="4" y="4" width="6" height="6" rx="1.4"></rect><rect x="14" y="4" width="6" height="6" rx="1.4"></rect><rect x="4" y="14" width="6" height="6" rx="1.4"></rect><rect x="14" y="14" width="6" height="6" rx="1.4"></rect></svg>
                    </span>
                    <strong>{{ $technologyCategories->count() }}</strong>
                    <small>{{ \Illuminate\Support\Str::plural('categoría', $technologyCategories->count()) }}</small>
                </article>
            @endif

            @if($hasTechnologyShipping)
                <article>
                    <span aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M4 7h10v9H4z"></path><path d="M14 10h3.5l2.5 3v3h-6z"></path><circle cx="8" cy="18" r="1.7"></circle><circle cx="17" cy="18" r="1.7"></circle></svg>
                    </span>
                    <strong>Envíos</strong>
                    <small>{{ $store->localDeliveryEnabled() ? 'Entrega local disponible' : (($technologyShippingMethods->first()['name'] ?? null) ?: 'Opciones disponibles') }}</small>
                </article>
            @endif

            @if($store->whatsapp)
                <article>
                    <span aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M20 11.5a8 8 0 0 1-11.8 7L4 20l1.5-4.1A8 8 0 1 1 20 11.5Z"></path><path d="M9.5 8.5c.3 2 2 3.8 4 4.5l1.2-1.2 2 1.5c-.4 1.3-1.3 2-2.5 1.8-3.3-.5-5.8-3-6.5-6.1-.2-1.1.4-2 1.6-2.5l1.5 2-1.3 1.5Z"></path></svg>
                    </span>
                    <strong>WhatsApp</strong>
                    <small>{{ $store->whatsapp }}</small>
                </article>
            @endif
        </div>
    </section>

    @if($hasAboutValues)
        <section class="tech-about-values" aria-label="Misión y visión">
            @if($mission !== '')
                <article>
                    <span aria-hidden="true">
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
                    <span aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </span>
                    <div>
                        <h2>Visión</h2>
                        <p>{{ $vision }}</p>
                    </div>
                </article>
            @endif
        </section>
    @endif

    @if($hasContact)
        <section class="tech-about-contact" aria-label="Contacto">
            <div>
                <h2>¿Hablamos?</h2>
                <p>Estamos aquí para ayudarte.</p>
            </div>
            <div class="tech-about-contact-grid">
                @if($store->whatsapp)
                    <a href="{{ $store->whatsappInfoUrl() ?: '#' }}" target="_blank" rel="noopener noreferrer">
                        <span aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M20 11.5a8 8 0 0 1-11.8 7L4 20l1.5-4.1A8 8 0 1 1 20 11.5Z"></path><path d="M9.5 8.5c.3 2 2 3.8 4 4.5l1.2-1.2 2 1.5c-.4 1.3-1.3 2-2.5 1.8-3.3-.5-5.8-3-6.5-6.1-.2-1.1.4-2 1.6-2.5l1.5 2-1.3 1.5Z"></path></svg>
                        </span>
                        <strong>WhatsApp</strong>
                        <small>{{ $store->whatsapp }}</small>
                    </a>
                @endif

                @if($storeEmail)
                    <a href="mailto:{{ $storeEmail }}">
                        <span aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="4" y="6" width="16" height="12" rx="2"></rect><path d="m5 7 7 6 7-6"></path></svg>
                        </span>
                        <strong>Correo</strong>
                        <small>{{ $storeEmail }}</small>
                    </a>
                @endif

                @if($location !== '')
                    <div>
                        <span aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 21s7-5.2 7-11a7 7 0 0 0-14 0c0 5.8 7 11 7 11Z"></path><circle cx="12" cy="10" r="2.4"></circle></svg>
                        </span>
                        <strong>Ubicación</strong>
                        <small>{{ $location }}</small>
                    </div>
                @endif

                @if($businessHours !== '')
                    <div>
                        <span aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"></circle><path d="M12 8v4l3 2"></path></svg>
                        </span>
                        <strong>Horario</strong>
                        <small>{{ $businessHours }}</small>
                    </div>
                @endif
            </div>
        </section>
    @endif
</section>
@elseif($isFashionAbout)
<section class="fashion-about" id="quienes-somos">
    <div class="fashion-about-hero">
        <div class="fashion-about-copy">
            <span>Conoce nuestra historia</span>
            <h1>Nosotros</h1>
            <p class="fashion-about-lead">Moda creada para acompañarte todos los días.</p>
            <p>{{ $aboutCopy !== '' ? $aboutCopy : 'Somos una tienda creada para ofrecer prendas con estilo, atención cercana y una experiencia de compra simple desde WhatsApp.' }}</p>

            @if($hasContact)
                <div class="fashion-about-quick-contact" aria-label="Contacto rápido">
                    @if($store->whatsapp)
                        <a href="{{ $store->whatsappInfoUrl() ?: '#' }}" target="_blank" rel="noopener noreferrer">
                            <span aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M20 11.5a8 8 0 0 1-11.8 7L4 20l1.5-4.1A8 8 0 1 1 20 11.5Z"></path><path d="M9.5 8.5c.3 2 2 3.8 4 4.5l1.2-1.2 2 1.5c-.4 1.3-1.3 2-2.5 1.8-3.3-.5-5.8-3-6.5-6.1-.2-1.1.4-2 1.6-2.5l1.5 2-1.3 1.5Z"></path></svg>
                            </span>
                            <strong>WhatsApp</strong>
                            <small>{{ $store->whatsapp }}</small>
                        </a>
                    @endif

                    @if($storeEmail)
                        <a href="mailto:{{ $storeEmail }}">
                            <span aria-hidden="true">
                                <svg viewBox="0 0 24 24"><rect x="4" y="6" width="16" height="12" rx="2"></rect><path d="m5 7 7 6 7-6"></path></svg>
                            </span>
                            <strong>Correo</strong>
                            <small>{{ $storeEmail }}</small>
                        </a>
                    @endif

                    @if($location !== '')
                        <div>
                            <span aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M12 21s7-5.2 7-11a7 7 0 0 0-14 0c0 5.8 7 11 7 11Z"></path><circle cx="12" cy="10" r="2.4"></circle></svg>
                            </span>
                            <strong>Ubicación</strong>
                            <small>{{ $location }}</small>
                        </div>
                    @endif
                </div>
            @endif

            @include('storefront.partials.fashion-social-links', [
                'class' => 'fashion-about-socials',
                'label' => 'Redes sociales de ' . $store->name,
            ])
        </div>

        <div class="fashion-about-media" aria-hidden="true">
            @if($aboutImageUrl)
                <img src="{{ $aboutImageUrl }}" alt="">
            @else
                <span>{{ $aboutInitial }}</span>
            @endif
        </div>
    </div>

    <div class="fashion-about-cards" aria-label="Información de la tienda">
        <article>
            <span class="fashion-about-card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"></path></svg>
            </span>
            <h2>Misión</h2>
            <p>{{ $mission !== '' ? $mission : 'Ofrecer moda actual, cómoda y confiable para que cada cliente encuentre prendas que conecten con su estilo.' }}</p>
        </article>

        <article>
            <span class="fashion-about-card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            </span>
            <h2>Visión</h2>
            <p>{{ $vision !== '' ? $vision : 'Crecer como una marca cercana, reconocida por su estilo, servicio y experiencia de compra simple.' }}</p>
        </article>

        <article>
            <span class="fashion-about-card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><rect x="4" y="6" width="16" height="12" rx="2"></rect><path d="m5 7 7 6 7-6"></path></svg>
            </span>
            <h2>Contacto</h2>
            <p>{{ $businessHours !== '' ? $businessHours : 'Estamos aquí para ayudarte con tus pedidos, cambios y dudas sobre nuestros productos.' }}</p>
            @if($store->whatsapp)
                <a href="{{ $store->whatsappInfoUrl() ?: '#' }}" target="_blank" rel="noopener noreferrer">Escribir por WhatsApp</a>
            @elseif($storeEmail)
                <a href="mailto:{{ $storeEmail }}">Enviar correo</a>
            @endif
        </article>
    </div>
</section>
@else
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
@endif
