@php
    $minimalProductCategory = trim((string) $product->category) !== '' ? $product->category : 'Otros';
    $minimalGallery = $productGallery->isNotEmpty() ? $productGallery : collect([null]);
    $minimalRelated = $relatedProducts->take(3);
    $minimalAllowsOnlinePayments = $store->allowsOnlinePayments();
    $minimalReviewsEnabled = $store->allowsProductReviews();
    $minimalReviews = $minimalReviewsEnabled
        ? $product->approvedReviews()->latest()->take(6)->get()
        : collect();
    $minimalReviewCount = $minimalReviewsEnabled ? $product->reviewCount() : 0;
    $minimalReviewAverage = $minimalReviewsEnabled ? $product->reviewAverage() : null;
    $minimalReviewLabel = $minimalReviewCount > 0
        ? number_format($minimalReviewAverage, 1) . ' (' . $minimalReviewCount . ' ' . \Illuminate\Support\Str::plural('resena', $minimalReviewCount) . ')'
        : null;
    $minimalInitials = strtoupper(substr($product->name, 0, 2));
    $minimalBadges = $product->displayBadges($store);
    $minimalColors = $product->hasColors() ? collect($product->colors)->values() : collect();
    $minimalColorDisplay = $store->colorVariantDisplay();
    $minimalColorMap = [
        'navy' => '#173a63',
        'azul' => '#173a63',
        'blue' => '#173a63',
        'green' => '#12643f',
        'verde' => '#12643f',
        'black' => '#111111',
        'negro' => '#111111',
        'gray' => '#b8b8b8',
        'gris' => '#b8b8b8',
        'silver' => '#c7c7c7',
        'plateado' => '#c7c7c7',
        'white' => '#f8f8f8',
        'blanco' => '#f8f8f8',
        'red' => '#d62828',
        'rojo' => '#d62828',
        'pink' => '#f2a0b7',
        'rosado' => '#f2a0b7',
        'purple' => '#6d4aff',
        'morado' => '#6d4aff',
        'yellow' => '#f4c430',
        'amarillo' => '#f4c430',
        'orange' => '#ff7a1a',
        'naranja' => '#ff7a1a',
        'brown' => '#8b5a2b',
        'cafe' => '#8b5a2b',
        'beige' => '#d7c4a3',
    ];
    $minimalColorOptions = $minimalColors
        ->map(function ($color) use ($minimalColorMap) {
            $label = trim((string) $color);
            $key = \Illuminate\Support\Str::lower($label);
            $swatch = preg_match('/^#[0-9a-f]{3}(?:[0-9a-f]{3})?$/i', $label)
                ? $label
                : ($minimalColorMap[$key] ?? '#d7dbe0');

            return [
                'label' => $label,
                'value' => $label,
                'swatch' => $swatch,
            ];
        })
        ->filter(fn ($color) => $color['label'] !== '')
        ->values();
    $minimalSizeOptions = collect($product->sizes ?? [])
        ->map(fn ($size) => trim((string) $size))
        ->filter()
        ->values();
    $minimalCartIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="20" r="1.6"/><circle cx="18" cy="20" r="1.6"/><path d="M3 4h2.4l2.2 10.4a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 8H7"/></svg>';
    $minimalBuyIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 2 4 14h7l-1 8 10-13h-7l1-7Z"/></svg>';
    $minimalIcons = \App\Support\MinimalShopIcons::class;
    $minimalDisplayPrice = (float) $product->price;
    $minimalShowsOfferPricing = $store->allowsOfferBadges() && $product->hasOfferPricing();
    $minimalDescription = \App\Support\ProductText::plain($product->description);
    $minimalFeatureItems = collect(preg_split('/\R+/', \App\Support\ProductText::featureLines($product->features)) ?: [])
        ->map(fn ($feature) => trim($feature, " \t\n\r\0\x0B-*"))
        ->filter()
        ->take(6)
        ->values();
    $minimalTechSpecItems = collect([
        trim((string) $product->material) !== '' ? ['icon' => 'settings', 'label' => $product->material] : null,
        $minimalFeatureItems->get(0) ? ['icon' => 'spark', 'label' => $minimalFeatureItems->get(0)] : null,
        $minimalFeatureItems->get(1) ? ['icon' => 'award', 'label' => $minimalFeatureItems->get(1)] : null,
        ['icon' => 'grid', 'label' => $minimalProductCategory],
    ])
        ->filter(fn ($item) => $item && trim((string) $item['label']) !== '')
        ->unique(fn ($item) => \Illuminate\Support\Str::lower((string) $item['label']))
        ->take(4)
        ->values();
    $minimalShippingMethods = collect($store->shippingMethods())
        ->filter(fn ($method) => trim((string) ($method['name'] ?? '')) !== '')
        ->values();
    $minimalTermsContent = trim(strip_tags((string) $store->terms_content));
    $minimalTermsUrl = trim((string) $store->terms_url);
    $hasMinimalShippingInfo = $store->localDeliveryEnabled()
        || $minimalShippingMethods->isNotEmpty()
        || $minimalTermsContent !== ''
        || $minimalTermsUrl !== '';
    $hasMinimalProductInfo = $minimalDescription !== ''
        || $minimalFeatureItems->isNotEmpty()
        || $hasMinimalShippingInfo;
    $minimalInfoTabItems = collect([
        $minimalDescription !== '' ? ['key' => 'description', 'target' => 'minimalProductDescription', 'label' => 'Descripcion'] : null,
        $minimalFeatureItems->isNotEmpty() ? ['key' => 'features', 'target' => 'minimalProductFeatures', 'label' => 'Caracteristicas'] : null,
        $hasMinimalShippingInfo ? ['key' => 'shipping', 'target' => 'minimalProductShipping', 'label' => 'Envios y devoluciones'] : null,
    ])->filter()->values();
    $minimalReviewsNavItem = ($minimalReviewsEnabled && $minimalReviewCount > 0)
        ? ['href' => '#minimalProductReviews', 'label' => 'Resenas ('.$minimalReviewCount.')']
        : null;
    $minimalWhatsappNumber = $store->whatsappNumber();
    $minimalProductUrl = $storefrontUrls->product($store, $product);
    $minimalWhatsappUrl = $minimalWhatsappNumber !== ''
        ? 'https://wa.me/' . $minimalWhatsappNumber . '?text=' . rawurlencode("Hola, quiero comprar {$product->name}. {$minimalProductUrl}")
        : null;
    $minimalAddFormId = 'minimalProductAddForm-' . $product->id;
@endphp

<main class="shell minimal-product-page">
    <section class="minimal-product-breadcrumb" aria-label="Ruta del producto">
        <a href="{{ $storefrontUrls->home($store) }}">Inicio</a>
        <span aria-hidden="true">&rsaquo;</span>
        <a href="{{ $storefrontUrls->products($store) }}">Tienda</a>
        @if($product->category)
            <span aria-hidden="true">&rsaquo;</span>
            <span>{{ $product->category }}</span>
        @endif
        <span aria-hidden="true">&rsaquo;</span>
        <strong>{{ $product->name }}</strong>
    </section>

    <section class="minimal-product-layout">
        <div class="minimal-product-gallery" data-product-carousel>
            <div class="minimal-product-stage">
                <span class="minimal-product-badge">{{ $minimalProductCategory }}</span>
                @if($minimalBadges !== [])
                    <div class="minimal-product-badges">
                        @foreach($minimalBadges as $badge)
                            <span class="minimal-product-badge">{{ $badge }}</span>
                        @endforeach
                    </div>
                @endif
                @foreach($minimalGallery as $index => $galleryImage)
                    @if($galleryImage)
                        <img
                            src="{{ asset('storage/' . $galleryImage) }}"
                            alt="{{ $product->name }} imagen {{ $index + 1 }}"
                            class="minimal-product-image {{ $index === 0 ? 'is-active' : '' }}"
                            data-carousel-slide="{{ $index }}"
                            loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                            fetchpriority="{{ $index === 0 ? 'high' : 'auto' }}"
                            decoding="async"
                        >
                        <div class="minimal-product-placeholder {{ $index === 0 ? 'is-active' : '' }}" data-carousel-fallback="{{ $index }}" hidden>{{ $minimalInitials }}</div>
                    @else
                        <div class="minimal-product-placeholder is-active" data-carousel-slide="{{ $index }}">{{ $minimalInitials }}</div>
                    @endif
                @endforeach

                @if($minimalGallery->count() > 1)
                    <button type="button" class="minimal-product-arrow minimal-product-arrow--prev" data-carousel-prev aria-label="Imagen anterior">&lsaquo;</button>
                    <button type="button" class="minimal-product-arrow minimal-product-arrow--next" data-carousel-next aria-label="Imagen siguiente">&rsaquo;</button>
                @endif
            </div>

            @if($minimalGallery->count() > 1)
                <div class="minimal-product-thumbs" aria-label="Imagenes del producto">
                    @foreach($minimalGallery as $index => $galleryImage)
                        <button
                            type="button"
                            class="minimal-product-thumb {{ $index === 0 ? 'is-active' : '' }}"
                            data-carousel-thumb="{{ $index }}"
                            aria-label="Ver imagen {{ $index + 1 }}"
                            aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                        >
                            @if($galleryImage)
                                <img src="{{ asset('storage/' . $galleryImage) }}" alt="" loading="lazy" decoding="async">
                            @else
                                        <span>{{ $minimalInitials }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif

            @if($hasMinimalProductInfo || $minimalReviewsEnabled)
                <section class="minimal-product-tabs">
                    @if($minimalInfoTabItems->isNotEmpty())
                        @foreach($minimalInfoTabItems as $item)
                            <input
                                type="radio"
                                class="minimal-product-tab-state"
                                id="minimalProductTab-{{ $item['key'] }}"
                                name="minimal_product_tab_{{ $product->id }}"
                                value="{{ $item['key'] }}"
                                @checked($loop->first)
                            >
                        @endforeach

                        <nav aria-label="Informacion del producto" class="minimal-product-tab-list">
                            @foreach($minimalInfoTabItems as $item)
                                <label
                                    for="minimalProductTab-{{ $item['key'] }}"
                                    class="minimal-product-tab-control minimal-product-tab-control--{{ $item['key'] }}"
                                    role="button"
                                    tabindex="0"
                                >
                                    {{ $item['label'] }}
                                </label>
                            @endforeach
                            @if($minimalReviewsNavItem)
                                <a href="{{ $minimalReviewsNavItem['href'] }}">{{ $minimalReviewsNavItem['label'] }}</a>
                            @endif
                        </nav>
                    @endif

                    @if($hasMinimalProductInfo)
                        <div class="minimal-product-info-grid">
                            @if($minimalDescription !== '')
                                <section id="minimalProductDescription" class="minimal-product-copy minimal-product-info-card minimal-product-tab-panel minimal-product-tab-panel--description">
                                    <span>{!! $minimalIcons::icon('grid') !!}</span>
                                    <div>
                                        <h2>Descripcion</h2>
                                        <p>{!! nl2br(e($minimalDescription)) !!}</p>
                                    </div>
                                </section>
                            @endif

                            @if($minimalFeatureItems->isNotEmpty())
                                <section id="minimalProductFeatures" class="minimal-product-copy minimal-product-info-card minimal-product-tab-panel minimal-product-tab-panel--features">
                                    <span>{!! $minimalIcons::icon('settings') !!}</span>
                                    <div>
                                        <h2>Caracteristicas</h2>
                                        <ul>
                                            @foreach($minimalFeatureItems as $feature)
                                                <li>{{ $feature }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </section>
                            @endif

                            @if($hasMinimalShippingInfo)
                                <section id="minimalProductShipping" class="minimal-product-copy minimal-product-info-card minimal-product-tab-panel minimal-product-tab-panel--shipping">
                                    <span>{!! $minimalIcons::icon('truck') !!}</span>
                                    <div>
                                        <h2>Envios y devoluciones</h2>
                                        <ul>
                                            @if($store->localDeliveryEnabled())
                                                <li>Envio local disponible.</li>
                                            @endif
                                            @foreach($minimalShippingMethods->take(4) as $method)
                                                <li>{{ $method['name'] }}@if((float) ($method['cost'] ?? 0) > 0): ${{ number_format((float) $method['cost'], 0, ',', '.') }}@endif</li>
                                            @endforeach
                                            @if($minimalTermsUrl !== '')
                                                <li><a href="{{ $minimalTermsUrl }}" target="_blank" rel="noopener noreferrer">Ver terminos y condiciones</a></li>
                                            @elseif($minimalTermsContent !== '')
                                                <li>Cambios y devoluciones segun politicas de la tienda.</li>
                                            @endif
                                        </ul>
                                    </div>
                                </section>
                            @endif
                        </div>
                    @endif

                @if($minimalReviewsEnabled)
                    <section id="minimalProductReviews" class="minimal-product-reviews">
                        <div class="minimal-product-reviews-head">
                            <div>
                                <h2>Resenas</h2>
                                <p>Opiniones de clientes sobre este producto.</p>
                            </div>
                            @if($minimalReviewCount > 0)
                                <div class="minimal-product-review-score" aria-label="{{ $minimalReviewLabel }}">
                                    <span aria-hidden="true">&#9733;</span>
                                    <strong>{{ number_format($minimalReviewAverage, 1) }}</strong>
                                    <small>{{ $minimalReviewCount }} {{ \Illuminate\Support\Str::plural('resena', $minimalReviewCount) }}</small>
                                </div>
                            @endif
                        </div>

                        @if(session('review_success'))
                            <div class="minimal-product-review-alert">{{ session('review_success') }}</div>
                        @endif

                        <div class="minimal-product-review-list">
                            @foreach($minimalReviews as $review)
                                <article>
                                    <div>
                                        <strong>{{ $review->name }}</strong>
                                        <span>{{ number_format((float) $review->rating, 1) }} &#9733;</span>
                                    </div>
                                    @if($review->comment)
                                        <p>{{ $review->comment }}</p>
                                    @endif
                                </article>
                            @endforeach
                        </div>

                        <form action="{{ route('product.reviews.store', $product) }}" method="POST" class="minimal-product-review-form">
                            @csrf
                            <div class="minimal-product-review-form-head">
                                <h3>Comparte tu experiencia</h3>
                                <p>Tu resena sera revisada antes de publicarse.</p>
                            </div>
                            <label>
                                <span>Nombre</span>
                                <input type="text" name="name" value="{{ old('name') }}" maxlength="80" required>
                            </label>
                            <label>
                                <span>Calificacion</span>
                                <select name="rating" required>
                                    @for($rating = 5; $rating >= 1; $rating--)
                                        <option value="{{ $rating }}" @selected((int) old('rating', 5) === $rating)>{{ $rating }} estrellas</option>
                                    @endfor
                                </select>
                            </label>
                            <label class="minimal-product-review-form-comment">
                                <span>Comentario</span>
                                <textarea name="comment" rows="3" maxlength="1000">{{ old('comment') }}</textarea>
                            </label>
                            <button type="submit">Publicar resena</button>
                        </form>
                    </section>
                @endif
                </section>
            @endif
        </div>

        <aside class="minimal-product-summary">
            <div class="minimal-product-main">
                <span class="minimal-product-summary-badge">{{ $minimalProductCategory }}</span>
                <h1>{{ $product->name }}</h1>
                @if($minimalReviewsEnabled && $minimalReviewCount > 0)
                    <div class="minimal-product-rating"><span aria-hidden="true">&#9733;</span> {{ $minimalReviewLabel }}</div>
                @endif
                <div class="minimal-product-price">
                    @if($minimalShowsOfferPricing)
                        <span class="minimal-product-price-before">${{ number_format((float) $product->offer_original_price, 2, '.', ',') }}</span>
                    @endif
                    <span>${{ number_format((float) $minimalDisplayPrice, 2, '.', ',') }}</span>
                </div>
                @if($product->hasWholesalePricing($store))
                    <span class="product-wholesale-note product-wholesale-note--detail">Mayorista desde {{ $product->wholesale_min_quantity }} unidades: ${{ number_format((float) $product->wholesale_price, 2, '.', ',') }}</span>
                @endif
                @if($minimalTechSpecItems->isNotEmpty())
                    <div class="minimal-product-spec-chips" aria-label="Resumen del producto">
                        @foreach($minimalTechSpecItems as $spec)
                            <span>
                                {!! $minimalIcons::icon($spec['icon']) !!}
                                <strong>{{ $spec['label'] }}</strong>
                            </span>
                        @endforeach
                    </div>
                @endif
                @if($minimalDescription !== '')
                    <p>{{ $minimalDescription }}</p>
                @endif

                <div class="minimal-product-divider"></div>

                @if($minimalColorOptions->isNotEmpty())
                    <fieldset @class([
                        'minimal-product-colors',
                        'minimal-product-variant-group',
                        'minimal-product-colors--labels' => $minimalColorDisplay === \App\Models\Store::COLOR_VARIANT_DISPLAY_LABEL,
                        'minimal-product-colors--swatches' => $minimalColorDisplay !== \App\Models\Store::COLOR_VARIANT_DISPLAY_LABEL,
                    ])>
                        <legend>
                            <span>Color</span>
                        </legend>
                        <div class="minimal-product-variant-list">
                            @foreach($minimalColorOptions as $color)
                                <label title="{{ $color['label'] }}">
                                    <input type="radio" name="visual_color" value="{{ $color['value'] }}" data-role="selected-color-radio">
                                    <span class="minimal-product-variant-option minimal-product-color-option">
                                        <i class="minimal-product-color-swatch" style="--swatch: {{ $color['swatch'] }}" aria-hidden="true"></i>
                                        <b>{{ $color['label'] }}</b>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                @if($minimalSizeOptions->isNotEmpty())
                    <fieldset class="minimal-product-size minimal-product-variant-group">
                        <legend>
                            <span>Talla</span>
                        </legend>
                        <div class="minimal-product-variant-list">
                            @foreach($minimalSizeOptions as $size)
                                <label title="{{ $size }}">
                                    <input type="radio" name="visual_size" value="{{ $size }}" data-role="selected-size-radio">
                                    <span class="minimal-product-variant-option">{{ $size }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                <div @class(['minimal-product-purchase-panel', 'is-unavailable' => $isProductSoldOut])>
                    <div class="minimal-product-quantity-row">
                        <div>
                            <div class="minimal-product-stepper">
                                <button type="button" data-quantity-minus aria-label="Restar cantidad">&minus;</button>
                                <input id="quantity" type="number" name="quantity" min="1" max="{{ $quantityMax }}" value="{{ old('quantity', 1) }}" class="product-quantity-input">
                                <button type="button" data-quantity-plus aria-label="Sumar cantidad">+</button>
                            </div>
                        </div>
                        @if($product->stockLabel())
                            <span class="minimal-product-stock {{ $isProductSoldOut ? 'is-sold-out' : '' }}">{{ $product->stockLabel() }}</span>
                        @endif
                    </div>

                    @if($isProductSoldOut)
                        <div class="product-unavailable-message">Este producto esta agotado por ahora.</div>
                    @else
                        <div class="minimal-product-actions">
                            <form id="{{ $minimalAddFormId }}" action="{{ route('cart.add', $product->id) }}" method="POST" class="add-to-cart-form" data-role="minimal-add-form">
                                @csrf
                                <input type="hidden" name="quantity" value="{{ old('quantity', 1) }}" data-role="add-quantity">
                                <input type="hidden" name="size" value="" data-role="add-size">
                                <input type="hidden" name="color" value="" data-role="add-color">
                                <button
                                    type="submit"
                                    class="minimal-product-add"
                                    data-variant-action
                                    data-variant-add-action
                                    data-enabled-label="Agregar al carrito"
                                    @disabled($product->hasVariants())
                                >
                                    {!! $minimalCartIcon !!}
                                    <span data-variant-label>{{ $product->hasVariants() ? 'Selecciona una opcion' : 'Agregar al carrito' }}</span>
                                    <span class="minimal-product-add-count" data-cart-count-badge @if(($cartCount ?? 0) < 1) hidden @endif>{{ $cartCount ?? 0 }}</span>
                                </button>
                            </form>

                            <form action="{{ route('cart.buy_now', $product->id) }}" method="POST" data-role="buy-now-form">
                                @csrf
                                <input type="hidden" name="quantity" value="{{ old('quantity', 1) }}" data-role="buy-now-quantity">
                                <input type="hidden" name="size" value="" data-role="buy-now-size">
                                <input type="hidden" name="color" value="" data-role="buy-now-color">
                                <button type="submit" class="minimal-product-buy" data-variant-action @disabled($product->hasVariants())>
                                    {!! $minimalBuyIcon !!}
                                    <span>Comprar ahora</span>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                @if(!$isProductSoldOut && $minimalWhatsappUrl)
                    <a class="minimal-product-whatsapp-action" href="{{ $minimalWhatsappUrl }}" target="_blank" rel="noopener" aria-label="Pedir este producto por WhatsApp">
                        <span>
                            <img src="{{ asset('images/icons/icon-whatsapp.png') }}" alt="" class="minimal-product-whatsapp-logo" aria-hidden="true" loading="lazy" decoding="async">
                        </span>
                        <strong>Pedir por WhatsApp</strong>
                    </a>
                @endif

                <section class="minimal-product-share" aria-label="Compartir producto">
                    <h2>Compartir</h2>
                    <div>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrlEncoded }}" target="_blank" rel="noopener noreferrer" aria-label="Compartir en Facebook">
                            <img src="{{ asset('images/icons/icon-facebook.png') }}" alt="" aria-hidden="true">
                            <span>Facebook</span>
                        </a>
                        <a href="https://wa.me/?text={{ $shareTextEncoded }}%20{{ $shareUrlEncoded }}" target="_blank" rel="noopener noreferrer" aria-label="Compartir por WhatsApp">
                            <img src="{{ asset('images/icons/icon-whatsapp.png') }}" alt="" aria-hidden="true">
                            <span>WhatsApp</span>
                        </a>
                        <button type="button" data-copy-product-link="{{ $metaUrl }}" aria-label="Copiar enlace del producto">
                            <img src="{{ asset('images/icons/icon-copiar-enlace.png') }}" alt="" aria-hidden="true">
                            <span>Copiar</span>
                        </button>
                    </div>
                </section>
            </div>
        </aside>

        @if($minimalRelated->isNotEmpty())
            <section class="minimal-product-related">
                <div class="minimal-shop-section-head">
                    <h2>Tambien te puede gustar</h2>
                </div>
                <div class="minimal-product-related-grid">
                    @foreach($minimalRelated as $relatedProduct)
                        @include('storefront.partials.minimal-product-card', ['product' => $relatedProduct, 'isRecommendation' => true])
                    @endforeach
                </div>
            </section>
        @endif
    </section>

</main>
