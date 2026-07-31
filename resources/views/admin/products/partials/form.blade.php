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
    $showInventory = ! ($formStore?->isReservationStore() ?? false);
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="product-editor-form">
    @csrf
    @if($isEditing)
        @method('PUT')
    @endif

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
                <input id="name" type="text" name="name" value="{{ old('name', $formProduct?->name) }}" placeholder="Ej: Camiseta básica de algodón" required>
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
                    <select name="category" id="category_select">
                        <option value="">Selecciona categoría</option>
                        @foreach ($categoryOptions as $categoryOption)
                            <option value="{{ $categoryOption }}" @selected($selectedCategory === $categoryOption)>{{ $categoryOption }}</option>
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
                <div id="features_editor" class="rich-content" contenteditable="true" data-rich-content>{!! $featuresEditorValue !!}</div>
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
                <input id="product_image" type="file" name="image" accept="image/*" data-optimize-image data-max-width="1600" data-max-height="1600" data-quality="0.82" data-output="webp" data-max-size="2097152">
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
                <input id="price" type="number" step="0.01" name="price" value="{{ old('price', $formProduct?->price) }}" placeholder="0" required>
            </div>

            @if($formStore?->allowsOfferBadges())
                <div class="product-editor-field" data-offer-pricing>
                    <label for="offer_original_price">Precio antes</label>
                    <input id="offer_original_price" type="number" step="0.01" name="offer_original_price" value="{{ old('offer_original_price', $formProduct?->offer_original_price) }}" placeholder="Sin descuento">
                </div>
            @endif

            @if($showInventory)
                <div class="product-editor-field">
                    <label for="stock_quantity">Stock disponible</label>
                    <input id="stock_quantity" type="number" name="stock_quantity" min="0" step="1" value="{{ old('stock_quantity', $formProduct?->stock_quantity) }}" placeholder="Ilimitado">
                </div>
            @endif
        </div>

        <div class="product-editor-options">
            @if($formStore?->allowsOfferBadges())
                <label class="product-editor-switch">
                    <span>
                        <strong>Mostrar etiqueta de oferta</strong>
                        <small>El precio actual queda como precio de oferta.</small>
                    </span>
                    <input type="checkbox" name="has_offer" value="1" @checked(old('has_offer', $formProduct?->has_offer)) data-offer-toggle>
                    <i></i>
                </label>
            @endif

            @if($showInventory)
                <label class="product-editor-switch">
                    <span>
                        <strong>Marcar como agotado</strong>
                        <small>Oculta la compra cuando no haya disponibilidad.</small>
                    </span>
                    <input type="checkbox" name="is_sold_out" value="1" @checked(old('is_sold_out', $formProduct?->is_sold_out))>
                    <i></i>
                </label>
            @endif
        </div>

        @if($formStore?->allowsCustomProductBadges())
            <div class="product-editor-field">
                <label for="custom_badges">Etiquetas personalizadas</label>
                <input id="custom_badges" type="text" name="custom_badges" value="{{ old('custom_badges', $formProduct ? implode(', ', $formProduct->customBadges()) : '') }}" maxlength="255" placeholder="Ej: Nuevo, Más vendido, Últimas unidades">
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

    <div class="product-editor-actions">
        <a href="/admin/products" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn">
            {{ $isEditing ? 'Actualizar' : 'Guardar' }}
        </button>
    </div>
</form>
