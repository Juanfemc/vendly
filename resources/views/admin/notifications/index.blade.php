@extends('layouts.admin')

@section('content')
<div class="header">
    <div>
        <h2>Notificaciones</h2>
        <p style="margin:6px 0 0; color:#6b7280;">Revisa pedidos, reseñas y actividad importante de la tienda.</p>
    </div>

    @if($unreadCount > 0)
        <form method="POST" action="{{ route('admin.notifications.read-all') }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-secondary">Marcar todas como leídas</button>
        </form>
    @endif
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

<form method="GET" action="{{ route('admin.notifications.index') }}" class="list-card order-filter-panel">
    <label>
        <span>Tipo</span>
        <select name="type">
            <option value="">Todas</option>
            @foreach($typeOptions as $value => $label)
                <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>

    <label>
        <span>Estado</span>
        <select name="status">
            <option value="">Todas</option>
            <option value="unread" @selected($status === 'unread')>No leídas</option>
            <option value="read" @selected($status === 'read')>Leídas</option>
        </select>
    </label>

    <button type="submit" class="btn">Filtrar</button>
</form>

@if($notifications->isEmpty())
    <div class="panel-empty">
        <h3>No hay notificaciones</h3>
        <p>Cuando lleguen pedidos o reseñas, aparecerán aquí.</p>
    </div>
@else
    <div class="panel-list">
        @foreach($notifications as $notification)
            <article class="list-card resource-card notification-card {{ $notification->read_at ? 'is-read' : 'is-unread' }}">
                <div class="resource-card__main">
                    <div class="resource-card__header">
                        <div>
                            <h3 class="resource-card__title">{{ $notification->title }}</h3>
                            <p class="resource-card__subtitle">{{ $notification->message }}</p>
                        </div>
                        <div class="resource-badges">
                            @if(! $notification->read_at)
                                <span class="resource-badge badge-warning">Nueva</span>
                            @endif
                            <span class="resource-badge">{{ $typeOptions[$notification->type] ?? 'Actividad' }}</span>
                        </div>
                    </div>

                    <div class="resource-metrics">
                        @if(auth()->user()?->isAdmin() && $notification->store)
                            <div class="resource-metric">
                                <span class="resource-metric__label">Tienda</span>
                                <span class="resource-metric__value">{{ $notification->store->name }}</span>
                            </div>
                        @endif
                        <div class="resource-metric">
                            <span class="resource-metric__label">Fecha</span>
                            <span class="resource-metric__value">{{ $notification->created_at?->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                </div>

                <div class="resource-actions">
                    @if($notification->action_url)
                        <a class="btn" href="{{ route('admin.notifications.read', ['notification' => $notification->id, 'redirect' => $notification->action_url]) }}">Ver</a>
                    @endif

                    @if(! $notification->read_at)
                        <form method="POST" action="{{ route('admin.notifications.read', $notification) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-secondary">Marcar leída</button>
                        </form>
                    @endif
                </div>
            </article>
        @endforeach
    </div>

    @if($notifications->hasPages())
        <div class="list-card admin-pagination">
            {{ $notifications->onEachSide(1)->links('pagination::bootstrap-4') }}
        </div>
    @endif
@endif
@endsection
