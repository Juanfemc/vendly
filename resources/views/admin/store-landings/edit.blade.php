@extends('layouts.admin')

@section('meta_title', 'Vendly - Landing de producto.')

@section('content')
@php
    $isAdmin = auth()->user()?->isAdmin();
    $updateRoute = $adminMode
        ? route('admin.stores.landing.update', $store)
        : route('admin.store-landing.update');
    $previewRoute = $adminMode
        ? route('admin.stores.landing.preview', $store)
        : route('admin.store-landing.preview');
    $activationRoute = $adminMode ? route('admin.stores.landing.activation', $store) : null;
    $selectedProductId = (int) old('product_id', $landing->product_id);
    $features = collect(old('feature_titles') !== null
        ? collect(old('feature_titles'))->map(fn ($title, $index) => [
            'title' => $title,
            'description' => old('feature_descriptions.' . $index),
        ])->all()
        : $landing->featuresList());
    $faqs = collect(old('faq_questions') !== null
        ? collect(old('faq_questions'))->map(fn ($question, $index) => [
            'question' => $question,
            'answer' => old('faq_answers.' . $index),
        ])->all()
        : $landing->faqList());
    $reviewImages = $landing->reviewImages();
@endphp

<style>
    .landing-editor {
        display: grid;
        gap: 22px;
    }

    .landing-editor__hero {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(260px, 360px);
        gap: 20px;
        align-items: stretch;
    }

    .landing-editor__intro,
    .landing-editor__status,
    .landing-editor__card {
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 18px 44px rgba(15, 23, 42, 0.06);
    }

    .landing-editor__intro {
        padding: 28px;
    }

    .landing-editor__eyebrow {
        display: inline-flex;
        margin-bottom: 10px;
        color: #ff6a00;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: 1.7px;
        text-transform: uppercase;
    }

    .landing-editor__intro h2 {
        margin: 0;
        color: #071225;
        font-size: clamp(32px, 4vw, 52px);
        line-height: 1;
        letter-spacing: 0;
    }

    .landing-editor__intro p,
    .landing-editor__status p,
    .landing-editor__card p {
        color: #64748b;
        line-height: 1.55;
    }

    .landing-editor__status {
        display: grid;
        gap: 14px;
        padding: 22px;
    }

    .landing-status-pill {
        display: inline-flex;
        width: fit-content;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-radius: 999px;
        background: #f1f5f9;
        color: #475569;
        font-weight: 900;
    }

    .landing-status-pill.is-active {
        background: #dcfce7;
        color: #047857;
    }

    .landing-status-pill.is-requested {
        background: #fff7ed;
        color: #c2410c;
    }

    .landing-editor__grid {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(300px, 0.8fr);
        gap: 22px;
    }

    .landing-editor__stack {
        display: grid;
        gap: 22px;
    }

    .landing-editor__card {
        padding: 24px;
    }

    .landing-editor__card h3 {
        margin: 0 0 14px;
        color: #071225;
        font-size: 22px;
    }

    .landing-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .landing-form-grid .is-full {
        grid-column: 1 / -1;
    }

    .landing-repeat-row {
        display: grid;
        grid-template-columns: minmax(0, 0.85fr) minmax(0, 1.15fr);
        gap: 12px;
        padding: 12px;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #f8fafc;
    }

    .landing-review-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 12px;
    }

    .landing-review-thumb {
        position: relative;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #f8fafc;
    }

    .landing-review-thumb img {
        width: 100%;
        aspect-ratio: 4 / 5;
        object-fit: cover;
        display: block;
    }

    .landing-review-thumb label {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px;
        color: #475569;
        font-size: 12px;
        font-weight: 800;
    }

    .landing-editor__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        justify-content: flex-end;
    }

    .landing-editor__hint {
        margin-top: 8px;
        color: #64748b;
        font-size: 13px;
    }

    @media (max-width: 980px) {
        .landing-editor__hero,
        .landing-editor__grid,
        .landing-form-grid {
            grid-template-columns: 1fr;
        }

        .landing-repeat-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="header">
    <div>
        <h2>Landing de producto</h2>
        <p style="margin:6px 0 0; color:#64748b;">Configura una pagina de venta para un solo producto.</p>
    </div>
    <a href="{{ $adminMode ? route('admin.stores.edit', $store) : '/dashboard' }}" class="btn btn-secondary">Volver</a>
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="flash error">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="flash error">{{ $errors->first() }}</div>
@endif

<div class="landing-editor">
    <section class="landing-editor__hero">
        <div class="landing-editor__intro">
            <span class="landing-editor__eyebrow">Landing de venta directa</span>
            <h2>{{ $store->name }}</h2>
            <p>El usuario puede configurar contenido, oferta, video y prueba social. La landing solo reemplaza la tienda publica cuando un admin la activa.</p>
        </div>

        <aside class="landing-editor__status">
            @if($landing->enabled_by_admin)
                <span class="landing-status-pill is-active">Activa publicamente</span>
                <p>La URL principal de la tienda esta mostrando esta landing.</p>
            @elseif($landing->activation_requested_at)
                <span class="landing-status-pill is-requested">Activacion solicitada</span>
                <p>Solicitud enviada el {{ $landing->activation_requested_at->format('d/m/Y H:i') }}.</p>
            @else
                <span class="landing-status-pill">Borrador</span>
                <p>Guarda la configuracion y solicita activacion cuando este lista.</p>
            @endif

            <div class="landing-editor__actions">
                @if($landing->product_id)
                    <a class="btn btn-secondary" href="{{ $previewRoute }}" target="_blank" rel="noopener">Vista previa</a>
                @endif

                @if($adminMode)
                    <form method="POST" action="{{ $activationRoute }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="enabled" value="{{ $landing->enabled_by_admin ? '0' : '1' }}">
                        <button class="btn {{ $landing->enabled_by_admin ? 'btn-secondary' : 'btn-primary' }}" type="submit">
                            {{ $landing->enabled_by_admin ? 'Desactivar landing' : 'Activar landing' }}
                        </button>
                    </form>
                @endif
            </div>
        </aside>
    </section>

    <form method="POST" action="{{ $updateRoute }}" enctype="multipart/form-data" class="landing-editor__grid">
        @csrf

        <div class="landing-editor__stack">
            <section class="landing-editor__card">
                <h3>Producto principal</h3>
                @if($products->isEmpty())
                    <p>Esta tienda aun no tiene productos. Crea primero el producto que vendera la landing.</p>
                    <a class="btn btn-primary" href="/admin/products/create">Crear producto</a>
                @else
                    <label class="field-wrap">
                        <span class="field-label">Producto que vendera la landing</span>
                        <select class="input" name="product_id" required>
                            <option value="">Selecciona un producto</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" @selected($selectedProductId === (int) $product->id)>
                                    {{ $product->name }} - ${{ number_format((float) $product->price, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <p class="landing-editor__hint">La compra, stock, variantes, carrito y checkout salen del producto real.</p>
                @endif
            </section>

            <section class="landing-editor__card">
                <h3>Contenido principal</h3>
                <div class="landing-form-grid">
                    <label class="field-wrap">
                        <span class="field-label">Texto superior</span>
                        <input class="input" type="text" name="eyebrow" value="{{ old('eyebrow', $landing->eyebrow) }}" placeholder="Rendimiento en cada movimiento">
                    </label>
                    <label class="field-wrap">
                        <span class="field-label">Titulo</span>
                        <input class="input" type="text" name="headline" value="{{ old('headline', $landing->headline) }}" placeholder="Mas que una camiseta">
                    </label>
                    <label class="field-wrap is-full">
                        <span class="field-label">Subtitulo</span>
                        <input class="input" type="text" name="subtitle" value="{{ old('subtitle', $landing->subtitle) }}" placeholder="Tu mejor version en movimiento">
                    </label>
                    <label class="field-wrap is-full">
                        <span class="field-label">Descripcion</span>
                        <textarea class="textarea" name="description" rows="5" placeholder="Describe el producto, su uso y el motivo para comprarlo.">{{ old('description', $landing->description) }}</textarea>
                    </label>
                </div>
            </section>

            <section class="landing-editor__card">
                <h3>Video</h3>
                <div class="landing-form-grid">
                    <label class="field-wrap is-full">
                        <span class="field-label">URL del video</span>
                        <input class="input" type="url" name="video_url" value="{{ old('video_url', $landing->video_url) }}" placeholder="https://...">
                    </label>
                    <label class="field-wrap">
                        <span class="field-label">Titulo del video</span>
                        <input class="input" type="text" name="video_title" value="{{ old('video_title', $landing->video_title) }}" placeholder="Ver video">
                    </label>
                    <label class="field-wrap">
                        <span class="field-label">Descripcion del video</span>
                        <input class="input" type="text" name="video_description" value="{{ old('video_description', $landing->video_description) }}" placeholder="Mira como se adapta a tu rutina.">
                    </label>
                </div>
            </section>

            <section class="landing-editor__card">
                <h3>Caracteristicas</h3>
                @for($i = 0; $i < 6; $i++)
                    @php $feature = $features[$i] ?? ['title' => '', 'description' => '']; @endphp
                    <div class="landing-repeat-row">
                        <label class="field-wrap">
                            <span class="field-label">Caracteristica {{ $i + 1 }}</span>
                            <input class="input" type="text" name="feature_titles[]" value="{{ $feature['title'] ?? '' }}" placeholder="Tela ligera">
                        </label>
                        <label class="field-wrap">
                            <span class="field-label">Detalle</span>
                            <input class="input" type="text" name="feature_descriptions[]" value="{{ $feature['description'] ?? '' }}" placeholder="Comoda y transpirable">
                        </label>
                    </div>
                @endfor
            </section>
        </div>

        <div class="landing-editor__stack">
            <section class="landing-editor__card">
                <h3>Oferta</h3>
                <label class="settings-toggle">
                    <input type="checkbox" name="bundle_enabled" value="1" @checked(old('bundle_enabled', $landing->bundle_enabled))>
                    <span>Mostrar pack x2</span>
                </label>
                <div class="landing-form-grid">
                    <label class="field-wrap">
                        <span class="field-label">Cantidad del pack</span>
                        <input class="input" type="number" name="bundle_quantity" min="2" max="10" value="{{ old('bundle_quantity', $landing->bundle_quantity ?: 2) }}">
                    </label>
                    <div class="field-wrap">
                        <span class="field-label">Precio del pack</span>
                        <p class="landing-editor__hint">Se calcula automaticamente con el precio real del producto o su precio mayorista configurado.</p>
                    </div>
                    <label class="field-wrap">
                        <span class="field-label">Etiqueta</span>
                        <input class="input" type="text" name="bundle_badge" value="{{ old('bundle_badge', $landing->bundle_badge) }}" placeholder="Mas popular">
                    </label>
                    <label class="field-wrap">
                        <span class="field-label">Texto de envio</span>
                        <input class="input" type="text" name="bundle_shipping_text" value="{{ old('bundle_shipping_text', $landing->bundle_shipping_text) }}" placeholder="Envio a toda Colombia">
                    </label>
                </div>
            </section>

            <section class="landing-editor__card">
                <h3>Reseñas de WhatsApp</h3>
                @if(! empty($reviewImages))
                    <div class="landing-review-grid">
                        @foreach($reviewImages as $image)
                            <div class="landing-review-thumb">
                                <img src="{{ asset('storage/' . $image) }}" alt="Captura de reseña">
                                <label>
                                    <input type="checkbox" name="remove_review_images[]" value="{{ $image }}">
                                    Quitar
                                </label>
                            </div>
                        @endforeach
                    </div>
                @endif
                <label class="field-wrap">
                    <span class="field-label">Subir capturas</span>
                    <input class="input" type="file" name="review_images[]" accept="image/png,image/jpeg,image/webp" multiple>
                </label>
                <p class="landing-editor__hint">Se muestran como prueba social visual. Maximo 12 imagenes.</p>
            </section>

            <section class="landing-editor__card">
                <h3>Preguntas frecuentes</h3>
                @for($i = 0; $i < 5; $i++)
                    @php $faq = $faqs[$i] ?? ['question' => '', 'answer' => '']; @endphp
                    <div class="landing-repeat-row">
                        <label class="field-wrap">
                            <span class="field-label">Pregunta {{ $i + 1 }}</span>
                            <input class="input" type="text" name="faq_questions[]" value="{{ $faq['question'] ?? '' }}" placeholder="Cuanto tarda el envio?">
                        </label>
                        <label class="field-wrap">
                            <span class="field-label">Respuesta</span>
                            <input class="input" type="text" name="faq_answers[]" value="{{ $faq['answer'] ?? '' }}" placeholder="Normalmente de 2 a 5 dias habiles.">
                        </label>
                    </div>
                @endfor
            </section>

            <section class="landing-editor__card">
                <h3>Guardar</h3>
                <div class="landing-editor__actions">
                    @if(! $adminMode)
                        <button class="btn btn-secondary" type="submit" name="request_activation" value="1">Solicitar activacion</button>
                    @endif
                    <button class="btn btn-primary" type="submit">Guardar cambios</button>
                </div>
                @if(! $adminMode)
                    <p class="landing-editor__hint">Solo Vendly puede activar la landing publica despues de revisar la configuracion.</p>
                @endif
            </section>
        </div>
    </form>
</div>
@endsection
