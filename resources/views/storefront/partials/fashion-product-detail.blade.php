@php
    $fashionGallery = $productGallery->isNotEmpty() ? $productGallery : collect([null]);
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
    $fashionDescription = $fashionDescription ?: 'Este producto aun no tiene una descripcion amplia configurada.';
    $fashionFeaturesText = $productFeaturesText ?? \App\Support\ProductText::featureLines($product->features);
    $fashionFeatureItems = collect(preg_split('/\R+/', $fashionFeaturesText) ?: [])
        ->map(fn ($item) => trim($item, " \t\n\r\0\x0B-*•"))
        ->filter()
        ->values();
@endphp

<main class="fashion-product-shell">
    <nav class="fashion-product-breadcrumb" aria-label="Ruta de navegacion">
        <a href="{{ $storefrontUrls->home($store) }}">Home</a>
        <span>&rsaquo;</span>
        <a href="{{ $storefrontUrls->products($store) }}">Shop</a>
        @if($product->category)
            <span>&rsaquo;</span>
            <a href="{{ $storefrontUrls->products($store) }}">{{ $product->category }}</a>
        @endif
        <span>&rsaquo;</span>
        <span>{{ $product->name }}</span>
    </nav>

    <section class="fashion-product-hero">
        <div class="fashion-product-gallery product-carousel" data-product-carousel>
            <div class="fashion-product-thumbs" aria-label="Imagenes del producto">
                @foreach($fashionGallery as $index => $galleryImage)
                    <button
                        type="button"
                        class="fashion-product-thumb {{ $index === 0 ? 'is-active' : '' }}"
                        data-carousel-thumb="{{ $index }}"
                        aria-label="Ver imagen {{ $index + 1 }}"
                        aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                    >
                        @if($galleryImage)
                            <img src="{{ asset('storage/' . $galleryImage) }}" alt="" loading="lazy" decoding="async">
                        @else
                            <span>{{ substr($product->name, 0, 1) }}</span>
                        @endif
                    </button>
                @endforeach
                @if($fashionGallery->count() < 4)
                    @for($thumbIndex = $fashionGallery->count(); $thumbIndex < 4; $thumbIndex++)
                        <button type="button" class="fashion-product-thumb" disabled aria-hidden="true">
                            <span>{{ substr($product->name, 0, 1) }}</span>
                        </button>
                    @endfor
                @endif
            </div>

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

            <p class="fashion-product-summary">{{ $fashionDescription }}</p>

            @if($isProductSoldOut)
                <div class="fashion-product-unavailable">Este producto está agotado por ahora.</div>
            @else
                <form action="{{ route('cart.add', $product->id) }}" method="POST" class="fashion-product-form add-to-cart-form">
                    @csrf
                    <input type="hidden" name="quantity" value="1">

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
                                <span>SIZE</span>
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
                        <button
                            type="submit"
                            data-variant-action
                            data-variant-add-action
                            data-enabled-label="Agregar al carrito"
                            @disabled($product->hasVariants())
                        >
                            {!! $fashionCartIcon !!}
                            <span data-variant-label>{{ $product->hasVariants() ? 'Selecciona una opcion' : 'Agregar al carrito' }}</span>
                        </button>
                        <a href="{{ $storefrontUrls->products($store) }}" aria-label="Agregar a favoritos">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M20.8 5.6a5.2 5.2 0 0 0-7.4 0L12 7l-1.4-1.4a5.2 5.2 0 0 0-7.4 7.4L12 21.8l8.8-8.8a5.2 5.2 0 0 0 0-7.4Z"></path>
                            </svg>
                        </a>
                    </div>
                </form>
            @endif

            <ul class="fashion-product-benefits">
                <li>Free Shipping on orders over $100</li>
                <li>Easy 30-Day Returns</li>
                <li>Secure Payments</li>
            </ul>
        </aside>
    </section>

    <section class="fashion-product-story" aria-label="Informacion del producto">
        <div class="fashion-product-story-grid fashion-product-story-grid--text-only">
            <article id="fashionDescription" class="fashion-product-text-block">
                <h2>Descripcion</h2>
                <p>{{ $fashionDescription }}</p>
            </article>

            @if($fashionFeatureItems->isNotEmpty())
                <article id="fashionFeatures" class="fashion-product-text-block">
                    <h2>Caracteristicas</h2>
                    <ul>
                        @foreach($fashionFeatureItems as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </article>
            @endif
        </div>
    </section>

    @if($fashionRelated->isNotEmpty())
        <section class="fashion-related">
            <h2>You May Also Like</h2>
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
                        <p>{{ $relatedProduct->category ?: 'Jackets' }}</p>
                        <h3>{{ $relatedProduct->name }}</h3>
                        <strong>${{ number_format((float) $relatedProduct->price, 0, ',', '.') }}</strong>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</main>
