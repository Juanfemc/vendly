@php
    $fashionGalleryImages = $productGallery->filter()->values();
    $fashionGallery = $fashionGalleryImages->isNotEmpty() ? $fashionGalleryImages : collect([null]);
    $fashionRelated = $relatedProducts->take(4);
    $fashionSizes = $product->hasSizes() ? collect($product->sizes)->values() : collect();
    $fashionColors = $product->hasColors() ? collect($product->colors)->values() : collect();
    $fashionCartIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="20" r="1.6"/><circle cx="18" cy="20" r="1.6"/><path d="M3 4h2.4l2.2 10.4a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 8H7"/></svg>';
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
    $splitFashionCompactText = function (string $text, int $limit = 150): array {
        $text = trim($text);

        if ($text === '' || \Illuminate\Support\Str::length($text) <= $limit) {
            return ['intro' => $text, 'rest' => ''];
        }

        $intro = \Illuminate\Support\Str::substr($text, 0, $limit);
        $lastSpace = max((int) strrpos($intro, ' '), (int) strrpos($intro, "\n"));

        if ($lastSpace !== false && $lastSpace > 80) {
            $intro = rtrim(substr($intro, 0, $lastSpace));
        }

        $rest = ltrim(\Illuminate\Support\Str::substr($text, \Illuminate\Support\Str::length($intro)));

        return ['intro' => $intro, 'rest' => $rest];
    };
    $fashionDescriptionParts = $splitFashionCompactText($fashionDescription, 155);
    $fashionFeaturesParts = $splitFashionCompactText($fashionFeaturesText, 140);
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
        <a href="{{ $storefrontUrls->products($store) }}">Tienda</a>
        @if($product->category)
            <span>&rsaquo;</span>
            <a href="{{ $storefrontUrls->products($store) }}">{{ $product->category }}</a>
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
                <button type="button" class="fashion-product-zoom" aria-label="Ampliar imagen">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="11" cy="11" r="6"></circle>
                        <path d="m16 16 4 4"></path>
                    </svg>
                </button>
            </div>
        </div>

        <aside class="fashion-product-info">
            @if($product->category)
                <span class="fashion-product-kicker">{{ $product->category }}</span>
            @endif
            <h1>{{ $product->name }}</h1>
            <div class="fashion-product-price">
                @if($showsOfferPricing)
                    <span>${{ number_format((float) $product->offer_original_price, 0, ',', '.') }}</span>
                @endif
                <strong>${{ number_format((float) $product->price, 0, ',', '.') }}</strong>
            </div>

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
                            <em><span>{{ $fashionDescriptionParts['intro'] }}</span>@if($fashionDescriptionParts['rest'] !== '')<span class="fashion-product-compact-ellipsis" aria-hidden="true">...</span><span class="fashion-product-compact-rest" hidden> {{ $fashionDescriptionParts['rest'] }}</span>@endif</em>
                        </span>
                        @if($fashionDescriptionParts['rest'] !== '')
                            <button type="button" class="fashion-product-compact-toggle" data-fashion-compact-toggle aria-expanded="false">
                                <span>Ver más</span>
                                <span>Ver menos</span>
                            </button>
                        @endif
                    </div>
                </div>

                @if($fashionFeaturesParts['intro'] !== '')
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
                                <em><span>{{ $fashionFeaturesParts['intro'] }}</span>@if($fashionFeaturesParts['rest'] !== '')<span class="fashion-product-compact-ellipsis" aria-hidden="true">...</span><span class="fashion-product-compact-rest" hidden> {{ $fashionFeaturesParts['rest'] }}</span>@endif</em>
                            </span>
                            @if($fashionFeaturesParts['rest'] !== '')
                                <button type="button" class="fashion-product-compact-toggle" data-fashion-compact-toggle aria-expanded="false">
                                    <span>Ver más</span>
                                    <span>Ver menos</span>
                                </button>
                            @endif
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

                    <div class="fashion-product-actions">
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

        </aside>
    </section>

    @if($fashionRelated->isNotEmpty())
        <section class="fashion-related">
            <h2>También te puede interesar</h2>
            <div class="fashion-related-grid">
                @foreach($fashionRelated as $relatedProduct)
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
                        <strong>${{ number_format((float) $relatedProduct->price, 0, ',', '.') }}</strong>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</main>
