@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Cupones de descuento</h2>
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

@if(auth()->user()->isAdmin() && empty($selectedStore))
    <div class="panel-list">
        @foreach(($stores ?? collect()) as $storeOption)
            <div class="list-card resource-card">
                <div class="resource-card__main">
                    <div class="resource-card__header">
                        <div>
                            <h3 class="resource-card__title">{{ $storeOption->name }}</h3>
                            <p class="resource-card__subtitle">Gestiona los cupones de esta tienda</p>
                        </div>
                        <div class="resource-badges">
                            <span class="resource-badge">Plan {{ $storeOption->planLabel() }}</span>
                            <span class="resource-badge">{{ $storeOption->discount_coupons_count ?? 0 }} cupon(es)</span>
                        </div>
                    </div>
                </div>
                <div class="resource-actions">
                    <a href="{{ route('admin.stores.coupons.index', $storeOption) }}" class="btn">
                        {{ $storeOption->allowsDiscountCoupons() ? 'Ver cupones' : 'Ver plan' }}
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
    @if(auth()->user()->isAdmin() && ! empty($selectedStore))
        <div class="list-card resource-card">
            <div class="resource-card__main">
                <h3 class="resource-card__title">{{ $selectedStore->name }}</h3>
                <p class="resource-card__subtitle">Cupones de esta tienda</p>
            </div>
            <div class="resource-actions">
                <a href="{{ route('admin.coupons.index') }}" class="btn btn-secondary">Volver a tiendas</a>
            </div>
        </div>
    @endif

    @if(! empty($couponsLocked))
        <div class="panel-empty">
            <h3>Cupones no disponibles</h3>
            <p>El plan {{ $store->planLabel() }} no incluye códigos de descuento. Disponible solo en el plan Premium.</p>
        </div>
    @else
        <div class="list-card">
            <h3 style="margin-top:0;">Crear cupon</h3>
            <form method="POST" action="{{ route('admin.coupons.store') }}">
                @csrf
                @if(auth()->user()->isAdmin())
                    <input type="hidden" name="store_id" value="{{ $store->id }}">
                @endif

                <div class="grid-two">
                    <label>
                        <span>Código</span>
                        <input type="text" name="code" value="{{ old('code') }}" placeholder="EJ: BIENVENIDA10" required>
                    </label>

                    <label>
                        <span>Tipo de descuento</span>
                        <select name="type" required>
                            @foreach(\App\Models\DiscountCoupon::typeOptions() as $type => $label)
                                <option value="{{ $type }}" @selected(old('type') === $type)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="grid-two">
                    <label>
                        <span>Valor</span>
                        <input type="text" name="value" value="{{ old('value') }}" placeholder="10 o 15000" required>
                    </label>

                    <label>
                        <span>Compra minima</span>
                        <input type="text" name="min_subtotal" value="{{ old('min_subtotal', 0) }}" placeholder="0">
                    </label>
                </div>

                <div class="grid-two">
                    <label>
                        <span>Descuento maximo (opcional)</span>
                        <input type="text" name="max_discount_amount" value="{{ old('max_discount_amount') }}" placeholder="Solo para porcentajes">
                    </label>

                    <label>
                        <span>Limite de usos (opcional)</span>
                        <input type="number" name="usage_limit" value="{{ old('usage_limit') }}" min="1" placeholder="Ej: 50">
                    </label>
                </div>

                <div class="grid-two">
                    <label>
                        <span>Disponible desde (opcional)</span>
                        <input type="date" name="starts_at" value="{{ old('starts_at') }}">
                    </label>

                    <label>
                        <span>Vence el (opcional)</span>
                        <input type="date" name="expires_at" value="{{ old('expires_at') }}">
                    </label>
                </div>

                <label style="display:flex; gap:8px; align-items:center; margin:10px 0;">
                    <input type="checkbox" name="is_active" value="1" checked style="width:auto; margin:0;">
                    <span>Cupon activo</span>
                </label>

                <button class="btn" type="submit">Crear cupon</button>
            </form>
        </div>

        @if ($coupons->isEmpty())
            <div class="panel-empty">
                <h3>No hay cupones</h3>
                <p>Crea códigos para campañas, clientes frecuentes o descuentos de bienvenida.</p>
            </div>
        @endif

        <div class="panel-list">
            @foreach ($coupons as $coupon)
                <article class="list-card resource-card">
                    <div class="resource-card__main">
                        <div class="resource-card__header">
                            <div>
                                <h3 class="resource-card__title">{{ $coupon->code }}</h3>
                                <p class="resource-card__subtitle">{{ $coupon->typeLabel() }}: {{ $coupon->formattedValue() }}</p>
                            </div>
                            <div class="resource-badges">
                                <span class="resource-badge {{ $coupon->statusBadgeClass() }}">{{ $coupon->statusLabel() }}</span>
                                <span class="resource-badge">{{ $coupon->used_count }}{{ $coupon->usage_limit ? ' / ' . $coupon->usage_limit : '' }} usos</span>
                            </div>
                        </div>

                        <div class="resource-metrics">
                            <div class="resource-metric">
                                <span class="resource-metric__label">Compra minima</span>
                                <span class="resource-metric__value">$ {{ number_format((float) $coupon->min_subtotal, 0, ',', '.') }}</span>
                            </div>
                            <div class="resource-metric">
                                <span class="resource-metric__label">Vigencia</span>
                                <span class="resource-metric__value">
                                    {{ $coupon->starts_at ? $coupon->starts_at->format('d/m/Y') : 'Hoy' }}
                                    -
                                    {{ $coupon->expires_at ? $coupon->expires_at->format('d/m/Y') : 'Sin vencimiento' }}
                                </span>
                            </div>
                            @if($coupon->max_discount_amount)
                                <div class="resource-metric">
                                    <span class="resource-metric__label">Maximo</span>
                                    <span class="resource-metric__value">$ {{ number_format((float) $coupon->max_discount_amount, 0, ',', '.') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="resource-actions">
                        <form method="POST" action="{{ route('admin.coupons.toggle', $coupon) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-secondary">{{ $coupon->is_active ? 'Desactivar' : 'Activar' }}</button>
                        </form>

                        <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" data-confirm-delete data-confirm-message="Eliminar este cupon?">
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
@endsection
