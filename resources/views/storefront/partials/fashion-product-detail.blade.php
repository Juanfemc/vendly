@php
    $fashionGalleryImages = $productGallery->filter()->values();
    $fashionGallery = $fashionGalleryImages->isNotEmpty() ? $fashionGalleryImages : collect([null]);
    $fashionRelated = $relatedProducts->take(4);
    $fashionProductPrice = (float) $product->price;
    $fashionSizes = $product->hasSizes() ? collect($product->sizes)->values() : collect();
    $fashionColors = $product->hasColors() ? collect($product->colors)->values() : collect();
    $fashionCartIcon = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7.2 9.2h9.6l-.7 10a2 2 0 0 1-2 1.8H9.9a2 2 0 0 1-2-1.8l-.7-10Z"/><path d="M9.5 9.2V7.4a2.5 2.5 0 0 1 5 0v1.8"/></svg>';
    $fashionBuyIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 2 4 14h7l-1 8 10-13h-7l1-7Z"/></svg>';
    $fashionProductBadges = $productBadges ?? $product->displayBadges($store);
    $fashionStockLabel = $product->stockLabel();
    $fashionProductCategoryLink = ($activeCategories ?? collect())
        ->first(fn ($category) => mb_strtolower(trim((string) $category->name)) === mb_strtolower(trim((string) $product->category)));
    $fashionProductParentCategoryLink = $fashionProductCategoryLink?->parent;
    $fashionReviewsEnabled = $productReviewsEnabled ?? $store->allowsProductReviews();
    $fashionReviews = $productReviews ?? ($fashionReviewsEnabled ? $product->approvedReviews()->latest()->take(3)->get() : collect());
    $fashionReviewCount = $productReviewCount ?? ($fashionReviewsEnabled ? $product->reviewCount() : 0);
    $fashionReviewAverage = $productReviewAverage ?? ($fashionReviewsEnabled ? $product->reviewAverage() : null);
    $fashionReviewLabel = $productReviewLabel ?? ($fashionReviewCount > 0
        ? number_format((float) $fashionReviewAverage, 1) . ' (' . $fashionReviewCount . ' ' . \Illuminate\Support\Str::plural('reseña', $fashionReviewCount) . ')'
        : null);
    $fashionColorMap = [
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
    $fashionColorOptions = $fashionColors
        ->map(function ($color) use ($fashionColorMap) {
            $label = trim((string) $color);
            $key = \Illuminate\Support\Str::lower($label);
            $swatch = preg_match('/^#[0-9a-f]{3}(?:[0-9a-f]{3})?$/i', $label)
                ? $label
                : ($fashionColorMap[$key] ?? '#d7dbe0');

            return [
                'label' => $label,
                'value' => $label,
                'swatch' => $swatch,
            ];
        })
        ->filter(fn ($color) => $color['label'] !== '')
        ->values();
    $fashionDescription = $productDescriptionText ?? \App\Support\ProductText::plain($product->description);
    $fashionDescription = $fashionDescription ?: 'Este producto aún no tiene una descripción amplia configurada.';
    $fashionFeaturesText = $productFeaturesText ?? \App\Support\ProductText::featureLines($product->features);
    $renderFashionProductText = fn (string $text): string => nl2br(e($text), false);
    $fashionWhatsappNumber = $store->whatsappNumber();
    $fashionProductUrl = $storefrontUrls->product($store, $product);
    $fashionWhatsappUrl = $fashionWhatsappNumber !== ''
        ? 'https://wa.me/' . $fashionWhatsappNumber . '?text=' . rawurlencode("Hola, quiero comprar {$product->name}. {$fashionProductUrl}")
        : null;
@endphp

<main class="fashion-product-shell">
    <nav class="fashion-product-breadcrumb" aria-label="Ruta de navegacion">
        <a href="{{ $storefrontUrls->home($store) }}">Inicio</a>
        <span>&rsaquo;</span>
        <a href="{{ $storefrontUrls->products($store) }}">Categorías</a>
        @if($fashionProductParentCategoryLink)
            <span>&rsaquo;</span>
            <a href="{{ $storefrontUrls->category($store, $fashionProductParentCategoryLink) }}">{{ $fashionProductParentCategoryLink->name }}</a>
        @endif
        @if($product->category)
            <span>&rsaquo;</span>
            @if($fashionProductCategoryLink)
                <a href="{{ $storefrontUrls->category($store, $fashionProductCategoryLink) }}">{{ $product->category }}</a>
            @else
                <a href="{{ $storefrontUrls->products($store) }}">{{ $product->category }}</a>
            @endif
        @endif
        <span>&rsaquo;</span>
        <span>{{ $product->name }}</span>
    </nav>

    <section class="fashion-product-hero">
        <div class="fashion-product-gallery product-carousel" data-product-carousel>
            @if($fashionGalleryImages->isNotEmpty())
                <div class="fashion-product-thumbs" aria-label="Imagenes del producto">
                    @foreach($fashionGalleryImages as $index => $galleryImage)
                        <button
                            type="button"
                            class="fashion-product-thumb {{ $index === 0 ? 'is-active' : '' }}"
                            data-carousel-thumb="{{ $index }}"
                            aria-label="Ver imagen {{ $index + 1 }}"
                            aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                        >
                            <img src="{{ asset('storage/' . $galleryImage) }}" alt="" loading="lazy" decoding="async">
                        </button>
                    @endforeach
                </div>
            @endif

            <div class="fashion-product-stage">
                @foreach($fashionGallery as $index => $galleryImage)
                    @if($galleryImage)
                        <img
                            src="{{ asset('storage/' . $galleryImage) }}"
                            alt="{{ $product->name }} imagen {{ $index + 1 }}"
                            class="fashion-product-image product-carousel-image {{ $index === 0 ? 'is-active' : '' }}"
                            data-carousel-slide="{{ $index }}"
                            loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                            fetchpriority="{{ $index === 0 ? 'high' : 'auto' }}"
                            decoding="async"
                        >
                    @else
                        <div class="fashion-product-fallback product-carousel-image is-active" data-carousel-slide="{{ $index }}">
                            {{ $product->name }}
                        </div>
                    @endif
                @endforeach
                @if($fashionGalleryImages->isNotEmpty())
                    <button type="button" class="fashion-product-zoom" data-carousel-zoom aria-label="Ampliar imagen">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="11" cy="11" r="6"></circle>
                            <path d="m16 16 4 4"></path>
                        </svg>
                    </button>
                @endif

                @if($fashionGalleryImages->count() > 1)
                    <button type="button" class="product-carousel-control product-carousel-control--prev fashion-product-carousel-control" data-carousel-prev aria-label="Imagen anterior">
                        <span aria-hidden="true"></span>
                    </button>
                    <button type="button" class="product-carousel-control product-carousel-control--next fashion-product-carousel-control" data-carousel-next aria-label="Imagen siguiente">
                        <span aria-hidden="true"></span>
                    </button>
                @endif
            </div>
        </div>

        <aside class="fashion-product-info">
            @if($product->category)
                <span class="fashion-product-kicker">{{ $product->category }}</span>
            @endif
            @if($fashionProductBadges !== [])
                <div class="fashion-product-detail-badges" aria-label="Etiquetas del producto">
                    @foreach($fashionProductBadges as $badge)
                        <span>{{ $badge }}</span>
                    @endforeach
                </div>
            @endif
            <h1>{{ $product->name }}</h1>
            @if($fashionReviewsEnabled && $fashionReviewCount > 0)
                <a class="fashion-product-rating" href="#fashionProductReviews" aria-label="{{ $fashionReviewLabel }}">
                    <span aria-hidden="true">★★★★★</span>
                    <strong>{{ number_format((float) $fashionReviewAverage, 1) }}</strong>
                    <em>{{ $fashionReviewCount }} {{ \Illuminate\Support\Str::plural('reseña', $fashionReviewCount) }}</em>
                </a>
            @endif
            <div class="fashion-product-price">
                @if($showsOfferPricing)
                    <span>${{ number_format((float) $product->offer_original_price, 0, ',', '.') }}</span>
                @endif
                <strong>${{ number_format((float) $fashionProductPrice, 0, ',', '.') }}</strong>
            </div>
            @if($fashionStockLabel)
                <div class="fashion-product-stock-state {{ $isProductSoldOut ? 'is-sold-out' : '' }}">{{ $fashionStockLabel }}</div>
            @endif
            @if($product->hasWholesalePricing($store))
                <span class="product-wholesale-note product-wholesale-note--detail">Mayorista desde {{ $product->wholesale_min_quantity }} unidades: ${{ number_format((float) $product->wholesale_price, 0, ',', '.') }}</span>
            @endif

            <div class="fashion-product-compact-info" aria-label="Informacion rapida del producto">
                <div class="fashion-product-compact-card" data-fashion-compact-card>
                    <div class="fashion-product-compact-row">
                        <span class="fashion-product-compact-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M7 4h7l3 3v13H7z"></path>
                                <path d="M14 4v4h4"></path>
                                <path d="M9 12h6"></path>
                                <path d="M9 15h5"></path>
                            </svg>
                        </span>
                        <span class="fashion-product-compact-copy">
                            <strong>Descripción</strong>
                            <em data-fashion-collapsible>{!! $renderFashionProductText($fashionDescription) !!}</em>
                        </span>
                        <button type="button" class="fashion-product-compact-toggle" data-fashion-compact-toggle aria-expanded="false" hidden>
                            <span>Ver más</span>
                            <span>Ver menos</span>
                        </button>
                    </div>
                </div>

                @if($product->material)
                    <div class="fashion-product-compact-card">
                        <div class="fashion-product-compact-row">
                            <span class="fashion-product-compact-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"></path>
                                    <path d="M12 12 4 7.5"></path>
                                    <path d="m12 12 8-4.5"></path>
                                    <path d="M12 12v9"></path>
                                </svg>
                            </span>
                            <span class="fashion-product-compact-copy">
                                <strong>Material</strong>
                                <em>{{ $product->material }}</em>
                            </span>
                        </div>
                    </div>
                @endif

                @if($fashionFeaturesText !== '')
                    <div class="fashion-product-compact-card" data-fashion-compact-card>
                        <div class="fashion-product-compact-row">
                            <span class="fashion-product-compact-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <path d="M5 12l4 4L19 6"></path>
                                    <path d="M5 19h14"></path>
                                </svg>
                            </span>
                            <span class="fashion-product-compact-copy">
                                <strong>Características</strong>
                                <em data-fashion-collapsible>{!! $renderFashionProductText($fashionFeaturesText) !!}</em>
                            </span>
                            <button type="button" class="fashion-product-compact-toggle" data-fashion-compact-toggle aria-expanded="false" hidden>
                                <span>Ver más</span>
                                <span>Ver menos</span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            @if($isProductSoldOut)
                <div class="fashion-product-unavailable">Este producto está agotado por ahora.</div>
            @else
                <form action="{{ route('cart.add', $product->id) }}" method="POST" class="fashion-product-form add-to-cart-form" data-role="minimal-add-form">
                    @csrf
                    <input type="hidden" data-role="buy-now-quantity" value="1">

                    @if($fashionColorOptions->isNotEmpty())
                    <div class="fashion-option-group">
                        <div class="fashion-option-head">
                            <span>COLOR</span>
                        </div>
                        <div class="fashion-color-options">
                            @foreach($fashionColorOptions as $color)
                                <label title="{{ $color['label'] }}">
                                    <input type="radio" name="color" value="{{ $color['value'] }}" required data-role="selected-color-radio">
                                    <span style="--swatch-color: {{ $color['swatch'] }}"></span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($fashionSizes->isNotEmpty())
                        <div class="fashion-option-group">
                            <div class="fashion-option-head">
                                <span>TALLA</span>
                            </div>
                            <div class="fashion-size-options">
                                @foreach($fashionSizes as $size)
                                    <label>
                                        <input type="radio" name="size" value="{{ $size }}" required data-role="selected-size-radio">
                                        <span>{{ $size }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="fashion-product-actions {{ $fashionWhatsappUrl ? 'has-whatsapp' : 'no-whatsapp' }}">
                        <div class="fashion-quantity-control" aria-label="Cantidad">
                            <button type="button" data-quantity-minus aria-label="Disminuir cantidad">-</button>
                            <input id="quantity" type="number" name="quantity" value="1" min="1" max="99" inputmode="numeric" aria-label="Cantidad">
                            <button type="button" data-quantity-plus aria-label="Aumentar cantidad">+</button>
                        </div>
                        <button
                            type="submit"
                            class="fashion-product-cart-action"
                            data-variant-action
                            data-variant-add-action
                            data-enabled-label="Agregar al carrito"
                            @disabled($product->hasVariants())
                        >
                            {!! $fashionCartIcon !!}
                            <span data-variant-label>{{ $product->hasVariants() ? 'Selecciona una opción' : 'Agregar al carrito' }}</span>
                        </button>
                        <button
                            type="submit"
                            formaction="{{ route('cart.buy_now', $product->id) }}"
                            formmethod="POST"
                            class="fashion-product-buy-now-action"
                            data-direct-submit
                            data-variant-action
                            @disabled($product->hasVariants())
                        >
                            {!! $fashionBuyIcon !!}
                            <span>Comprar ahora</span>
                        </button>
                        @if($fashionWhatsappUrl)
                            <a class="fashion-product-whatsapp-action" href="{{ $fashionWhatsappUrl }}" target="_blank" rel="noopener" aria-label="Pedir por WhatsApp">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M5.2 19.2l1-3.5a7.4 7.4 0 1 1 2.6 2.5z"></path>
                                    <path d="M9.7 8.7c.2-.5.4-.5.7-.5h.5c.2 0 .4 0 .6.4l.8 1.8c.1.2.1.4 0 .6l-.4.5c-.1.2-.2.3 0 .6.4.8 1.5 2.1 2.9 2.7.3.2.5.1.7-.1l.7-.8c.2-.2.4-.2.6-.1l1.7.8c.3.1.4.3.4.5 0 .6-.5 1.4-1.1 1.6-.8.4-2.6.3-4.8-1.1-2.4-1.4-4-3.7-4.2-5.3-.1-.8.1-1.4.3-1.6z"></path>
                                </svg>
                                <span>WhatsApp</span>
                            </a>
                        @endif
                    </div>
                </form>
            @endif

            <section class="fashion-product-share" aria-label="Compartir producto">
                <h2>Compartir</h2>
                <div class="fashion-product-share-actions">
                    <a
                        href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrlEncoded }}"
                        class="fashion-product-share-button"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Compartir en Facebook"
                    >
                        <img src="{{ asset('images/icons/icon-facebook.png') }}" alt="" aria-hidden="true">
                        <span>Facebook</span>
                    </a>
                    <a
                        href="https://wa.me/?text={{ $shareTextEncoded }}%20{{ $shareUrlEncoded }}"
                        class="fashion-product-share-button"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Compartir por WhatsApp"
                    >
                        <img src="{{ asset('images/icons/icon-whatsapp.png') }}" alt="" aria-hidden="true">
                        <span>WhatsApp</span>
                    </a>
                    <a
                        href="https://twitter.com/intent/tweet?url={{ $shareUrlEncoded }}&text={{ $shareTextEncoded }}"
                        class="fashion-product-share-button"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Compartir en X"
                    >
                        <img src="{{ asset('images/icons/icon-x.png') }}" alt="" aria-hidden="true">
                        <span>X</span>
                    </a>
                    <button
                        type="button"
                        class="fashion-product-share-button"
                        data-copy-product-link="{{ $metaUrl }}"
                        aria-label="Copiar enlace del producto"
                    >
                        <img src="{{ asset('images/icons/icon-copiar-enlace.png') }}" alt="" aria-hidden="true">
                        <span>Copiar</span>
                    </button>
                </div>
            </section>

        </aside>
    </section>

    @if($fashionReviewsEnabled)
        <section class="fashion-product-reviews" id="fashionProductReviews" aria-label="Reseñas del producto">
            <div class="fashion-product-reviews-head">
                <div>
                    <h2>Reseñas</h2>
                    <p>Opiniones de clientes sobre este producto.</p>
                </div>

                @if($fashionReviewCount > 0)
                    <div class="fashion-product-review-score" aria-label="{{ $fashionReviewLabel }}">
                        <strong>{{ number_format((float) $fashionReviewAverage, 1) }}</strong>
                        <span aria-hidden="true">★★★★★</span>
                        <small>{{ $fashionReviewCount }} {{ \Illuminate\Support\Str::plural('reseña', $fashionReviewCount) }}</small>
                    </div>
                @endif
            </div>

            @if(session('review_success'))
                <div class="fashion-product-review-alert">{{ session('review_success') }}</div>
            @endif

            @if($fashionReviews->isNotEmpty())
                <div class="fashion-product-review-list">
                    @foreach($fashionReviews as $review)
                        <article>
                            <div>
                                <strong>{{ $review->name }}</strong>
                                <span>{{ number_format((float) $review->rating, 1) }} ★</span>
                            </div>
                            @if($review->comment)
                                <p>{{ \Illuminate\Support\Str::limit($review->comment, 140) }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif

            <details class="fashion-product-review-write">
                <summary>Escribir reseña</summary>
                <form action="{{ route('product.reviews.store', $product) }}" method="POST" class="fashion-product-review-form">
                    @csrf
                    <label>
                        <span>Nombre</span>
                        <input type="text" name="name" value="{{ old('name') }}" maxlength="80" required>
                    </label>
                    <label>
                        <span>Calificación</span>
                        <select name="rating" required>
                            @for($rating = 5; $rating >= 1; $rating--)
                                <option value="{{ $rating }}" @selected((int) old('rating', 5) === $rating)>{{ $rating }} estrellas</option>
                            @endfor
                        </select>
                    </label>
                    <label class="fashion-product-review-form-comment">
                        <span>Comentario</span>
                        <textarea name="comment" rows="3" maxlength="1000">{{ old('comment') }}</textarea>
                    </label>
                    <button type="submit">Enviar reseña</button>
                </form>
            </details>
        </section>
    @endif

    @if($fashionRelated->isNotEmpty())
        <section class="fashion-related">
            <h2>También te puede interesar</h2>
            <div class="fashion-related-grid">
                @foreach($fashionRelated as $relatedProduct)
                    @php($relatedPrice = (float) $relatedProduct->price)
                    <article class="fashion-related-card">
                        <a href="{{ $storefrontUrls->product($store, $relatedProduct) }}" class="fashion-related-media">
                            @if($relatedProduct->image)
                                <img src="{{ asset('storage/' . $relatedProduct->image) }}" alt="{{ $relatedProduct->name }}" loading="lazy" decoding="async">
                            @else
                                <span>{{ $relatedProduct->name }}</span>
                            @endif
                        </a>
                        @if($relatedProduct->category)
                            <p>{{ $relatedProduct->category }}</p>
                        @endif
                        <h3>{{ $relatedProduct->name }}</h3>
                        <strong>${{ number_format((float) $relatedPrice, 0, ',', '.') }}</strong>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</main>
