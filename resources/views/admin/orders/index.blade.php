@extends('layouts.admin')

@section('content')
<div class="header">
    <div>
        <h2>Pedidos</h2>
        <p class="vendly-page-subtitle">Gestiona y da seguimiento a todos tus pedidos.</p>
    </div>
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="flash error">{{ $errors->first() }}</div>
@endif

@if (($totalOrders ?? $orders->count()) === 0)
    <div class="panel-empty">
        <h3>No hay pedidos todavía</h3>
        <p>Cuando un cliente envíe un carrito o reserva, aparecerá aquí para gestionarlo.</p>
    </div>
@endif

@if (($totalOrders ?? $orders->count()) > 0)
    <form method="GET" action="{{ url('/admin/orders') }}" class="list-card order-filter-panel">
        <label class="field-label" for="orderStatusFilter">Filtrar por estado</label>
        <select id="orderStatusFilter" name="status" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            @foreach($statusFilterOptions as $value => $label)
                <option value="{{ $value }}" @selected(($selectedStatus ?? null) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="hidden" name="payment_status" value="{{ $selectedPaymentStatus ?? '' }}">
        <details class="orders-payment-filter" @if($selectedPaymentStatus) open @endif>
            <summary>
                <span>Pago:</span>
                <strong>{{ $selectedPaymentStatus ? $paymentStatusOptions[$selectedPaymentStatus]['label'] : 'Todos' }}</strong>
                <span class="orders-payment-filter__chevron" aria-hidden="true">⌃</span>
            </summary>
            <div class="orders-payment-filter__menu">
                <a href="{{ request()->fullUrlWithQuery(['payment_status' => null, 'page' => null]) }}" class="orders-payment-option {{ ! $selectedPaymentStatus ? 'is-selected' : '' }}">
                    <span class="orders-payment-option__check" aria-hidden="true">✓</span>
                    <span><strong>Todos</strong><small>Mostrar todos los pedidos</small></span>
                </a>
                @foreach($paymentStatusOptions as $value => $option)
                    <a href="{{ request()->fullUrlWithQuery(['payment_status' => $value, 'page' => null]) }}" class="orders-payment-option {{ $selectedPaymentStatus === $value ? 'is-selected' : '' }}">
                        <span class="orders-payment-option__check" aria-hidden="true">✓</span>
                        <span><strong>{{ $option['label'] }}</strong><small>{{ $option['description'] }}</small></span>
                    </a>
                @endforeach
            </div>
        </details>
        <div class="order-filter-count">
            Mostrando {{ $orders->count() }} de {{ $totalOrders ?? $orders->count() }} pedidos
        </div>
    </form>

    @if ($orders->isEmpty())
        <div class="list-card">
            No hay pedidos con los filtros seleccionados.
        </div>
    @endif
@endif

@if($orders->isNotEmpty())
    <div class="vendly-table-card orders-table-card">
        <div class="vendly-table-scroll">
            <table class="vendly-data-table orders-data-table">
                <thead>
                    <tr>
                        <th># Pedido</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Método de pago</th>
                        <th>Entrega</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                        @php
                            $statusClass = match ($order->status) {
                                'pagado' => 'is-success',
                                'enviado' => 'is-info',
                                'devuelto' => 'is-danger',
                                default => 'is-warning',
                            };
                        @endphp
                        <tr>
                            <td><strong>#{{ $order->id }}</strong></td>
                            <td>
                                <strong>{{ $order->customer_name ?: 'Sin nombre' }}</strong>
                                <small>
                                    {{ $order->customer_phone ?: 'Sin teléfono' }}
                                    @if (auth()->user()->isAdmin())
                                        · {{ $order->store?->name ?? 'Sin tienda' }}
                                    @endif
                                </small>
                            </td>
                            <td><strong>{{ $order->formatMoney($order->total) }}</strong></td>
                            <td>{{ $order->paymentMethodLabel() }}</td>
                            <td>{{ $order->shipping_method ?: 'Sin envío' }}</td>
                            <td><span class="vendly-status {{ $statusClass }}">{{ $order->statusLabel() }}</span></td>
                            <td>{{ $order->created_at?->format('d M Y') }}</td>
                            <td>
                                <details class="vendly-order-actions">
                                    <summary>Gestionar</summary>
                                    <div>
                                        <form method="POST" action="{{ route('admin.orders.manage', $order) }}" data-confirm-delete data-confirm-message="¿Confirmar los cambios del pedido y del pago?" data-confirm-submit-label="Guardar cambios">
                                            @csrf
                                            @method('PATCH')
                                            @if($order->status === 'pagado')
                                                <span class="orders-managed-state">El estado del pedido se ajusta según el pago.</span>
                                                <input type="hidden" name="status" value="pagado">
                                            @else
                                                <label class="field-label" for="status-{{ $order->id }}">Estado</label>
                                                <select name="status" id="status-{{ $order->id }}">
                                                    @foreach($statusFilterOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                            <label class="field-label" for="payment-status-{{ $order->id }}">Estado del pago</label>
                                            <select name="payment_status" id="payment-status-{{ $order->id }}">
                                                @foreach($paymentManagementOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected(($value === 'failed' && in_array($order->payment_status, ['rejected', 'cancelled', 'expired'], true)) || ($value !== 'failed' && $order->payment_status === $value))>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <span class="orders-payment-note">El pedido se actualiza según el pago.</span>
                                            <button type="submit" class="btn">Guardar cambios</button>
                                        </form>

                                        @if($order->canRestoreStockManually())
                                            <form method="POST" action="{{ route('admin.orders.restore-stock', $order) }}" data-confirm-delete data-confirm-message="¿Restaurar el stock de este pedido? Usa esta acción solo si la compra por WhatsApp no se concretó.">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-secondary">Restaurar stock</button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" data-confirm-delete data-confirm-message="¿Eliminar este pedido? Esta acción no se puede deshacer.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">Eliminar pedido</button>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                        <tr class="vendly-order-detail-row">
                            <td colspan="8">
                                <div class="vendly-order-detail">
                                    <span><strong>Estado de pago:</strong> {{ $order->paymentStatusLabel() }}</span>
                                    <span><strong>Ciudad:</strong> {{ $order->customer_city ?: 'Sin ciudad' }}</span>
                                    <span><strong>Dirección:</strong> {{ $order->customer_address ?: 'Sin dirección' }}</span>
                                    <span><strong>Barrio:</strong> {{ $order->customer_neighborhood ?: 'Sin barrio' }}</span>
                                    <span><strong>Documento:</strong> {{ $order->customer_document ?: 'Sin documento' }}</span>
                                    <span>
                                        <strong>Envío:</strong>
                                        @if((float) ($order->shipping_cost ?? 0) > 0)
                                            {{ $order->formatMoney($order->shipping_cost) }}
                                        @elseif($order->shipping_method)
                                            Gratis
                                        @else
                                            -
                                        @endif
                                    </span>
                                    <span>
                                        <strong>{{ $order->store?->isReservationStore() ? 'Reserva' : 'Items' }}:</strong>
                                        @if ($order->store?->isReservationStore())
                                            {{ $order->reservation_date?->format('Y-m-d') ?: 'Sin fecha' }} {{ $order->reservation_time ?: '' }}
                                        @else
                                            {{ $order->items->sum('quantity') }} item(s)
                                        @endif
                                    </span>
                                    @if(\App\Models\Order::supportsDiscountColumns() && (float) ($order->discount_amount ?? 0) > 0)
                                        <span><strong>Cupón:</strong> {{ $order->discount_code ?: 'Descuento' }} (-{{ $order->formatMoney($order->discount_amount) }})</span>
                                    @endif
                                    @if ($order->items->isNotEmpty())
                                        <span class="vendly-order-products">
                                            <strong>Productos:</strong>
                                            @foreach ($order->items as $item)
                                                @php
                                                    $itemDetails = [
                                                        $item->displayName() . ' x' . $item->quantity,
                                                    ];

                                                    if ($item->size) {
                                                        $itemDetails[] = 'Talla: ' . $item->size;
                                                    }

                                                    if ($item->color) {
                                                        $itemDetails[] = 'Color: ' . $item->color;
                                                    }
                                                @endphp
                                                {{ implode(' · ', $itemDetails) }}{{ ! $loop->last ? ' / ' : '' }}
                                            @endforeach
                                        </span>
                                    @endif
                                    @if ($order->notes)
                                        <span><strong>Notas:</strong> {{ $order->notes }}</span>
                                    @endif
                                    @if (\App\Models\Order::supportsTermsAcceptanceColumns() && $order->terms_accepted_at)
                                        <span>
                                            <strong>Términos aceptados:</strong> {{ $order->terms_accepted_at->format('Y-m-d H:i') }}
                                            @if($order->terms_version)
                                                · Version {{ $order->terms_version }}
                                            @endif
                                        </span>
                                    @endif
                                    @if(\App\Models\Order::supportsStockRestorationColumns() && $order->stockRestored())
                                        <span><strong>Stock restaurado:</strong> {{ $order->stock_restored_at?->format('Y-m-d H:i') }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection
