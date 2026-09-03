@php
    $minimalProductCategory = trim((string) $product->category) !== '' ? $product->category : 'Otros';
    $minimalGallery = $productGallery->isNotEmpty() ? $productGallery : collect([null]);
    $minimalRelated = $relatedProducts->take(4);
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
    $minimalSwatches = ['#111111', '#ffffff', '#33415f'];
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
    $minimalInfoNavItems = collect([
        $minimalDescription !== '' ? ['href' => '#minimalProductDescription', 'label' => 'Descripcion'] : null,
        $minimalFeatureItems->isNotEmpty() ? ['href' => '#minimalProductFeatures', 'label' => 'Caracteristicas'] : null,
        $hasMinimalShippingInfo ? ['href' => '#minimalProductShipping', 'label' => 'Envios y devoluciones'] : null,
        ($minimalReviewsEnabled && $minimalReviewCount > 0) ? ['href' => '#minimalProductReviews', 'label' => 'Resenas ('.$minimalReviewCount.')'] : null,
    ])->filter()->values();
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
                    @if($minimalInfoNavItems->isNotEmpty())
                        <nav aria-label="Informacion del producto">
                            @foreach($minimalInfoNavItems as $item)
                                <a href="{{ $item['href'] }}" @class(['is-active' => $loop->first])>{{ $item['label'] }}</a>
                            @endforeach
                        </nav>
                    @endif

                    @if($hasMinimalProductInfo)
                        <div class="minimal-product-info-grid">
                            @if($minimalDescription !== '')
                                <section id="minimalProductDescription" class="minimal-product-copy minimal-product-info-card">
                                    <span>{!! $minimalIcons::icon('grid') !!}</span>
                                    <div>
                                        <h2>Descripcion</h2>
                                        <p>{!! nl2br(e($minimalDescription)) !!}</p>
                                    </div>
                                </section>
                            @endif

                            @if($minimalFeatureItems->isNotEmpty())
                                <section id="minimalProductFeatures" class="minimal-product-copy minimal-product-info-card">
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
                                <section id="minimalProductShipping" class="minimal-product-copy minimal-product-info-card">
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
                @if($minimalDescription !== '')
                    <p>{{ $minimalDescription }}</p>
                @endif

                <div class="minimal-product-divider"></div>

                @if($product->hasColors())
                    <fieldset class="minimal-product-colors">
                        <legend>Color</legend>
                        <div>
                            @foreach($product->colors as $color)
                                <label>
                                    <input type="radio" name="visual_color" value="{{ $color }}" data-role="selected-color-radio">
                                    <span style="--swatch: {{ $minimalSwatches[($loop->iteration - 1) % count($minimalSwatches)] }}"></span>
                                    <em>{{ $color }}</em>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @else
                    <div class="minimal-product-colors" aria-hidden="true">
                        <strong>Color</strong>
                        <div>
                            @foreach($minimalSwatches as $swatch)
                                <span style="--swatch: {{ $swatch }}"></span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($product->hasSizes())
                    <label class="minimal-product-size">
                        <span>Talla</span>
                        <select data-role="selected-size">
                            <option value="">Selecciona talla</option>
                            @foreach($product->sizes as $size)
                                <option value="{{ $size }}">{{ $size }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif

                <div class="minimal-product-quantity-row">
                    <div>
                        <span>Cantidad</span>
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
                        <form action="{{ route('cart.add', $product->id) }}" method="POST" class="add-to-cart-form" data-role="minimal-add-form">
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

            @if($minimalRelated->isNotEmpty())
                <section class="minimal-product-related">
                    <div class="minimal-shop-section-head">
                        <h2>Tambien te puede gustar</h2>
                        <div aria-hidden="true">&lsaquo; &rsaquo;</div>
                    </div>
                    <div class="minimal-product-related-grid">
                        @foreach($minimalRelated as $relatedProduct)
                            @include('storefront.partials.minimal-product-card', ['product' => $relatedProduct, 'isRecommendation' => true])
                        @endforeach
                    </div>
                </section>
            @endif
        </aside>
    </section>
</main>
