<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\VendlyMeasurementService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubscriptionPaymentController extends Controller
{
    public function store(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless($user->role === 'store', 404);

        $data = $request->validate([
            'store_id' => ['required', 'integer', Rule::exists('stores', 'id')->where('user_id', $user->id)],
            'plan' => ['required', Rule::in([Store::PLAN_PRO, Store::PLAN_PREMIUM])],
            'billing_cycle' => ['required', Rule::in(array_keys(config('plans.cycles')))],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'method' => ['required', Rule::in(['transfer', 'cash', 'other'])],
            'reference' => ['required', 'string', 'max:150'],
            'idempotency_key' => ['required', 'uuid'],
            'paid_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'initial_kind' => ['required', Rule::in(['first_payment', 'renewal'])],
            'money_received' => ['accepted'],
        ]);
        $paidAt = Carbon::createFromFormat('Y-m-d\TH:i', $data['paid_at'], 'America/Bogota')->startOfMinute()->utc();
        if ($paidAt->isFuture() || trim($data['reference']) === '') {
            throw ValidationException::withMessages(['paid_at' => 'Indica la fecha real del cobro recibido y una referencia válida.']);
        }

        $expectedAmount = config('plans.prices.'.$data['plan'].'.'.$data['billing_cycle']);
        if (! is_int($expectedAmount) || (int) $data['amount'] !== $expectedAmount) {
            throw ValidationException::withMessages([
                'amount' => 'El importe no coincide con el precio configurado para ese plan y periodo.',
            ]);
        }

        try {
            DB::transaction(function () use ($request, $user, $data, $paidAt) {
                // Serialize per customer, including Premium customers with several stores.
                User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $reference = trim($data['reference']);
                $key = hash('sha256', $data['method'].'|'.mb_strtolower($reference));
                $existing = SubscriptionPayment::where('reference_key', $key)
                    ->orWhere('idempotency_key', $data['idempotency_key'])->first();
                if ($existing) {
                    if ((int) $existing->user_id !== $user->id || (int) $existing->store_id !== (int) $data['store_id']
                        || $existing->reference_key !== $key || $existing->amount !== (int) $data['amount']
                        || $existing->plan !== $data['plan'] || $existing->billing_cycle !== $data['billing_cycle']
                        || ! $existing->paid_at->equalTo($paidAt)) {
                        throw ValidationException::withMessages(['reference' => 'La referencia o el formulario ya se usaron para otro cobro.']);
                    }

                    return;
                }

                $lastPayment = SubscriptionPayment::where('user_id', $user->id)->orderByDesc('paid_at')->first();
                if ($lastPayment && $paidAt->lt($lastPayment->paid_at)) {
                    throw ValidationException::withMessages(['paid_at' => 'Registra los cobros en orden cronológico. Ya existe un cobro posterior.']);
                }
                $kind = $lastPayment ? 'renewal' : $data['initial_kind'];
                $payment = SubscriptionPayment::create([
                    'user_id' => $user->id, 'store_id' => $data['store_id'], 'recorded_by' => $request->user()->id,
                    'plan' => $data['plan'], 'billing_cycle' => $data['billing_cycle'], 'amount' => $data['amount'], 'currency' => 'COP', 'kind' => $kind,
                    'method' => $data['method'], 'reference' => $reference, 'reference_key' => $key,
                    'idempotency_key' => $data['idempotency_key'], 'paid_at' => $paidAt,
                ]);
                $store = Store::findOrFail($data['store_id']);
                $event = app(VendlyMeasurementService::class)->capture(
                    $store,
                    $kind === 'first_payment' ? 'Subscribe' : 'SubscriptionRenewed',
                    'subscription-payment-'.$payment->id,
                    ['value' => $payment->amount, 'currency' => 'COP', 'payment_kind' => $kind,
                        'payment_id' => (string) $payment->id, 'plan' => $payment->plan,
                        'billing_cycle' => $payment->billing_cycle],
                    $paidAt,
                );
                // Payment plan is a receipt snapshot, independent of later settings changes.
                $event->update(['store_payload' => [...$event->store_payload, 'plan' => $payment->plan]]);
            });
        } catch (UniqueConstraintViolationException $exception) {
            // A concurrent request for another customer can race on a receipt reference.
            throw ValidationException::withMessages(['reference' => 'Esta referencia o formulario ya fue registrado. Revisa los cobros existentes.']);
        }

        return back()->with('success', 'Cobro confirmado registrado. No se modificó el vencimiento; usa Extender acceso si corresponde.');
    }
}
