@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Editar usuario</h2>
</div>

@if ($errors->any())
    <div class="flash error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="list-card">
    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')

        <input type="text" name="name" value="{{ old('name', $user->name) }}" placeholder="Nombre" required>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="Email" required>
        <input type="text" value="{{ $user->role === 'admin' ? 'Administrador' : 'Usuario de tienda' }}" readonly>
        <input type="password" name="password" placeholder="Nueva contraseña (opcional)">
        <input type="password" name="password_confirmation" placeholder="Confirmar nueva contraseña">

        @if($user->role === 'store')
            <label class="field-label" for="active_starts_at">Fecha de inicio</label>
            <input type="date" id="active_starts_at" name="active_starts_at" value="{{ old('active_starts_at', optional($user->active_starts_at)->toDateString()) }}">
            <label class="field-label" for="active_duration_days">Duración total del plan (días)</label>
            <input type="number" id="active_duration_days" name="active_duration_days" value="{{ old('active_duration_days', $user->active_duration_days) }}" min="1" max="3650" placeholder="Ej: 30">

            <div class="flash success">
                Ajuste manual. Si quieres sumar días sin recalcular todo el plan, usa el botón "Extender acceso" en la lista de usuarios.<br>
                Fecha final actual:
                <strong>{{ $user->active_ends_at ? $user->active_ends_at->format('d/m/Y') : 'Sin fecha final' }}</strong>
            </div>
        @endif

        <button type="submit" class="btn">Guardar cambios</button>
    </form>
</div>
@if($user->role === 'store')
<div class="list-card" style="margin-top:24px">
    <h3>Registrar cobro recibido de Vendly</h3>
    <p>Registra únicamente dinero recibido por la suscripción. Los pedidos de sus clientes, las pruebas y las extensiones gratuitas no son cobros de Vendly.</p>
    <p>Este registro no cambia el vencimiento. Después puedes usar «Extender acceso» si corresponde.</p>
    <form method="POST" action="{{ route('admin.users.subscription-payments.store', $user) }}">
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
        <label for="payment_store">Tienda asociada al cobro (un solo registro por cliente y cobro)</label>
        <select id="payment_store" name="store_id" required>
            @foreach($user->stores as $paymentStore)
                <option value="{{ $paymentStore->id }}" @selected((string) old('store_id') === (string) $paymentStore->id)>{{ $paymentStore->name }}</option>
            @endforeach
        </select>
        <label for="payment_plan">Plan cobrado</label>
        <select id="payment_plan" name="plan" required>
            <option value="">Selecciona el plan del cobro</option>
            <option value="pro" @selected(old('plan') === 'pro')>Pro</option>
            <option value="premium" @selected(old('plan') === 'premium')>Premium</option>
        </select>
        <label for="payment_billing_cycle">Periodo cobrado</label>
        <select id="payment_billing_cycle" name="billing_cycle" required>
            @foreach(config('plans.cycles') as $cycleKey => $cycle)
                <option value="{{ $cycleKey }}" @selected(old('billing_cycle', 'monthly') === $cycleKey)>
                    {{ $cycle['label'] }}{{ $cycle['discount'] > 0 ? ' · Ahorra '.$cycle['discount'].'%' : '' }}
                </option>
            @endforeach
        </select>
        <label for="payment_amount">Importe recibido en COP, sin separadores</label>
        <input id="payment_amount" type="number" name="amount" min="1" max="1000000000" step="1" value="{{ old('amount') }}" data-plan-prices='@json(config('plans.prices'))' required>
        <label for="payment_method">Medio de cobro</label>
        <select id="payment_method" name="method" required>
            <option value="transfer" @selected(old('method') === 'transfer')>Transferencia</option>
            <option value="cash" @selected(old('method') === 'cash')>Efectivo</option>
            <option value="other" @selected(old('method') === 'other')>Otro</option>
        </select>
        <label for="payment_reference">Referencia única del comprobante (para efectivo, número de recibo)</label>
        <input id="payment_reference" name="reference" maxlength="150" value="{{ old('reference') }}" required>
        <label for="payment_date">Fecha y hora del cobro recibido, hora de Colombia</label>
        <input id="payment_date" type="datetime-local" name="paid_at" value="{{ old('paid_at', now('America/Bogota')->format('Y-m-d\TH:i')) }}" required>
        <label for="payment_initial_kind">Historial del cliente</label>
        <select id="payment_initial_kind" name="initial_kind" required>
            <option value="">Confirma si ya había pagado antes</option>
            <option value="first_payment" @selected(old('initial_kind') === 'first_payment')>Este es su primer pago real a Vendly</option>
            <option value="renewal" @selected(old('initial_kind') === 'renewal')>Ya había pagado antes: es una renovación</option>
        </select>
        <p>Si ya tiene cobros registrados, el siguiente será una renovación aunque su acceso haya vencido. Para clientes antiguos, confirma su historial; no se deduce de su fecha de vencimiento.</p>
        <label><input type="checkbox" name="money_received" value="1" required> Verifiqué que este dinero fue recibido. No es una promesa de pago.</label>
        <button type="submit" class="btn">Registrar cobro confirmado</button>
    </form>
</div>
<div class="list-card" style="margin-top:24px;overflow-x:auto">
    <h3>Cobros verificados</h3>
    <table>
        <thead><tr><th>Fecha (Colombia)</th><th>Tipo</th><th>Plan</th><th>Periodo</th><th>Importe COP</th><th>Referencia</th></tr></thead>
        <tbody>
        @forelse($payments as $payment)
            <tr><td>{{ $payment->paid_at->timezone('America/Bogota')->format('d/m/Y H:i') }}</td><td>{{ $payment->kind === 'first_payment' ? 'Primer pago' : 'Renovación' }}</td><td>{{ $payment->plan }}</td><td>{{ config('plans.cycles.'.$payment->billing_cycle.'.label', 'Mensual') }}</td><td>$ {{ number_format($payment->amount, 0, ',', '.') }}</td><td>{{ $payment->reference }}</td></tr>
        @empty
            <tr><td colspan="6">Sin cobros verificados registrados. Un plan activo no demuestra un cobro.</td></tr>
        @endforelse
        </tbody>
    </table>
    <h3>Entrega de eventos a Meta</h3>
    <p>Se envían desde el servidor, sin usar la identidad del administrador. Los históricos de más de seis días se conservan localmente y no se envían como conversiones nuevas.</p>
    <table>
        <thead><tr><th>Evento</th><th>Identificador</th><th>Estado</th></tr></thead>
        <tbody>
        @forelse($measurementEvents as $measurementEvent)
            <tr><td>{{ $measurementEvent->event_name }}</td><td>{{ $measurementEvent->event_id }}</td><td>{{ $measurementEvent->sent_at ? 'Recibido por Meta' : ($measurementEvent->discarded_at ? 'Histórico: no enviado' : 'Pendiente') }} {{ $measurementEvent->last_error }}</td></tr>
        @empty
            <tr><td colspan="3">Sin eventos de la medición nueva.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@push('scripts')
<script>
    (() => {
        const amount = document.getElementById('payment_amount');
        const plan = document.getElementById('payment_plan');
        const cycle = document.getElementById('payment_billing_cycle');
        if (!amount || !plan || !cycle) return;
        const prices = JSON.parse(amount.dataset.planPrices || '{}');
        const updateAmount = () => {
            amount.value = prices[plan.value]?.[cycle.value] || '';
        };
        plan.addEventListener('change', updateAmount);
        cycle.addEventListener('change', updateAmount);
        updateAmount();
    })();
</script>
@endpush
@endif
@endsection
