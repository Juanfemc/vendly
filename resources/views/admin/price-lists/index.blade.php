@extends('layouts.admin')

@php
    $isAdmin = auth()->user()?->isAdmin();
    $priceListBaseUrl = isset($store) && $store
        ? app(\App\Services\StorefrontUrlService::class)->publicHome($store)
        : null;
    $editingPriceList = $editingPriceList ?? null;
@endphp

@section('content')
<div class="header">
    <h2>Listas de precios</h2>
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="flash error">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="flash error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

@if($isAdmin && empty($selectedStore))
    <div class="panel-list">
        @foreach(($stores ?? collect()) as $storeOption)
            <div class="list-card resource-card">
                <div class="resource-card__main">
                    <div class="resource-card__header">
                        <div>
                            <h3 class="resource-card__title">{{ $storeOption->name }}</h3>
                            <p class="resource-card__subtitle">Precios especiales para clientes o campañas.</p>
                        </div>
                        <div class="resource-badges">
                            <span class="resource-badge">Plan {{ $storeOption->planLabel() }}</span>
                            <span class="resource-badge">{{ $storeOption->price_lists_count ?? 0 }} lista(s)</span>
                        </div>
                    </div>
                </div>
                <div class="resource-actions">
                    <a href="{{ route('admin.stores.price-lists.index', $storeOption) }}" class="btn">
                        {{ $storeOption->allowsPriceLists() ? 'Gestionar' : 'Ver plan' }}
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    @if(($stores ?? null) && method_exists($stores, 'hasPages') && $stores->hasPages())
        <div class="list-card admin-pagination">
            {{ $stores->onEachSide(1)->links('pagination::bootstrap-4') }}
        </div>
    @endif
@else
    @if($isAdmin && ! empty($selectedStore))
        <div class="list-card resource-card">
            <div class="resource-card__main">
                <h3 class="resource-card__title">{{ $selectedStore->name }}</h3>
                <p class="resource-card__subtitle">Listas de precios de esta tienda.</p>
            </div>
            <div class="resource-actions">
                <a href="{{ route('admin.price-lists.index') }}" class="btn btn-secondary">Volver a tiendas</a>
            </div>
        </div>
    @endif

    @if(! empty($priceListsLocked))
        <div class="panel-empty">
            <h3>Listas de precios no disponibles</h3>
            <p>El plan {{ $store->planLabel() }} no incluye listas de precios. Disponible solo en el plan Premium.</p>
        </div>
    @elseif(! empty($priceListsUnavailable))
        <div class="panel-empty">
            <h3>Listas de precios pendientes de activar</h3>
            <p>Esta tienda ya tiene plan Premium. Falta ejecutar las migraciones para crear las tablas de listas de precios.</p>
            <p><code>php artisan migrate</code></p>
        </div>
    @else
        <div class="list-card">
            <h3 style="margin-top:0;">{{ $editingPriceList ? 'Editar lista' : 'Crear lista' }}</h3>
            <form method="POST" action="{{ $editingPriceList ? route('admin.price-lists.update', $editingPriceList) : route('admin.price-lists.store') }}">
                @csrf
                @if($editingPriceList)
                    @method('PUT')
                @endif
                @if($isAdmin)
                    <input type="hidden" name="store_id" value="{{ $store->id }}">
                @endif

                <div class="grid-two">
                    <label>
                        <span>Nombre de la lista</span>
                        <input type="text" name="name" value="{{ old('name', $editingPriceList?->name) }}" placeholder="Mayoristas, clientes VIP, feria..." required>
                    </label>

                    <label>
                        <span>Código privado</span>
                        <input type="text" name="access_code" value="{{ old('access_code', $editingPriceList?->access_code) }}" placeholder="Ej: MAYORISTAS">
                    </label>
                </div>

                <div class="grid-two">
                    <label>
                        <span>Disponible desde</span>
                        <input type="date" name="starts_at" value="{{ old('starts_at', $editingPriceList?->starts_at?->format('Y-m-d')) }}">
                    </label>

                    <label>
                        <span>Disponible hasta</span>
                        <input type="date" name="ends_at" value="{{ old('ends_at', $editingPriceList?->ends_at?->format('Y-m-d')) }}">
                    </label>
                </div>

                <label style="display:flex; gap:8px; align-items:center; margin:10px 0;">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editingPriceList?->is_active ?? true)) style="width:auto; margin:0;">
                    <span>Lista activa</span>
                </label>

                @if($editingPriceList)
                    <hr style="border:none; border-top:1px solid #e5e7eb; margin:20px 0;">
                    <h3>Precios por producto</h3>
                    <p class="muted">Deja vacío un producto para que conserve su precio normal.</p>

                    <div class="panel-list">
                        @foreach($products as $product)
                            <div class="list-card resource-card resource-card--compact">
                                <div class="resource-card__main">
                                    <h4 class="resource-card__title">{{ $product->name }}</h4>
                                    <p class="resource-card__subtitle">Precio normal: $ {{ number_format((float) $product->price, 0, ',', '.') }}</p>
                                </div>
                                <label style="min-width:220px;">
                                    <span>Precio en lista</span>
                                    <input type="text" name="prices[{{ $product->id }}]" value="{{ old('prices.' . $product->id, $productPrices[$product->id] ?? '') }}" placeholder="Ej: 49000">
                                </label>
                            </div>
                        @endforeach
                    </div>
                @endif

                <button class="btn" type="submit">{{ $editingPriceList ? 'Guardar lista' : 'Crear lista' }}</button>
                @if($editingPriceList)
                    <a href="{{ $isAdmin ? route('admin.stores.price-lists.index', $store) : route('admin.price-lists.index') }}" class="btn btn-secondary">Crear otra</a>
                @endif
            </form>
        </div>

        @if($priceLists->isEmpty())
            <div class="panel-empty">
                <h3>No hay listas de precios</h3>
                <p>Crea una lista para compartir precios mayoristas, VIP o de campaña sin cambiar el precio público.</p>
            </div>
        @else
            <div class="panel-list">
                @foreach($priceLists as $priceList)
                    @php
                        $shareKey = $priceList->access_code ?: $priceList->slug;
                        $shareUrl = $priceListBaseUrl ? $priceListBaseUrl . '?lista=' . urlencode($shareKey) : null;
                    @endphp
                    <article class="list-card resource-card">
                        <div class="resource-card__main">
                            <div class="resource-card__header">
                                <div>
                                    <h3 class="resource-card__title">{{ $priceList->name }}</h3>
                                    <p class="resource-card__subtitle">
                                        Código: {{ $shareKey ?: 'Sin código' }}
                                    </p>
                                </div>
                                <div class="resource-badges">
                                    <span class="resource-badge {{ $priceList->is_active ? 'status-active' : 'status-paused' }}">{{ $priceList->is_active ? 'Activa' : 'Inactiva' }}</span>
                                    <span class="resource-badge">{{ $priceList->product_prices_count }} precio(s)</span>
                                </div>
                            </div>

                            <div class="resource-metrics">
                                <div class="resource-metric">
                                    <span class="resource-metric__label">Vigencia</span>
                                    <span class="resource-metric__value">
                                        {{ $priceList->starts_at ? $priceList->starts_at->format('d/m/Y') : 'Hoy' }}
                                        -
                                        {{ $priceList->ends_at ? $priceList->ends_at->format('d/m/Y') : 'Sin vencimiento' }}
                                    </span>
                                </div>
                                @if($shareUrl)
                                    <div class="resource-metric">
                                        <span class="resource-metric__label">Enlace</span>
                                        <span class="resource-metric__value" style="word-break:break-all;">{{ $shareUrl }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="resource-actions">
                            <a href="{{ ($isAdmin ? route('admin.stores.price-lists.index', $store) : route('admin.price-lists.index')) . '?edit=' . $priceList->id }}" class="btn btn-secondary">Editar</a>
                            <form method="POST" action="{{ route('admin.price-lists.destroy', $priceList) }}" data-confirm-delete data-confirm-message="¿Eliminar esta lista de precios?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Eliminar</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    @endif
@endif
@endsection
