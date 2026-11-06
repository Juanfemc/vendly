@extends('layouts.admin')

@section('meta_title', 'Métricas | Vendly')

@section('content')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ filemtime(public_path('css/dashboard.css')) }}">

<div class="header metrics-page-header">
    <div>
        <h2>Métricas</h2>
        <p class="vendly-page-subtitle">Consulta el rendimiento de {{ $store?->name ?: 'tu tienda' }} por mes.</p>
    </div>
    <form method="GET" action="{{ route('admin.metrics') }}" class="metrics-month-picker">
        @if($showVisits)
            <input type="hidden" name="view" value="visits">
        @endif
        <span aria-hidden="true">▣</span>
        <select name="month" aria-label="Seleccionar mes" onchange="this.form.submit()">
            @foreach($monthOptions as $monthOption)
                <option value="{{ $monthOption['value'] }}" @selected($monthOption['value'] === $selectedMonth->format('Y-m'))>
                    {{ $monthOption['label'] }}
                </option>
            @endforeach
        </select>
        <span aria-hidden="true">⌄</span>
    </form>
</div>

<section class="metrics-page" aria-label="Métricas de la tienda">
    <nav class="metrics-tabs" aria-label="Tipo de métrica">
        <a href="{{ route('admin.metrics', ['month' => $selectedMonth->format('Y-m')]) }}" class="{{ !$showVisits ? 'is-active' : '' }}">Ventas</a>
        @if($isPremium)
            <a href="{{ route('admin.metrics', ['month' => $selectedMonth->format('Y-m'), 'view' => 'visits']) }}" class="{{ $showVisits ? 'is-active' : '' }}">Visitas</a>
        @else
            <span class="metrics-tab-locked">Visitas <small>Premium</small></span>
        @endif
    </nav>

    @if(!$showVisits)
    <div class="metrics-card-grid" id="ventas">
        <article class="metrics-data-card">
            <div class="metrics-data-card-head">
                <span class="metrics-data-icon">$</span>
                <h3>Facturación Total</h3>
                <span class="metrics-help" title="Ventas registradas en el mes seleccionado">?</span>
            </div>
            <div class="metrics-data-main">
                <strong>$ {{ number_format($totalSales, 0, ',', '.') }}</strong>
                <div class="metrics-breakdown">
                    <span><i class="is-paid"></i>Pagado <b>$ {{ number_format($paidSales, 0, ',', '.') }}</b></span>
                    <span><i class="is-pending"></i>Por cobrar <b>$ {{ number_format($pendingSales, 0, ',', '.') }}</b></span>
                </div>
            </div>
            <p>{{ $totalSales > 0 ? 'Ventas registradas en este periodo' : 'Sin datos en este periodo' }}</p>
        </article>

        <article class="metrics-data-card">
            <div class="metrics-data-card-head">
                <span class="metrics-data-icon">🛒</span>
                <h3>Pedidos</h3>
                <span class="metrics-help" title="Pedidos creados en el mes seleccionado">?</span>
            </div>
            <div class="metrics-data-main">
                <strong>{{ number_format($ordersCount, 0, ',', '.') }}</strong>
                <div class="metrics-breakdown">
                    <span><i class="is-paid"></i>Enviados <b>{{ number_format($shippedOrdersCount, 0, ',', '.') }}</b></span>
                    <span><i class="is-pending"></i>Pendientes <b>{{ number_format($pendingOrdersCount, 0, ',', '.') }}</b></span>
                    <span><i class="is-cancelled"></i>Devueltos <b>{{ number_format($returnedOrdersCount, 0, ',', '.') }}</b></span>
                </div>
            </div>
            <p>{{ $ordersCount > 0 ? 'Pedidos registrados en este periodo' : 'Sin datos en este periodo' }}</p>
        </article>

        <article class="metrics-data-card">
            <div class="metrics-data-card-head">
                <span class="metrics-data-icon">▧</span>
                <h3>Productos publicados</h3>
                <span class="metrics-help" title="Productos creados en el mes seleccionado">?</span>
            </div>
            <div class="metrics-data-main metrics-data-main--single">
                <strong>{{ number_format($productsCount, 0, ',', '.') }}</strong>
            </div>
            <p>{{ $productsCount > 0 ? 'Productos agregados en este periodo' : 'Sin datos en este periodo' }}</p>
        </article>

    </div>
    @else
        @php
            $maxDailyVisits = max(1, (int) $dailyVisits->max('visits'));
        @endphp
        <div class="metrics-card-grid metrics-visits-summary">
            <article class="metrics-data-card">
                <div class="metrics-data-card-head">
                    <span class="metrics-data-icon">◌</span>
                    <h3>Visitas registradas</h3>
                    <span class="metrics-help" title="Una visita por visitante y día">?</span>
                </div>
                <div class="metrics-data-main metrics-data-main--single">
                    <strong>{{ number_format($totalVisits, 0, ',', '.') }}</strong>
                </div>
                <p>Visitas diarias registradas en este periodo</p>
            </article>

            <article class="metrics-data-card">
                <div class="metrics-data-card-head">
                    <span class="metrics-data-icon">◎</span>
                    <h3>Visitantes únicos</h3>
                    <span class="metrics-help" title="Visitantes diferenciados por IP y navegador">?</span>
                </div>
                <div class="metrics-data-main metrics-data-main--single">
                    <strong>{{ number_format($uniqueVisitors, 0, ',', '.') }}</strong>
                </div>
                <p>Estimación basada en navegador e IP protegidos</p>
            </article>

            <article class="metrics-data-card">
                <div class="metrics-data-card-head">
                    <span class="metrics-data-icon">%</span>
                    <h3>Conversión</h3>
                    <span class="metrics-help" title="Pedidos enviados dividido entre visitantes únicos">?</span>
                </div>
                <div class="metrics-data-main metrics-data-main--single">
                    <strong>{{ number_format($visitConversionRate, 2, ',', '.') }}%</strong>
                </div>
                <p>{{ $shippedOrdersCount }} pedidos enviados en este periodo</p>
            </article>
        </div>

        <article class="metrics-data-card metrics-visits-chart">
            <div class="metrics-data-card-head">
                <h3>Visitas por día</h3>
                <span class="metrics-help" title="Distribución diaria de visitas del mes seleccionado">?</span>
            </div>
            @if($dailyVisits->isNotEmpty())
                <div class="metrics-visits-bars" aria-label="Gráfico de visitas por día">
                    @foreach($dailyVisits as $dailyVisit)
                        <div class="metrics-visits-bar-item">
                            <span class="metrics-visits-bar-value">{{ $dailyVisit['visits'] }}</span>
                            <div class="metrics-visits-bar" style="height: {{ max(8, round(($dailyVisit['visits'] / $maxDailyVisits) * 130)) }}px" title="{{ $dailyVisit['visits'] }} visitas el día {{ $dailyVisit['label'] }}"></div>
                            <small>{{ $dailyVisit['label'] }}</small>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="metrics-visits-empty">Aún no hay visitas registradas en este periodo.</p>
            @endif
        </article>

        <article class="metrics-data-card metrics-top-products">
            <div class="metrics-data-card-head">
                <h3>Productos más vistos</h3>
                <span class="metrics-help" title="Productos con más visitantes en el mes seleccionado">?</span>
            </div>
            @if($topViewedProducts->isNotEmpty())
                <div class="metrics-top-products-list">
                    @foreach($topViewedProducts as $topProduct)
                        @if($topProduct->product)
                            <div class="metrics-top-product">
                                <span class="metrics-top-product-rank">{{ $loop->iteration }}</span>
                                <span class="metrics-top-product-name">{{ $topProduct->product->name }}</span>
                                <strong>{{ number_format($topProduct->visits_count, 0, ',', '.') }}</strong>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="metrics-visits-empty">Aún no hay vistas de productos en este periodo.</p>
            @endif
        </article>
    @endif

    @if(!$hasVisitHistory && $showVisits)
        <p class="metrics-page-note">Las visitas aparecerán cuando esté disponible el registro histórico detallado de visitas.</p>
    @endif
</section>
@endsection
