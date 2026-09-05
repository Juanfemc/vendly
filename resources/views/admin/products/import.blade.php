@extends('layouts.admin')

@section('content')
@php
    $preview = $preview ?? null;
    $summary = $preview['summary'] ?? null;
    $rows = collect($preview['rows'] ?? []);
    $zipFiles = collect($preview['zip_files'] ?? []);
    $ocrBatches = $ocrBatches ?? collect();
    $isOcrPreview = str_starts_with((string) ($preview['source'] ?? ''), 'PDF con OCR + IA:');
    $hasErrors = $summary && (int) ($summary['errors'] ?? 0) > 0;
    $productFileMaxKb = $productFileMaxKb ?? 5120;
    $imagesZipMaxKb = $imagesZipMaxKb ?? 51200;
    $ocrPdfMaxKb = $ocrPdfMaxKb ?? 25600;
    $productFileMaxBytes = $productFileMaxKb * 1024;
    $imagesZipMaxBytes = $imagesZipMaxKb * 1024;
    $ocrPdfMaxBytes = $ocrPdfMaxKb * 1024;
    $totalUploadMaxBytes = $productFileMaxBytes + $imagesZipMaxBytes;
@endphp

<style>
    .product-import-page {
        display: grid;
        gap: 22px;
        min-width: 0;
        max-width: 100%;
        overflow-x: hidden;
    }

    .product-import-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
        padding-bottom: 18px;
        border-bottom: 1px solid #dde5ea;
    }

    .product-import-title {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .product-import-title h1 {
        margin: 0;
        color: #071827;
        font-size: clamp(28px, 4vw, 40px);
        line-height: 1.05;
    }

    .product-import-title p {
        margin: 6px 0 0;
        color: #0f766e;
    }

    .product-import-back {
        width: 44px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        border: 1px solid #dbe5ea;
        border-radius: 999px;
        background: #fff;
        color: var(--vendly-brand);
        text-decoration: none;
    }

    .product-import-card {
        display: grid;
        gap: 18px;
        padding: clamp(18px, 3vw, 28px);
        border: 1px solid #dfe8ee;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 14px 34px rgba(15, 23, 42, .06);
    }

    .product-import-card h2,
    .product-import-card h3 {
        margin: 0;
        color: #071827;
    }

    .product-import-help {
        margin: 0;
        color: #526b75;
        line-height: 1.55;
    }

    .product-import-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(240px, .42fr);
        gap: 18px;
        align-items: start;
    }

    .product-import-field {
        display: grid;
        gap: 8px;
    }

    .product-import-field label {
        color: #071827;
        font-weight: 800;
    }

    .product-import-field input,
    .product-import-field select {
        width: 100%;
        min-height: 48px;
        padding: 0 14px;
        border: 1px solid #d8e2e8;
        border-radius: 13px;
        background: #fff;
        color: #071827;
        font: inherit;
    }

    .product-import-field input:focus,
    .product-import-field select:focus {
        outline: none;
        border-color: var(--vendly-brand);
        box-shadow: 0 0 0 4px var(--vendly-brand-focus);
    }

    .product-import-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .product-import-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .product-import-stat {
        padding: 14px;
        border: 1px solid #dce8ee;
        border-radius: 14px;
        background: #f8fbfc;
    }

    .product-import-stat strong {
        display: block;
        color: #071827;
        font-size: 24px;
    }

    .product-import-stat span {
        color: #5b737c;
        font-size: 13px;
    }

    .product-import-table-wrap {
        max-width: 100%;
        overflow-x: auto;
        border: 1px solid #dfe8ee;
        border-radius: 16px;
        background: #fff;
    }

    .product-import-table {
        width: 100%;
        min-width: 920px;
        border-collapse: collapse;
    }

    .product-import-table th,
    .product-import-table td {
        padding: 13px 14px;
        border-bottom: 1px solid #e8eef2;
        text-align: left;
        vertical-align: top;
    }

    .product-import-table th {
        color: #5b737c;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .06em;
        background: #f8fbfc;
    }

    .product-import-table tr:last-child td {
        border-bottom: 0;
    }

    .product-import-status {
        display: inline-flex;
        align-items: center;
        min-height: 24px;
        padding: 0 9px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 900;
    }

    .product-import-status.is-valid {
        background: #dcfce7;
        color: #166534;
    }

    .product-import-status.is-error {
        background: #fee2e2;
        color: #991b1b;
    }

    .product-import-errors {
        margin: 0;
        padding-left: 18px;
        color: #b42318;
    }

    .product-import-note {
        padding: 14px 16px;
        border: 1px solid #bfdbfe;
        border-radius: 14px;
        background: #eff6ff;
        color: #1e3a8a;
    }

    .product-import-client-error {
        display: none;
        padding: 12px 14px;
        border: 1px solid #fecaca;
        border-radius: 13px;
        background: #fef2f2;
        color: #b42318;
        font-weight: 700;
        line-height: 1.45;
    }

    .product-import-ocr-card {
        border-color: #d7f0e8;
        background: linear-gradient(135deg, #ffffff 0%, #f4fffb 100%);
    }

    .product-import-ocr-badge {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        min-height: 26px;
        padding: 0 10px;
        border-radius: 999px;
        background: #dcfce7;
        color: #166534;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .product-import-batches {
        display: grid;
        gap: 10px;
    }

    .product-import-batch {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 12px;
        align-items: center;
        padding: 14px;
        border: 1px solid #dfe8ee;
        border-radius: 14px;
        background: #fff;
    }

    .product-import-batch strong {
        display: block;
        color: #071827;
    }

    .product-import-batch p {
        margin: 4px 0 0;
        color: #526b75;
        font-size: 13px;
        line-height: 1.45;
    }

    .product-import-batch-status {
        display: inline-flex;
        width: fit-content;
        min-height: 24px;
        align-items: center;
        padding: 0 9px;
        border-radius: 999px;
        background: #e0f2fe;
        color: #075985;
        font-size: 12px;
        font-weight: 900;
    }

    .product-import-batch-status.is-completed {
        background: #dcfce7;
        color: #166534;
    }

    .product-import-batch-status.is-failed {
        background: #fee2e2;
        color: #991b1b;
    }

    .product-import-batch-status.is-imported {
        background: #f1f5f9;
        color: #475569;
    }

    .product-import-image-tools {
        display: grid;
        gap: 14px;
    }

    .product-import-image-row {
        display: grid;
        grid-template-columns: minmax(160px, .8fr) minmax(180px, 1fr) minmax(180px, 1fr);
        gap: 12px;
        align-items: end;
        padding: 14px;
        border: 1px solid #e3ebef;
        border-radius: 14px;
        background: #fbfdfe;
    }

    .product-import-image-row strong {
        display: block;
        color: #071827;
    }

    .product-import-image-row span {
        color: #5b737c;
        font-size: 12px;
    }

    .product-import-image-row input,
    .product-import-image-row select {
        width: 100%;
        min-height: 42px;
        border: 1px solid #d8e2e8;
        border-radius: 12px;
        background: #fff;
        color: #071827;
        font: inherit;
    }

    .product-import-image-row select {
        padding: 0 12px;
    }

    .product-import-client-error.is-visible {
        display: block;
    }

    @media (max-width: 780px) {
        .product-import-grid,
        .product-import-summary {
            grid-template-columns: 1fr;
        }

        .product-import-card {
            border-radius: 16px;
        }

        .product-import-actions .btn,
        .product-import-actions button,
        .product-import-actions a {
            width: 100%;
            justify-content: center;
        }

        .product-import-image-row {
            grid-template-columns: 1fr;
        }

        .product-import-batch {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="product-import-page">
    <div class="product-import-head">
        <div class="product-import-title">
            <a href="{{ route('admin.products.index') }}" class="product-import-back" aria-label="Volver a productos">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"></path></svg>
            </a>
            <div>
                <h1>Importar productos</h1>
                <p>Sube un archivo CSV o Excel, revisa errores y confirma antes de crear.</p>
            </div>
        </div>
        <a href="{{ route('admin.products.import.template') }}" class="btn btn-secondary">Descargar plantilla</a>
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

    <section class="product-import-card">
        <div>
            <h2>Archivo de productos</h2>
                <p class="product-import-help">Columnas obligatorias: <strong>nombre</strong> y <strong>precio</strong>. Puedes agregar categoría, descripción, características, material, stock, precio_antes, etiquetas, agotado, imagen_url o imagen.</p>
        </div>

        <form
            method="POST"
            action="{{ route('admin.products.import.preview') }}"
            enctype="multipart/form-data"
            class="product-import-grid"
            data-product-import-form
            data-file-max="{{ $productFileMaxBytes }}"
            data-zip-max="{{ $imagesZipMaxBytes }}"
            data-total-max="{{ $totalUploadMaxBytes }}"
        >
            @csrf
            <div class="product-import-field">
                <label for="file">Archivo CSV o XLSX</label>
                <input id="file" type="file" name="file" accept=".csv,.txt,.xlsx" required>
                <p class="product-import-help">Máximo {{ number_format($productFileMaxKb / 1024, 0, ',', '.') }} MB. El Excel no debe traer imágenes incrustadas; usa imagen_url o un ZIP.</p>
            </div>

            <div class="product-import-field">
                <label for="images_zip">ZIP de imágenes opcional</label>
                <input id="images_zip" type="file" name="images_zip" accept=".zip">
                <p class="product-import-help">Úsalo si la columna <strong>imagen</strong> tiene nombres como camiseta.jpg. Máximo 50 MB.</p>
            </div>

            @if(auth()->user()?->isAdmin())
                <div class="product-import-field">
                    <label for="store_id">Tienda</label>
                    <select id="store_id" name="store_id" required>
                        <option value="">Selecciona tienda</option>
                        @foreach($stores as $storeOption)
                            <option value="{{ $storeOption->id }}" @selected(old('store_id', $preview['store_id'] ?? null) == $storeOption->id)>{{ $storeOption->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="product-import-actions">
                <button type="submit" class="btn">Revisar archivo</button>
                <a href="{{ route('admin.products.import.template') }}" class="btn btn-secondary">Usar plantilla</a>
            </div>
            <div class="product-import-client-error" data-product-import-error aria-live="polite"></div>
        </form>
    </section>

    @if(auth()->user()?->isAdmin())
        <section class="product-import-card product-import-ocr-card">
            <div>
                <span class="product-import-ocr-badge">Admin interno</span>
                <h2>PDF escaneado con OCR + IA</h2>
                <p class="product-import-help">Sube un catálogo PDF desordenado o escaneado. La IA extrae productos, precios y datos visibles; después debes revisar la vista previa antes de importar.</p>
            </div>

            <form
                method="POST"
                action="{{ route('admin.products.import.ocr-preview') }}"
                enctype="multipart/form-data"
                class="product-import-grid"
                data-product-import-form
                data-file-max="{{ $ocrPdfMaxBytes }}"
                data-zip-max="{{ $imagesZipMaxBytes }}"
                data-total-max="{{ $ocrPdfMaxBytes + $imagesZipMaxBytes }}"
            >
                @csrf

                <div class="product-import-field">
                    <label for="ocr_pdf">PDF del catálogo</label>
                    <input id="ocr_pdf" type="file" name="pdf" accept=".pdf,application/pdf" required>
                    <p class="product-import-help">Máximo {{ number_format($ocrPdfMaxKb / 1024, 0, ',', '.') }} MB. Funciona mejor con catálogos claros, una página por sección y precios visibles.</p>
                </div>

                <div class="product-import-field">
                    <label for="ocr_images_zip">ZIP de imágenes opcional</label>
                    <input id="ocr_images_zip" type="file" name="images_zip" accept=".zip">
                    <p class="product-import-help">Sube fotos en JPG, PNG o WebP para asignarlas en la vista previa antes de importar.</p>
                </div>

                <div class="product-import-field">
                    <label for="ocr_store_id">Tienda</label>
                    <select id="ocr_store_id" name="store_id" required>
                        <option value="">Selecciona tienda</option>
                        @foreach($stores as $storeOption)
                            <option value="{{ $storeOption->id }}" @selected(old('store_id', $preview['store_id'] ?? null) == $storeOption->id)>{{ $storeOption->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="product-import-actions">
                    <button type="submit" class="btn">Procesar en segundo plano</button>
                </div>
                <div class="product-import-client-error" data-product-import-error aria-live="polite"></div>
            </form>

            @if($ocrBatches->isNotEmpty())
                <div class="product-import-batches">
                    <h3>Procesos recientes</h3>
                    @foreach($ocrBatches as $batch)
                        @php
                            $statusLabel = match ($batch->status) {
                                'completed' => 'Listo',
                                'failed' => 'Falló',
                                'imported' => 'Importado',
                                'processing' => 'Procesando',
                                default => 'Pendiente',
                            };
                        @endphp
                        <div class="product-import-batch">
                            <div>
                                <span class="product-import-batch-status is-{{ $batch->status }}">{{ $statusLabel }}</span>
                                <strong>{{ $batch->source_name ?: 'Catálogo PDF' }}</strong>
                                <p>{{ $batch->store?->name ?? 'Tienda' }} · {{ $batch->created_at?->diffForHumans() }}</p>
                                @if($batch->status === 'failed' && $batch->error)
                                    <p>{{ $batch->error }}</p>
                                @elseif(in_array($batch->status, ['pending', 'processing'], true))
                                    <p>El OCR puede tardar varios minutos. Actualiza esta página para ver si ya terminó.</p>
                                @endif
                            </div>

                            @if($batch->status === 'completed')
                                <form method="POST" action="{{ route('admin.products.import.batches.load', $batch) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary">Cargar vista previa</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    @if($preview)
        <section class="product-import-card">
            <div>
                <h2>Vista previa</h2>
                <p class="product-import-help">Tienda: <strong>{{ $preview['store_name'] ?? 'Tienda' }}</strong>. Nada se guarda hasta que confirmes la importación.</p>
            </div>

            @if(! empty($preview['source']))
                <div class="product-import-note">
                    Origen: {{ $preview['source'] }}. Revisa nombres, precios y categorías antes de confirmar.
                </div>
            @endif

            <div class="product-import-summary">
                <div class="product-import-stat">
                    <strong>{{ $summary['total'] ?? 0 }}</strong>
                    <span>filas revisadas</span>
                </div>
                <div class="product-import-stat">
                    <strong>{{ $summary['valid'] ?? 0 }}</strong>
                    <span>listas para importar</span>
                </div>
                <div class="product-import-stat">
                    <strong>{{ $summary['errors'] ?? 0 }}</strong>
                    <span>con errores</span>
                </div>
            </div>

            @if(($summary['available_slots'] ?? null) !== null)
                <div class="product-import-note">
                    Tu plan permite importar {{ $summary['available_slots'] }} producto(s) más en este momento.
                </div>
            @endif

            @if(($preview['zip_image_count'] ?? 0) > 0)
                <div class="product-import-note">
                    ZIP revisado: encontramos {{ $preview['zip_image_count'] }} imagen(es) compatibles.
                </div>
            @endif

            @if($isOcrPreview && auth()->user()?->isAdmin())
                <form method="POST" action="{{ route('admin.products.import.images') }}" enctype="multipart/form-data" class="product-import-card product-import-image-tools">
                    @csrf
                    <div>
                        <h3>Asignar imágenes antes de importar</h3>
                        <p class="product-import-help">Puedes elegir una imagen del ZIP o subir una manual para cada producto. Si subes una manual, tendrá prioridad sobre el ZIP.</p>
                    </div>

                    @foreach($rows as $rowIndex => $row)
                        @php
                            $imageSource = $row['data']['image_source'] ?? ['type' => 'none', 'value' => ''];
                            $selectedZipImage = ($imageSource['type'] ?? null) === 'zip' ? (string) ($imageSource['value'] ?? '') : '';
                            $currentImageLabel = match ($imageSource['type'] ?? 'none') {
                                'zip' => 'ZIP: ' . ($imageSource['value'] ?? ''),
                                'temp' => 'Imagen manual asignada',
                                'url' => 'URL asignada',
                                default => 'Sin imagen',
                            };
                        @endphp
                        <div class="product-import-image-row">
                            <div>
                                <strong>{{ $row['data']['name'] ?: 'Producto sin nombre' }}</strong>
                                <span>{{ $currentImageLabel }}</span>
                            </div>

                            <div class="product-import-field">
                                <label for="image_from_zip_{{ $rowIndex }}">Desde ZIP</label>
                                <select id="image_from_zip_{{ $rowIndex }}" name="image_from_zip[{{ $rowIndex }}]" @disabled($zipFiles->isEmpty())>
                                    <option value="">Sin cambio</option>
                                    @foreach($zipFiles as $zipFile)
                                        <option value="{{ $zipFile }}" @selected($selectedZipImage === $zipFile)>{{ $zipFile }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="product-import-field">
                                <label for="manual_image_{{ $rowIndex }}">Subir manual</label>
                                <input id="manual_image_{{ $rowIndex }}" type="file" name="manual_images[{{ $rowIndex }}]" accept="image/jpeg,image/png,image/webp">
                            </div>
                        </div>
                    @endforeach

                    <div class="product-import-actions">
                        <button type="submit" class="btn">Guardar imágenes asignadas</button>
                    </div>
                </form>
            @endif

            <div class="product-import-table-wrap">
                <table class="product-import-table">
                    <thead>
                        <tr>
                            <th>Fila</th>
                            <th>Estado</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Categoría</th>
                            <th>Stock</th>
                            <th>Imagen</th>
                            <th>Etiquetas</th>
                            <th>Errores</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td>{{ $row['line'] }}</td>
                                <td>
                                    <span class="product-import-status {{ $row['valid'] ? 'is-valid' : 'is-error' }}">
                                        {{ $row['valid'] ? 'Válido' : 'Revisar' }}
                                    </span>
                                </td>
                                <td>{{ $row['data']['name'] ?: 'Sin nombre' }}</td>
                                <td>
                                    @if($row['data']['price'] !== null)
                                        ${{ number_format((float) $row['data']['price'], 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $row['data']['category'] ?: 'Sin categoría' }}</td>
                                <td>{{ $row['data']['stock_quantity'] ?? 'Ilimitado' }}</td>
                                <td>
                                    @if(($row['data']['image_source']['type'] ?? 'none') === 'url')
                                        URL
                                    @elseif(($row['data']['image_source']['type'] ?? 'none') === 'zip')
                                        {{ $row['data']['image_source']['value'] }}
                                    @elseif(($row['data']['image_source']['type'] ?? 'none') === 'temp')
                                        Manual
                                    @else
                                        Sin imagen
                                    @endif
                                </td>
                                <td>{{ implode(', ', $row['data']['custom_badges'] ?? []) ?: '-' }}</td>
                                <td>
                                    @if(! empty($row['errors']))
                                        <ul class="product-import-errors">
                                            @foreach($row['errors'] as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="product-import-actions">
                <form method="POST" action="{{ route('admin.products.import.store') }}">
                    @csrf
                    <button type="submit" class="btn" @disabled($hasErrors)>Confirmar importación</button>
                </form>

                <form method="POST" action="{{ route('admin.products.import.destroy') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-secondary">Descartar vista previa</button>
                </form>
            </div>
        </section>
    @endif
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const forms = document.querySelectorAll('[data-product-import-form]');

        if (! forms.length) {
            return;
        }

        const formatMb = (bytes) => `${Math.round((bytes / 1024 / 1024) * 10) / 10} MB`;

        forms.forEach((form) => {
            const fileInput = form.querySelector('input[name="file"], input[name="pdf"]');
            const zipInput = form.querySelector('input[name="images_zip"]');
            const errorBox = form.querySelector('[data-product-import-error]');
            const fileMax = Number(form.dataset.fileMax || 0);
            const zipMax = Number(form.dataset.zipMax || 0);
            const totalMax = Number(form.dataset.totalMax || 0);

            const setError = (message = '') => {
                if (! errorBox) {
                    return;
                }

                errorBox.textContent = message;
                errorBox.classList.toggle('is-visible', message !== '');
            };

            const selectedFile = (input) => input?.files?.[0] || null;

            const validateFiles = () => {
                const productFile = selectedFile(fileInput);
                const imagesZip = selectedFile(zipInput);
                const totalSize = (productFile?.size || 0) + (imagesZip?.size || 0);

                if (productFile && fileMax > 0 && productFile.size > fileMax) {
                    return `El archivo pesa ${formatMb(productFile.size)}. Sube un archivo de máximo ${formatMb(fileMax)}.`;
                }

                if (imagesZip && zipMax > 0 && imagesZip.size > zipMax) {
                    return `El ZIP pesa ${formatMb(imagesZip.size)}. Sube un ZIP de máximo ${formatMb(zipMax)}.`;
                }

                if (totalMax > 0 && totalSize > totalMax) {
                    return `Los archivos pesan ${formatMb(totalSize)} en total. Sube máximo ${formatMb(totalMax)} por importación.`;
                }

                return '';
            };

            [fileInput, zipInput].forEach((input) => {
                input?.addEventListener('change', () => setError(validateFiles()));
            });

            form.addEventListener('submit', (event) => {
                const error = validateFiles();

                if (error !== '') {
                    event.preventDefault();
                    setError(error);
                    errorBox?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        });
    })();
</script>
@endpush
