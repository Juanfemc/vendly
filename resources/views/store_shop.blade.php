<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $page = \App\View\Models\StorefrontPageViewModel::from($store);
        $absoluteStorageUrl = fn (?string $path) => $page->storageUrl($path);
        $storageAssetUrl = fn (?string $path) => $path ? asset('storage/' . $path) : null;
        $isRestaurant = $store->isRestaurant();
        $catalogProducts = $products;
        $allProducts = $allProducts ?? collect($products->items());
        $heroImage = $storageAssetUrl($store->cover_image);
        $heroMetaImage = $absoluteStorageUrl($store->cover_image);
        $logoImage = $absoluteStorageUrl($store->logo_image);
        $faviconImage = $storefrontUrls->favicon($store);
        $seoImage = $heroMetaImage ?: $logoImage;
        $cartCount = $page->cartCount;
        $instagramUrl = $page->instagramUrl;
        $facebookUrl = $page->facebookUrl;
        $tiktokUrl = $page->tiktokUrl;
        $canManageStore = $page->canManageStore;
        $isTechnologyStore = $store->isTechnologyStore();
        $isFashionStore = $store->isFashionStore();
        $isSupplementStore = $store->isSupplementStore();
        $isReservationStore = $store->isReservationStore();
        $businessLabel = $isRestaurant ? 'Restaurante' : ($isReservationStore ? 'Reservas' : 'Tienda');
        $cartLabel = $isRestaurant ? 'Pedido' : ($isReservationStore ? 'Reserva' : 'Carrito');
        $collectionLabel = $isRestaurant ? 'menu' : ($isReservationStore ? 'servicios' : 'catalogo');
        $collectionLabelTitle = $isRestaurant ? 'Carta' : ($isReservationStore ? 'Servicios' : 'Catálogo');
        $itemsLabel = $isRestaurant ? 'platos' : ($isReservationStore ? 'servicios' : 'productos');
        $productsTotal = $storeProductsTotal ?? (method_exists($catalogProducts, 'total') ? $catalogProducts->total() : $allProducts->count());
        $buyNowLabel = $isRestaurant ? 'Pedir ahora' : ($isReservationStore ? 'Reservar ahora' : 'Comprar ahora');
        $addLabel = $isRestaurant ? 'Agregar al pedido' : ($isReservationStore ? 'Agregar a la reserva' : 'Agregar al carrito');
        $heroEyebrow = $isRestaurant
            ? 'Recomendados de la casa'
            : ($isTechnologyStore ? 'Lo último en tecnología' : ($isSupplementStore ? 'Bienestar y rendimiento' : ($isReservationStore ? 'Servicios disponibles' : 'Nueva colección')));
        $defaultHeroCopy = $isRestaurant
            ? 'Elige tus platos favoritos de la carta y envia tu pedido directo por WhatsApp.'
            : ($isTechnologyStore
                ? 'Descubre tecnología seleccionada para tu día a día, con una vitrina pensada para resolver compras rápido por WhatsApp.'
                : ($isSupplementStore
                    ? 'Encuentra suplementos para energia, fuerza y bienestar en una experiencia pensada para cerrar pedidos por WhatsApp.'
                    : ($isReservationStore
                        ? 'Explora nuestros servicios y solicita tu reserva rápido por WhatsApp.'
                        : 'Descubre nuestros productos y compra rápido desde una experiencia pensada para cerrar pedidos por WhatsApp.')));
        $heroShortCopy = trim((string) $store->shop_copy) !== '' ? trim((string) $store->shop_copy) : $defaultHeroCopy;
        $defaultShopCopy = $isRestaurant
            ? 'Revisa la carta de ' . ($store->name ?? 'el restaurante') . ', elige tus platos favoritos y envia tu pedido por WhatsApp.'
            : ($isTechnologyStore
                ? 'Explora la seleccion actual de tecnologia de ' . ($store->name ?? 'la tienda') . ' y agrega tus favoritos al carrito para cerrar tu compra por WhatsApp.'
                : ($isSupplementStore
                    ? 'Explora la linea actual de suplementos de ' . ($store->name ?? 'la tienda') . ' y agrega tus favoritos al carrito para cerrar tu pedido por WhatsApp.'
                    : ($isReservationStore
                        ? 'Explora los servicios disponibles de ' . ($store->name ?? 'la tienda') . ' y solicita tu reserva por WhatsApp.'
                        : 'Explora la colección actual de ' . ($store->name ?? 'la tienda') . ' y agrega tus favoritos al carrito para finalizar tu pedido por WhatsApp.')));
        $productDescriptionFallback = $isRestaurant
            ? 'Plato recomendado del restaurante.'
            : ($isTechnologyStore ? 'Producto de tecnologia.' : ($isSupplementStore ? 'Suplemento de la tienda.' : ($isReservationStore ? 'Servicio disponible para reservar.' : 'Producto de la tienda.')));
        $storefrontVariant = $isTechnologyStore ? 'technology' : ($isFashionStore ? 'fashion' : ($isRestaurant ? 'restaurant' : ($isSupplementStore ? 'supplements' : 'default')));
        $variantStylesheets = [
            'technology' => 'css/storefront-technology.css',
            'fashion' => 'css/storefront-fashion.css',
            'restaurant' => 'css/storefront-restaurant.css',
            'supplements' => 'css/storefront-supplements.css',
            'default' => 'css/storefront-default.css',
        ];
        $metaUrl = $storefrontUrls->home($store);
        $seo = \App\Support\SeoMeta::storeHome($store, $metaUrl, $seoImage, $defaultShopCopy, $faviconImage);
        $brandTheme = \App\Support\BrandTheme::from($store->brand_color);
        $responsiveProductColumns = in_array((int) $store->responsive_product_columns, [1, 2, 3], true) ? (int) $store->responsive_product_columns : 2;
        $showHeroProductsAction = (bool) ($store->show_hero_products_action ?? false);
        $supportsHeroOverlay = \App\Models\Store::supportsHeroOverlayColumns();
        $heroOverlayTitle = $supportsHeroOverlay ? trim((string) ($store->hero_overlay_title ?? '')) : '';
        $heroOverlayButtonText = $supportsHeroOverlay ? trim((string) ($store->hero_overlay_button_text ?? '')) : '';
        $heroOverlayButtonUrl = $supportsHeroOverlay ? trim((string) ($store->hero_overlay_button_url ?? '')) : '';
        $heroOverlayButtonUrl = $heroOverlayButtonUrl !== '' ? $heroOverlayButtonUrl : $storefrontUrls->products($store);
        $heroOverlayButtonText = $heroOverlayButtonText !== '' ? $heroOverlayButtonText : 'Comprar ahora';
        $showAboutSection = trim((string) $store->mission) !== '' && trim((string) $store->vision) !== '';
    @endphp
    @include('storefront.partials.seo', ['seo' => $seo])
    @include('storefront.partials.meta-pixel', ['store' => $store])
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}?v={{ filemtime(public_path('css/storefront.css')) }}">
    <link rel="stylesheet" href="{{ asset($variantStylesheets[$storefrontVariant]) }}?v={{ filemtime(public_path($variantStylesheets[$storefrontVariant])) }}">
</head>

<body
    class="storefront-page storefront-page--{{ $storefrontVariant }} storefront-cols-{{ $responsiveProductColumns }} {{ $storefrontVariant === 'technology' ? 'storefront-page--minimal-grid' : '' }}"
    data-csrf="{{ csrf_token() }}"
    data-adding-text="{{ $isRestaurant ? 'Agregando al pedido...' : ($isReservationStore ? 'Agregando a la reserva...' : 'Agregando...') }}"
    data-feedback-added="{{ $isRestaurant ? 'Plato agregado al pedido' : ($isReservationStore ? 'Servicio agregado a la reserva' : 'Producto agregado al carrito') }}"
    data-feedback-error="{{ $isRestaurant ? 'No pudimos agregar el plato' : ($isReservationStore ? 'No pudimos agregar el servicio' : 'No pudimos agregar el producto') }}"
    style="{{ $store->storefrontCssVariables($brandTheme, $responsiveProductColumns) }}"
>
    @include('storefront.partials.meta-pixel-noscript', ['store' => $store])

    @if($storefrontVariant === 'technology')
        @include('storefront.partials.header-minimal-grid')
    @elseif($storefrontVariant === 'fashion')
        @include('storefront.partials.header-fashion')
    @else
        @include('storefront.partials.header')
    @endif

    <main class="shell">
        @include('storefront.variants.' . $storefrontVariant)
    </main>

    @if($storefrontVariant === 'technology')
        @include('storefront.partials.footer-minimal-grid')
    @elseif($storefrontVariant === 'fashion')
        @include('storefront.partials.footer-fashion')
    @else
        @include('storefront.partials.footer')
    @endif

    <div class="cart-feedback" id="cartFeedback" aria-live="polite">{{ $isRestaurant ? 'Plato agregado al pedido' : ($isReservationStore ? 'Servicio agregado a la reserva' : 'Producto agregado al carrito') }}</div>

    <script src="{{ asset('js/storefront.js') }}?v={{ filemtime(public_path('js/storefront.js')) }}" defer></script>
    @if($storefrontVariant === 'technology')
        <script src="{{ asset('js/minimal-shop.js') }}?v={{ filemtime(public_path('js/minimal-shop.js')) }}" defer></script>
    @endif
</body>

</html>

