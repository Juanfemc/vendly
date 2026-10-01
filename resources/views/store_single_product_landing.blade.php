<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $page = \App\View\Models\StorefrontPageViewModel::from($store);
        $storefrontUrls = app(\App\Services\StorefrontUrlService::class);
        $brandTheme = \App\Support\BrandTheme::from($store->brand_color);
        $storageUrl = fn (?string $path) => $path ? asset('storage/' . $path) : null;
        $absoluteStorageUrl = fn (?string $path) => $page->storageUrl($path);
        $galleryImages = collect([$product->image])
            ->merge($product->images ?? [])
            ->filter()
            ->unique()
            ->values();
        $heroImage = $storageUrl($galleryImages->first());
        $seoImage = $absoluteStorageUrl($galleryImages->first()) ?: $absoluteStorageUrl($store->cover_image) ?: $absoluteStorageUrl($store->logo_image);
        $headline = trim((string) $landing->headline) ?: $product->name;
        $eyebrow = trim((string) $landing->eyebrow) ?: 'Producto destacado';
        $subtitle = trim((string) $landing->subtitle) ?: ($product->category ?: 'Compra facil y segura');
        $description = trim((string) $landing->description) ?: trim((string) $product->description);
        $features = collect($landing->featuresList());
        if ($features->isEmpty()) {
            $features = collect([
                ['title' => 'Compra segura', 'description' => 'Finaliza tu pedido desde el checkout de Vendly.'],
                ['title' => 'Envio disponible', 'description' => 'Elige el metodo disponible para tu pedido.'],
                ['title' => 'Atencion por WhatsApp', 'description' => 'Coordina detalles con la tienda si lo necesitas.'],
            ]);
        }
        $faqs = collect($landing->faqList());
        if ($faqs->isEmpty()) {
            $faqs = collect([
                ['question' => 'Cuanto tarda en llegar mi pedido?', 'answer' => 'El tiempo depende del metodo de envio disponible para tu ciudad.'],
                ['question' => 'Que medios de pago aceptan?', 'answer' => 'Puedes pagar con los medios activos en el checkout de la tienda.'],
                ['question' => 'Puedo comprar mas de una unidad?', 'answer' => 'Si, puedes elegir la cantidad antes de finalizar el pedido.'],
            ]);
        }
        $reviewImages = collect($landing->reviewImages());
        $productReviews = ($store->allowsProductReviews() && $product->relationLoaded('approvedReviews'))
            ? $product->approvedReviews
            : collect();
        $reviewCount = (int) ($product->reviews_count ?? $productReviews->count());
        $averageRating = (float) ($product->reviews_avg_rating ?? 0);
        $shippingMethods = collect($store->shippingMethods());
        $firstShippingMethod = $shippingMethods->first();
        $shippingText = trim((string) $landing->bundle_shipping_text)
            ?: ($firstShippingMethod ? $firstShippingMethod['name'] : 'Envio disponible');
        $shippingCost = $firstShippingMethod ? (float) $store->shippingCostForSubtotal($firstShippingMethod, (float) $product->price) : null;
        $videoUrl = trim((string) $landing->video_url);
        $videoEmbedUrl = null;
        if ($videoUrl !== '') {
            if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]{6,})~', $videoUrl, $matches)) {
                $videoEmbedUrl = 'https://www.youtube.com/embed/' . $matches[1];
            } elseif (preg_match('~vimeo\.com/(?:video/)?([0-9]+)~', $videoUrl, $matches)) {
                $videoEmbedUrl = 'https://player.vimeo.com/video/' . $matches[1];
            }
        }
        $bundleEnabled = (bool) $landing->bundle_enabled;
        $bundleQuantity = max(2, (int) ($landing->bundle_quantity ?: 2));
        $bundleUnitPrice = $product->hasWholesalePricing($store) && $bundleQuantity >= (int) $product->wholesale_min_quantity
            ? (float) $product->wholesale_price
            : (float) $product->price;
        $bundlePrice = $bundleUnitPrice * $bundleQuantity;
        $bundleBadge = trim((string) $landing->bundle_badge) ?: 'Mas popular';
        $cartCount = $page->cartCount;
        $previewMode = (bool) ($previewMode ?? false);
        $metaUrl = $storefrontUrls->home($store);
        $seo = \App\Support\SeoMeta::storeHome($store, $metaUrl, $seoImage, $description ?: $headline, $storefrontUrls->favicon($store));
    @endphp
    @include('storefront.partials.seo', ['seo' => $seo])
    @include('storefront.partials.meta-pixel', ['store' => $store])
    <link rel="stylesheet" href="{{ asset('css/storefront-single-product-landing.css') }}?v={{ filemtime(public_path('css/storefront-single-product-landing.css')) }}">
</head>

<body
    class="storefront-page storefront-page--single-product-landing {{ $previewMode ? 'is-previewing-landing' : '' }}"
    data-csrf="{{ csrf_token() }}"
    style="{{ $store->storefrontCssVariables($brandTheme, 2) }}"
>
    @include('storefront.partials.meta-pixel-noscript', ['store' => $store])

    @if($previewMode)
        <div class="single-landing-preview-bar">Vista previa privada. La landing solo es publica si un admin la activa.</div>
    @endif

    <div class="single-landing-topbar">
        <span>{{ $shippingText }}</span>
        <span>Pago seguro</span>
        <span>{{ $reviewCount > 0 ? number_format($averageRating, 1) . ' de calificacion' : 'Compra facil en Vendly' }}</span>
    </div>

    <header class="single-landing-header">
        <a class="single-landing-brand" href="{{ $storefrontUrls->home($store) }}">
            @if($store->logo_image)
                <img src="{{ $storageUrl($store->logo_image) }}" alt="{{ $store->name }}">
            @else
                <strong>{{ $store->name }}</strong>
            @endif
        </a>
        <nav aria-label="Secciones de la landing">
            <a href="#producto">Producto</a>
            <a href="#video">Video</a>
            <a href="#resenas">Reseñas</a>
            <a href="#faq">FAQ</a>
        </nav>
        <a class="single-landing-cart" href="{{ route('cart.index', ['store' => $store->slug]) }}" aria-label="Ver carrito">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="21" r="1.5"></circle><circle cx="19" cy="21" r="1.5"></circle><path d="M2 3h3l3 13h10l3-9H6"></path></svg>
            <span>{{ $cartCount }}</span>
        </a>
    </header>

    <main>
        <section class="single-landing-hero" id="producto">
            <div class="single-landing-copy">
                <div class="single-landing-product-meta">
                    @if($product->category)
                        <span>{{ $product->category }}</span>
                    @endif
                    @if($product->isSoldOut())
                        <span>Agotado</span>
                    @elseif((int) ($product->stock_quantity ?? 0) > 0)
                        <span>{{ (int) $product->stock_quantity }} disponibles</span>
                    @else
                        <span>Disponible</span>
                    @endif
                </div>
                <span class="single-landing-eyebrow">{{ $eyebrow }}</span>
                <h1>{{ $headline }}</h1>
                <p>{{ $subtitle }}</p>
                @if($description !== '')
                    <p class="single-landing-description">{{ $description }}</p>
                @endif
                @if($reviewCount > 0)
                    <div class="single-landing-rating" aria-label="{{ number_format($averageRating, 1) }} de 5 en {{ $reviewCount }} reseñas">
                        <span>{{ str_repeat('★', max(1, min(5, (int) round($averageRating ?: 5)))) }}</span>
                        <b>{{ number_format($averageRating ?: 5, 1) }}</b>
                        <small>{{ $reviewCount }} reseñas</small>
                    </div>
                @endif
                <div class="single-landing-feature-row">
                    @foreach($features->take(4) as $feature)
                        <span>
                            <b>{{ $feature['title'] }}</b>
                            @if(($feature['description'] ?? '') !== '')
                                <small>{{ $feature['description'] }}</small>
                            @endif
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="single-landing-gallery">
                <div class="single-landing-main-image">
                    @if($heroImage)
                        <img src="{{ $heroImage }}" alt="{{ $product->name }}" data-landing-main-image>
                    @else
                        <span>{{ $product->name }}</span>
                    @endif
                </div>
                @if($galleryImages->count() > 1)
                    <div class="single-landing-thumbs" aria-label="Imagenes del producto">
                        @foreach($galleryImages as $image)
                            <button type="button" class="{{ $loop->first ? 'is-active' : '' }}" data-landing-thumb="{{ $storageUrl($image) }}" aria-label="Ver imagen {{ $loop->iteration }}">
                                <img src="{{ $storageUrl($image) }}" alt="">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <aside class="single-landing-buy-card">
                <div class="single-landing-buy-head">
                    <span>Oferta activa</span>
                    <strong>{{ $product->name }}</strong>
                </div>
                <div class="single-landing-shipping">
                    <strong>{{ $shippingText }}</strong>
                    <span>
                        @if($shippingCost === null)
                            Costo segun disponibilidad
                        @elseif($shippingCost > 0)
                            Desde ${{ number_format($shippingCost, 0, ',', '.') }}
                        @else
                            Gratis
                        @endif
                    </span>
                </div>

                <form id="singleLandingBuyForm" method="POST" action="{{ route('cart.buy_now', $product->id) }}">
                    @csrf

                    <div class="single-landing-offers">
                        <label class="single-landing-offer is-selected">
                            <input type="radio" name="quantity" value="1" checked>
                            <span>
                                <strong>Compra 1 unidad</strong>
                                <b>${{ number_format((float) $product->price, 0, ',', '.') }}</b>
                            </span>
                        </label>

                        @if($bundleEnabled)
                            <label class="single-landing-offer single-landing-offer--featured">
                                <input type="radio" name="quantity" value="{{ $bundleQuantity }}">
                                <span>
                                    <strong>Pack x{{ $bundleQuantity }}</strong>
                                    <b>${{ number_format($bundlePrice, 0, ',', '.') }}</b>
                                    <small>{{ $bundleBadge }}</small>
                                </span>
                            </label>
                        @endif
                    </div>

                    @if($product->hasSizes())
                        <fieldset class="single-landing-variants">
                            <legend>Talla</legend>
                            @foreach($product->sizes ?? [] as $size)
                                <label>
                                    <input type="radio" name="size" value="{{ $size }}" required>
                                    <span>{{ $size }}</span>
                                </label>
                            @endforeach
                        </fieldset>
                    @endif

                    @if($product->hasColors())
                        <fieldset class="single-landing-variants">
                            <legend>Color</legend>
                            @foreach($product->colors ?? [] as $color)
                                <label>
                                    <input type="radio" name="color" value="{{ $color }}" required>
                                    <span>{{ $color }}</span>
                                </label>
                            @endforeach
                        </fieldset>
                    @endif

                    <button class="single-landing-primary" type="submit" @disabled($product->isSoldOut())>
                        {{ $product->isSoldOut() ? 'Producto agotado' : 'Comprar ahora' }}
                    </button>
                </form>

                <div class="single-landing-trust-strip" aria-label="Beneficios de compra">
                    <span>Compra segura</span>
                    <span>Pedido protegido</span>
                    <span>Soporte tienda</span>
                </div>
            </aside>
        </section>

        <section class="single-landing-section single-landing-video" id="video">
            <div>
                <span class="single-landing-eyebrow">Ver video</span>
                <h2>{{ trim((string) $landing->video_title) ?: 'Conoce el producto antes de comprar' }}</h2>
                <p>{{ trim((string) $landing->video_description) ?: 'Mira los detalles, el uso y la experiencia del producto.' }}</p>
            </div>
            @if($videoEmbedUrl)
                <div class="single-landing-video-card single-landing-video-card--embed">
                    <iframe src="{{ $videoEmbedUrl }}" title="{{ trim((string) $landing->video_title) ?: 'Video del producto' }}" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                </div>
            @else
                <a class="single-landing-video-card" href="{{ $videoUrl ?: '#producto' }}" target="{{ $videoUrl ? '_blank' : '_self' }}" rel="noopener">
                    @if($heroImage)
                        <img src="{{ $heroImage }}" alt="">
                    @endif
                    <span>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 7v10l8-5-8-5Z"></path></svg>
                        Ver video
                    </span>
                </a>
            @endif
        </section>

        <section class="single-landing-section">
            <span class="single-landing-eyebrow">Caracteristicas</span>
            <h2>Lo que hace diferente este producto</h2>
            <div class="single-landing-feature-grid">
                @foreach($features as $feature)
                    <article>
                        <span>{{ $loop->iteration }}</span>
                        <h3>{{ $feature['title'] }}</h3>
                        @if(($feature['description'] ?? '') !== '')
                            <p>{{ $feature['description'] }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>

        @if($reviewImages->isNotEmpty() || $productReviews->isNotEmpty())
            <section class="single-landing-section" id="resenas">
                <span class="single-landing-eyebrow">Reseñas de clientes</span>
                <h2>Clientes que ya compraron</h2>
                @if($reviewImages->isNotEmpty())
                    <div class="single-landing-whatsapp-reviews">
                        @foreach($reviewImages->take(6) as $image)
                            <img src="{{ $storageUrl($image) }}" alt="Captura de reseña por WhatsApp">
                        @endforeach
                    </div>
                @endif
                @if($productReviews->isNotEmpty())
                    <div class="single-landing-review-cards">
                        @foreach($productReviews->take(3) as $review)
                            <article>
                                <strong>{{ $review->name }}</strong>
                                <span>{{ str_repeat('★', max(1, (int) $review->rating)) }}</span>
                                <p>{{ $review->comment }}</p>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        <section class="single-landing-section" id="faq">
            <span class="single-landing-eyebrow">Preguntas frecuentes</span>
            <h2>Antes de finalizar tu pedido</h2>
            <div class="single-landing-faqs">
                @foreach($faqs as $faq)
                    <details>
                        <summary>{{ $faq['question'] }}</summary>
                        <p>{{ $faq['answer'] }}</p>
                    </details>
                @endforeach
            </div>
        </section>
    </main>

    <div class="single-landing-mobile-buy">
        <span>
            <small>Desde</small>
            <strong>${{ number_format((float) $product->price, 0, ',', '.') }}</strong>
        </span>
        <button type="submit" form="singleLandingBuyForm" @disabled($product->isSoldOut())>
            {{ $product->isSoldOut() ? 'Agotado' : 'Comprar ahora' }}
        </button>
    </div>

    @include('storefront.partials.meta-pixel-event', [
        'event' => 'ViewContent',
        'payload' => array_filter([
            'content_ids' => [(string) $product->id],
            'contents' => [[
                'id' => (string) $product->id,
                'quantity' => 1,
                'item_price' => (float) $product->price,
            ]],
            'content_name' => $product->name,
            'content_type' => 'product',
            'content_category' => $product->category,
            'value' => (float) $product->price,
            'currency' => 'COP',
        ], fn ($value) => $value !== null && $value !== ''),
    ])

    <script>
        document.querySelectorAll('[data-landing-thumb]').forEach((button) => {
            button.addEventListener('click', () => {
                const mainImage = document.querySelector('[data-landing-main-image]');
                if (!mainImage) return;
                mainImage.classList.add('is-changing');
                window.setTimeout(() => {
                    mainImage.src = button.dataset.landingThumb;
                    mainImage.classList.remove('is-changing');
                }, 120);
                document.querySelectorAll('[data-landing-thumb]').forEach((thumb) => thumb.classList.toggle('is-active', thumb === button));
            });
        });

        document.querySelectorAll('.single-landing-offer input[type="radio"]').forEach((input) => {
            input.addEventListener('change', () => {
                document.querySelectorAll('.single-landing-offer').forEach((offer) => {
                    const radio = offer.querySelector('input[type="radio"]');
                    offer.classList.toggle('is-selected', radio?.checked ?? false);
                });
            });
        });
    </script>
</body>

</html>
