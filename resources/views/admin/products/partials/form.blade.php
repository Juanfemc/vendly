@php
    $formProduct = $product ?? null;
    $formStore = $formProduct?->store ?? ($store ?? null);
    $isEditing = (bool) $formProduct;
    $selectedCategory = old('category', $formProduct?->category);
    $descriptionValue = old('description') !== null
        ? \App\Support\ProductText::plain(old('description'))
        : \App\Support\ProductText::plain($formProduct?->description);
    $featuresEditorValue = old('features') !== null
        ? \App\Support\ProductText::rich(old('features'))
        : \App\Support\ProductText::rich($formProduct?->features);
    $featuresInputValue = old('features') !== null ? \App\Support\ProductText::rich(old('features')) : $featuresEditorValue;
    $galleryAllowed = auth()->user()->isAdmin() || ($formStore?->allowsProductGallery() ?? true);
    $categoriesAllowed = auth()->user()->isAdmin() || ($formStore?->allowsCategories() ?? true);
    $wholesaleAllowed = $formStore?->allowsWholesalePricing() ?? false;
    $showInventory = ! ($formStore?->isReservationStore() ?? false);
    $previewImage = $formProduct?->image ? asset('storage/' . $formProduct->image) : null;
    $previewName = old('name', $formProduct?->name) ?: 'Nombre del producto';
    $previewCategory = $selectedCategory ?: 'Categoria';
    $previewPrice = (float) old('price', $formProduct?->price ?? 0);
    $previewStock = old('stock_quantity', $formProduct?->stock_quantity);
    $previewIsSoldOut = (bool) old('is_sold_out', $formProduct?->is_sold_out);
    $previewHasOffer = (bool) old('has_offer', $formProduct?->has_offer);
    $previewBadges = collect(explode(',', (string) old('custom_badges', $formProduct ? implode(', ', $formProduct->customBadges()) : '')))
        ->map(fn ($badge) => trim($badge))
        ->filter()
        ->take(3)
        ->values();
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="product-editor-form">
    @csrf
    @if($isEditing)
        @method('PUT')
    @endif

    <div class="product-editor-workspace">
        <div class="product-editor-main">
    <section class="product-editor-card">
        <div class="product-editor-card__head">
            <span class="product-editor-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m21 8-9-5-9 5 9 5 9-5z"></path>
                    <path d="M3 8v8l9 5 9-5V8"></path>
                    <path d="M12 13v8"></path>
                </svg>
            </span>
            <div>
                <h3>Datos básicos</h3>
                <p>Información principal que verá el cliente en la tienda.</p>
            </div>
        </div>

        @if(auth()->user()->isAdmin())
            <div class="product-editor-field">
                <label for="store_id">Tienda del producto <span>*</span></label>
                <select name="store_id" id="store_id" required>
                    <option value="">Selecciona tienda</option>
                    @foreach (($stores ?? collect()) as $storeOption)
                        <option value="{{ $storeOption->id }}" @selected(old('store_id', $formProduct?->store_id) == $storeOption->id)>{{ $storeOption->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @include('admin.partials.ai-content-tools', ['aiStore' => $formStore, 'aiProduct' => $formProduct, 'aiContext' => 'product'])

        <div class="product-editor-grid">
            <div class="product-editor-field">
                <label for="name">Nombre del producto <span>*</span></label>
                <input id="name" type="text" name="name" value="{{ old('name', $formProduct?->name) }}" placeholder="Ej: Camiseta básica de algodón" required data-product-preview-field="name">
                <small>Usa un nombre claro y fácil de reconocer.</small>
            </div>

            <div class="product-editor-field">
                <label for="material">Material</label>
                <input id="material" type="text" name="material" value="{{ old('material', $formProduct?->material) }}" placeholder="Ej: Algodón, cuero, acero">
                <small>Opcional, pero ayuda a vender mejor.</small>
            </div>
        </div>

        @if($categoriesAllowed)
            <div class="product-editor-grid product-editor-grid--wide">
                <div class="product-editor-field">
                    <label for="category_select">Categoría</label>
                    <select name="category" id="category_select" data-product-preview-field="category">
                        <option value="">Selecciona categoría</option>
                        @foreach ($categoryOptions as $categoryOption)
                            @php
                                $categoryOptionValue = is_array($categoryOption) ? ($categoryOption['value'] ?? '') : $categoryOption;
                                $categoryOptionLabel = is_array($categoryOption) ? ($categoryOption['label'] ?? $categoryOptionValue) : $categoryOption;
                            @endphp
                            <option value="{{ $categoryOptionValue }}" @selected($selectedCategory === $categoryOptionValue)>{{ $categoryOptionLabel }}</option>
                        @endforeach
                    </select>
                    <small>Para crear una categoría nueva, ve a la sección Categorías.</small>
                </div>
            </div>
        @else
            <div class="product-editor-note">El plan {{ $formStore?->planLabel() ?? 'actual' }} no incluye categorías. Este producto quedará sin categoría.</div>
        @endif

        <div class="product-editor-field">
            <label for="description">Descripción</label>
            <textarea id="description" name="description" class="long-textarea product-editor-textarea" rows="6" maxlength="5000" placeholder="Describe materiales, beneficios, medidas, uso o detalles importantes.">{{ $descriptionValue }}</textarea>
            <small>Mientras más clara sea la descripción, menos dudas tendrá el cliente.</small>
        </div>

        <div class="product-editor-field">
            <label for="features_editor">Características</label>
            <div class="rich-editor product-editor-rich" data-rich-editor>
                <div class="rich-toolbar" aria-label="Herramientas de texto">
                    <button type="button" data-command="bold"><strong>B</strong></button>
                    <button type="button" data-command="italic"><em>I</em></button>
                    <button type="button" data-command="underline"><u>U</u></button>
                    <button type="button" data-command="insertUnorderedList">Lista</button>
                    <button type="button" data-command="insertOrderedList">1. Lista</button>
                </div>
                <div id="features_editor" class="rich-content" contenteditable="true" data-rich-content>{{ $featuresEditorValue }}</div>
                <textarea name="features" data-rich-input hidden>{{ $featuresInputValue }}</textarea>
            </div>
            <small>Agrega beneficios o detalles en frases cortas.</small>
        </div>
    </section>

    <section class="product-editor-card">
        <div class="product-editor-card__head">
            <span class="product-editor-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="3"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <path d="m21 15-5-5L5 21"></path>
                </svg>
            </span>
            <div>
                <h3>Imágenes</h3>
                <p>La primera imagen será la principal del producto.</p>
            </div>
        </div>

        @if($isEditing && $formProduct->image)
            <div class="product-editor-current-media">
                <img src="{{ asset('storage/' . $formProduct->image) }}" alt="{{ $formProduct->name }}">
                <div>
                    <strong>Imagen principal actual</strong>
                    <span>Puedes subir una nueva para reemplazarla.</span>
                </div>
            </div>
        @endif

        @if($isEditing && ($formStore?->allowsProductGallery() ?? true) && ! empty($formProduct->images))
            <div class="product-editor-gallery">
                @foreach ($formProduct->images as $productImage)
                    <label>
                        <img src="{{ asset('storage/' . $productImage) }}" alt="{{ $formProduct->name }}">
                        <span>
                            <input type="checkbox" name="remove_images[]" value="{{ $productImage }}">
                            Quitar
                        </span>
                    </label>
                @endforeach
            </div>
        @endif

        <div class="product-editor-upload-grid">
            <label class="product-editor-upload" for="product_image">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M12 16V4"></path>
                    <path d="m7 9 5-5 5 5"></path>
                    <path d="M20 16v4H4v-4"></path>
                </svg>
                <strong>{{ $isEditing ? 'Subir nueva imagen principal' : 'Subir imagen principal' }}</strong>
                <span>JPG, PNG o WebP. Máximo 2 MB.</span>
                <input id="product_image" type="file" name="image" accept="image/*" data-optimize-image data-max-width="1600" data-max-height="1600" data-quality="0.82" data-output="webp" data-max-size="2097152" data-product-preview-field="image">
            </label>

            @if($galleryAllowed)
                <label class="product-editor-upload product-editor-upload--accent" for="product_images">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="3"></rect>
                        <path d="M8 12h8"></path>
                        <path d="M12 8v8"></path>
                    </svg>
                    <strong>Agregar galería</strong>
                    <span>Hasta 8 imágenes adicionales.</span>
                    <input id="product_images" type="file" name="images[]" accept="image/*" multiple data-optimize-image data-max-width="1600" data-max-height="1600" data-quality="0.82" data-output="webp" data-max-size="2097152" data-max-total-size="8388608" data-product-image-preview data-preview-target="product_images_preview">
                </label>
            @else
                <div class="product-editor-upgrade">
                    <strong>Galería disponible desde Pro</strong>
                    <span>Mejora el plan para mostrar mas imágenes por producto.</span>
                </div>
            @endif
        </div>

        @if($galleryAllowed)
            <div id="product_images_preview" class="product-image-preview" hidden></div>
        @endif
    </section>

    <section class="product-editor-card">
        <div class="product-editor-card__head">
            <span class="product-editor-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18"></path>
                    <path d="M7 12h10"></path>
                    <path d="M10 18h4"></path>
                </svg>
            </span>
            <div>
                <h3>Precio e inventario</h3>
                <p>Define precio, disponibilidad y etiquetas comerciales.</p>
            </div>
        </div>

        <div class="product-editor-grid product-editor-grid--three">
            <div class="product-editor-field">
                <label for="price">Precio <span>*</span></label>
                <input id="price" type="number" step="0.01" name="price" value="{{ old('price', $formProduct?->price) }}" placeholder="0" required data-product-preview-field="price">
            </div>

            @if($formStore?->allowsOfferBadges())
                <div class="product-editor-field" data-offer-pricing>
                    <label for="offer_original_price">Precio antes</label>
                    <input id="offer_original_price" type="number" step="0.01" name="offer_original_price" value="{{ old('offer_original_price', $formProduct?->offer_original_price) }}" placeholder="Sin descuento" data-product-preview-field="priceBefore">
                </div>
            @endif

            @if($showInventory)
                <div class="product-editor-field">
                    <label for="stock_quantity">Stock disponible</label>
                    <input id="stock_quantity" type="number" name="stock_quantity" min="0" step="1" value="{{ old('stock_quantity', $formProduct?->stock_quantity) }}" placeholder="Ilimitado" data-product-preview-field="stock">
                </div>
            @endif
        </div>

        <div class="product-editor-options">
            @if($wholesaleAllowed)
                <label class="product-editor-switch">
                    <span>
                        <strong>Activar precio mayorista</strong>
                        <small>Se aplica automáticamente cuando el cliente alcanza la cantidad mínima.</small>
                    </span>
                    <input type="checkbox" name="has_wholesale_price" value="1" @checked(old('has_wholesale_price', $formProduct?->has_wholesale_price))>
                    <i></i>
                </label>
            @endif

            @if($formStore?->allowsOfferBadges())
                <label class="product-editor-switch">
                    <span>
                        <strong>Mostrar etiqueta de oferta</strong>
                        <small>El precio actual queda como precio de oferta.</small>
                    </span>
                    <input type="checkbox" name="has_offer" value="1" @checked(old('has_offer', $formProduct?->has_offer)) data-offer-toggle data-product-preview-field="offer">
                    <i></i>
                </label>
            @endif

            @if($showInventory)
                <label class="product-editor-switch">
                    <span>
                        <strong>Marcar como agotado</strong>
                        <small>Oculta la compra cuando no haya disponibilidad.</small>
                    </span>
                    <input type="checkbox" name="is_sold_out" value="1" @checked(old('is_sold_out', $formProduct?->is_sold_out)) data-product-preview-field="soldout">
                    <i></i>
                </label>
            @endif
        </div>

        @if($wholesaleAllowed)
            <div class="product-editor-grid">
                <div class="product-editor-field">
                    <label for="wholesale_min_quantity">Cantidad mínima mayorista</label>
                    <input id="wholesale_min_quantity" type="number" name="wholesale_min_quantity" min="2" step="1" value="{{ old('wholesale_min_quantity', $formProduct?->wholesale_min_quantity) }}" placeholder="Ej: 6">
                    <small>Desde esta cantidad se aplica el precio mayorista.</small>
                </div>

                <div class="product-editor-field">
                    <label for="wholesale_price">Precio mayorista</label>
                    <input id="wholesale_price" type="number" step="0.01" name="wholesale_price" value="{{ old('wholesale_price', $formProduct?->wholesale_price) }}" placeholder="Ej: 42000">
                    <small>Debe ser menor al precio normal del producto.</small>
                </div>
            </div>
        @elseif($formStore && ! $formStore->isBasicPlan())
            <div class="product-editor-note">Los precios mayoristas están disponibles sólo en plan Premium.</div>
        @endif

        @if($formStore?->allowsCustomProductBadges())
            <div class="product-editor-field">
                <label for="custom_badges">Etiquetas personalizadas</label>
                <input id="custom_badges" type="text" name="custom_badges" value="{{ old('custom_badges', $formProduct ? implode(', ', $formProduct->customBadges()) : '') }}" maxlength="255" placeholder="Ej: Nuevo, Más vendido, Últimas unidades" data-product-preview-field="badges">
                <small>Se muestran hasta 3 etiquetas cortas, separadas por coma.</small>
            </div>
        @endif
    </section>

    <section class="product-editor-card">
        <div class="product-editor-card__head">
            <span class="product-editor-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 7h18"></path>
                    <path d="M3 12h18"></path>
                    <path d="M3 17h18"></path>
                </svg>
            </span>
            <div>
                <h3>Variantes</h3>
                <p>Opciones simples para talla y color.</p>
            </div>
        </div>

        <div class="product-editor-grid">
            <div class="product-editor-field">
                <label for="sizes">Tallas disponibles</label>
                <input id="sizes" type="text" name="sizes" value="{{ old('sizes', $formProduct ? implode(', ', $formProduct->sizes ?? []) : '') }}" placeholder="Ej: S, M, L, XL">
                <small>
                    @if($formStore?->isFashionStore())
                        En la plantilla de ropa se muestran como botones. Separa cada talla con coma.
                    @else
                        Separa cada talla con coma.
                    @endif
                </small>
            </div>

            <div class="product-editor-field">
                <label for="colors">Colores disponibles</label>
                <input id="colors" type="text" name="colors" value="{{ old('colors', $formProduct ? implode(', ', $formProduct->colors ?? []) : '') }}" placeholder="Ej: Negro, Blanco, Rojo, #ff6600">
                <small>
                    @if($formStore?->isFashionStore())
                        En la plantilla de ropa se muestran como circulos. Usa nombres comunes o codigos HEX, separados por coma.
                    @else
                        Separa cada color con coma.
                    @endif
                </small>
            </div>
        </div>
    </section>
        </div>

        <aside class="product-editor-preview-panel" aria-label="Vista previa del producto" data-product-preview>
            <div class="product-editor-preview-panel__head">
                <span>Vista previa</span>
                <strong>Asi lo verá tu cliente</strong>
            </div>

            <article class="product-editor-preview-card">
                <div class="product-editor-preview-media">
                    @if($previewImage)
                        <img src="{{ $previewImage }}" alt="{{ $previewName }}" data-product-preview-image>
                    @else
                        <span data-product-preview-placeholder>{{ strtoupper(mb_substr($previewName, 0, 1)) }}</span>
                        <img src="" alt="" data-product-preview-image hidden>
                    @endif

                    <div class="product-editor-preview-badges" data-product-preview-badges @if($previewBadges->isEmpty() && ! $previewHasOffer) hidden @endif>
                        @if($previewHasOffer)
                            <span>Oferta</span>
                        @endif
                        @foreach($previewBadges as $badge)
                            <span>{{ $badge }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="product-editor-preview-copy">
                    <small data-product-preview-category>{{ $previewCategory }}</small>
                    <h3 data-product-preview-name>{{ $previewName }}</h3>
                    <div class="product-editor-preview-price">
                        <span data-product-preview-price-before @if(! $previewHasOffer || ! old('offer_original_price', $formProduct?->offer_original_price)) hidden @endif>
                            ${{ number_format((float) old('offer_original_price', $formProduct?->offer_original_price), 0, ',', '.') }}
                        </span>
                        <strong data-product-preview-price>${{ number_format($previewPrice, 0, ',', '.') }}</strong>
                    </div>
                    <p @class(['is-sold-out' => $previewIsSoldOut]) data-product-preview-stock>
                        {{ $previewIsSoldOut ? 'Agotado' : ($previewStock !== null && $previewStock !== '' ? $previewStock . ' disponibles' : 'Disponible') }}
                    </p>
                </div>
            </article>

            <div class="product-editor-preview-tips">
                <strong>Consejos rápidos</strong>
                <ul>
                    <li>Usa una foto clara y cuadrada.</li>
                    <li>El nombre debe ser corto y directo.</li>
                    <li>Revisa precio y stock antes de guardar.</li>
                </ul>
            </div>
        </aside>
    </div>

    <div class="product-editor-actions">
        <a href="/admin/products" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn">
            {{ $isEditing ? 'Actualizar' : 'Guardar' }}
        </button>
    </div>
</form>

<style>
    .product-editor-workspace {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(300px, 360px);
        gap: 22px;
        align-items: start;
        min-width: 0;
    }

    .product-editor-main {
        min-width: 0;
        display: grid;
        gap: 18px;
    }

    .product-editor-preview-panel {
        position: sticky;
        top: 22px;
        display: grid;
        gap: 14px;
        min-width: 0;
        padding: 18px;
        border: 1px solid #e5e7eb;
        border-radius: 22px;
        background: #ffffff;
        box-shadow: 0 18px 42px rgba(17, 24, 39, .06);
    }

    .product-editor-preview-panel__head {
        display: grid;
        gap: 4px;
    }

    .product-editor-preview-panel__head span {
        color: #ff6a00;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .product-editor-preview-panel__head strong {
        color: #111827;
        font-size: 18px;
    }

    .product-editor-preview-card {
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        background: #ffffff;
    }

    .product-editor-preview-media {
        position: relative;
        display: grid;
        place-items: center;
        aspect-ratio: 1 / .86;
        overflow: hidden;
        background: linear-gradient(135deg, #f8fafc, #eef2f7);
    }

    .product-editor-preview-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .product-editor-preview-media > span {
        width: 76px;
        height: 76px;
        display: grid;
        place-items: center;
        border-radius: 22px;
        background: #ffffff;
        color: #ff6a00;
        font-size: 34px;
        font-weight: 900;
        box-shadow: 0 16px 30px rgba(17, 24, 39, .08);
    }

    .product-editor-preview-badges {
        position: absolute;
        top: 12px;
        left: 12px;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        max-width: calc(100% - 24px);
    }

    .product-editor-preview-badges span {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 0 10px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .92);
        color: #ff6a00;
        font-size: 12px;
        font-weight: 900;
        box-shadow: 0 10px 22px rgba(17, 24, 39, .08);
    }

    .product-editor-preview-copy {
        display: grid;
        gap: 7px;
        padding: 16px;
    }

    .product-editor-preview-copy small {
        color: #6b7280;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .product-editor-preview-copy h3 {
        margin: 0;
        color: #111827;
        font-size: 18px;
        line-height: 1.18;
        overflow-wrap: anywhere;
    }

    .product-editor-preview-price {
        display: flex;
        align-items: baseline;
        gap: 8px;
        flex-wrap: wrap;
    }

    .product-editor-preview-price span {
        color: #9ca3af;
        font-size: 13px;
        font-weight: 800;
        text-decoration: line-through;
    }

    .product-editor-preview-price strong {
        color: #111827;
        font-size: 22px;
        line-height: 1;
    }

    .product-editor-preview-copy p {
        margin: 0;
        color: #16a34a;
        font-size: 13px;
        font-weight: 900;
    }

    .product-editor-preview-copy p.is-sold-out {
        color: #dc2626;
    }

    .product-editor-preview-tips {
        padding: 14px;
        border: 1px solid #fde7d4;
        border-radius: 16px;
        background: #fff7ed;
        color: #9a3412;
    }

    .product-editor-preview-tips strong {
        display: block;
        margin-bottom: 8px;
        color: #111827;
        font-size: 14px;
    }

    .product-editor-preview-tips ul {
        display: grid;
        gap: 6px;
        margin: 0;
        padding-left: 18px;
        font-size: 13px;
        line-height: 1.35;
    }

    @media (max-width: 1180px) {
        .product-editor-workspace {
            grid-template-columns: 1fr;
        }

        .product-editor-preview-panel {
            position: static;
            order: -1;
        }

        .product-editor-preview-card {
            display: grid;
            grid-template-columns: minmax(160px, 240px) minmax(0, 1fr);
        }
    }

    @media (max-width: 640px) {
        .product-editor-preview-panel {
            padding: 14px;
            border-radius: 18px;
        }

        .product-editor-preview-card {
            grid-template-columns: 1fr;
        }
    }
</style>
