<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\StorePaymentAccount;
use App\Models\User;
use App\Services\MercadoPagoCheckoutService;
use App\Services\WompiCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_mercadopago_webhook_rejects_requests_when_signing_secret_is_missing(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['services.mercadopago.webhook_secret' => null]);
        Http::fake();

        $this->postJson(route('cart.mercadopago.webhook').'?type=payment&data.id=pay_123')
            ->assertUnauthorized();

        Http::assertNothingSent();
    }

    public function test_mercadopago_webhook_accepts_recent_valid_signature(): void
    {
        config(['services.mercadopago.webhook_secret' => 'mp-secret']);
        $order = $this->pendingOrder(paymentMethod: Order::PAYMENT_METHOD_MERCADOPAGO);
        $this->mercadoPagoAccount($order->store);
        $paymentId = 'pay_recent';

        Http::fake([
            'https://api.mercadopago.com/v1/payments/'.$paymentId => Http::response($this->mercadoPagoPayment($order, $paymentId), 200),
        ]);

        $this->postJson(
            route('cart.mercadopago.webhook').'?type=payment&data.id='.$paymentId.'&order='.$order->admin_token,
            [],
            $this->mercadoPagoHeaders($paymentId),
        )->assertOk();

        expect($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_APPROVED);
    }

    public function test_mercadopago_webhook_rejects_invalid_signature(): void
    {
        config(['services.mercadopago.webhook_secret' => 'mp-secret']);
        Http::fake();

        $this->postJson(
            route('cart.mercadopago.webhook').'?type=payment&data.id=pay_bad',
            [],
            ['x-request-id' => 'req_bad', 'x-signature' => 'ts='.now()->timestamp.',v1=invalid'],
        )->assertUnauthorized();

        Http::assertNothingSent();
    }

    public function test_mercadopago_webhook_rejects_old_timestamp(): void
    {
        config([
            'services.mercadopago.webhook_secret' => 'mp-secret',
            'services.mercadopago.webhook_tolerance_minutes' => 5,
        ]);
        Http::fake();
        $paymentId = 'pay_old';

        $this->postJson(
            route('cart.mercadopago.webhook').'?type=payment&data.id='.$paymentId,
            [],
            $this->mercadoPagoHeaders($paymentId, now()->subMinutes(10)->timestamp),
        )->assertUnauthorized();

        Http::assertNothingSent();
    }

    public function test_mercadopago_duplicate_webhook_is_idempotent(): void
    {
        config(['services.mercadopago.webhook_secret' => 'mp-secret']);
        Cache::flush();
        $order = $this->pendingOrder(paymentMethod: Order::PAYMENT_METHOD_MERCADOPAGO);
        $this->mercadoPagoAccount($order->store);
        $paymentId = 'pay_duplicate';
        $headers = $this->mercadoPagoHeaders($paymentId, requestId: 'req_duplicate');

        Http::fake([
            'https://api.mercadopago.com/v1/payments/'.$paymentId => Http::response($this->mercadoPagoPayment($order, $paymentId), 200),
        ]);

        $url = route('cart.mercadopago.webhook').'?type=payment&data.id='.$paymentId.'&order='.$order->admin_token;

        $this->postJson($url, [], $headers)->assertOk();
        $this->postJson($url, [], $headers)->assertOk();

        Http::assertSentCount(1);
        expect($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_APPROVED);
    }

    public function test_mercadopago_payment_with_unexpected_currency_does_not_approve_order(): void
    {
        $order = $this->pendingOrder();

        $approved = app(MercadoPagoCheckoutService::class)->applyPaymentToOrder($order, [
            'id' => 'pay_usd',
            'status' => 'approved',
            'transaction_amount' => 45000,
            'currency_id' => 'USD',
            'external_reference' => $order->admin_token,
        ]);

        expect($approved)->toBeFalse()
            ->and($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING)
            ->and($order->status)->toBe('pendiente')
            ->and($order->paid_at)->toBeNull();
    }

    public function test_mercadopago_payment_with_unexpected_amount_does_not_approve_order(): void
    {
        $order = $this->pendingOrder();

        $approved = app(MercadoPagoCheckoutService::class)->applyPaymentToOrder($order, [
            'id' => 'pay_low_amount',
            'status' => 'approved',
            'transaction_amount' => 1000,
            'currency_id' => 'COP',
            'external_reference' => $order->admin_token,
        ]);

        expect($approved)->toBeFalse()
            ->and($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING);
    }

    public function test_mercadopago_payment_with_wrong_reference_does_not_approve_order(): void
    {
        $order = $this->pendingOrder();

        $approved = app(MercadoPagoCheckoutService::class)->applyPaymentToOrder($order, [
            'id' => 'pay_wrong_reference',
            'status' => 'approved',
            'transaction_amount' => 45000,
            'currency_id' => 'COP',
            'external_reference' => 'wrong-reference',
        ]);

        expect($approved)->toBeFalse()
            ->and($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING);
    }

    public function test_mercadopago_webhook_with_unknown_payment_does_not_approve_order(): void
    {
        config(['services.mercadopago.webhook_secret' => 'mp-secret']);
        $order = $this->pendingOrder(paymentMethod: Order::PAYMENT_METHOD_MERCADOPAGO);
        $this->mercadoPagoAccount($order->store);

        Http::fake([
            'https://api.mercadopago.com/v1/payments/pay_unknown' => Http::response([
                'id' => 'pay_unknown',
                'status' => 'approved',
                'transaction_amount' => 45000,
                'currency_id' => 'COP',
                'external_reference' => 'other-order',
            ], 200),
        ]);

        $this->postJson(
            route('cart.mercadopago.webhook').'?type=payment&data.id=pay_unknown&order='.$order->admin_token,
            [],
            $this->mercadoPagoHeaders('pay_unknown'),
        )->assertOk();

        expect($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING);
    }

    public function test_wompi_webhook_accepts_recent_valid_signature(): void
    {
        $order = $this->pendingOrder(paymentMethod: Order::PAYMENT_METHOD_WOMPI);
        $this->wompiAccount($order->store);

        $this->postJson(route('cart.wompi.webhook'), $this->signedWompiPayload($order))->assertOk();

        expect($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_APPROVED);
    }

    public function test_wompi_webhook_rejects_invalid_signature(): void
    {
        $order = $this->pendingOrder(paymentMethod: Order::PAYMENT_METHOD_WOMPI);
        $this->wompiAccount($order->store);
        $payload = $this->signedWompiPayload($order);
        $payload['signature']['checksum'] = 'invalid';

        $this->postJson(route('cart.wompi.webhook'), $payload)->assertUnauthorized();

        expect($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING);
    }

    public function test_wompi_webhook_rejects_old_timestamp(): void
    {
        config(['services.wompi.webhook_tolerance_minutes' => 5]);
        $order = $this->pendingOrder(paymentMethod: Order::PAYMENT_METHOD_WOMPI);
        $this->wompiAccount($order->store);

        $this->postJson(route('cart.wompi.webhook'), $this->signedWompiPayload($order, now()->subMinutes(10)->timestamp))
            ->assertUnauthorized();

        expect($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING);
    }

    public function test_wompi_duplicate_webhook_is_idempotent_and_does_not_restore_stock_twice(): void
    {
        Cache::flush();
        $order = $this->pendingOrder(paymentMethod: Order::PAYMENT_METHOD_WOMPI, withStock: true);
        $this->wompiAccount($order->store);
        $payload = $this->signedWompiPayload($order);

        $this->postJson(route('cart.wompi.webhook'), $payload)->assertOk();
        $this->postJson(route('cart.wompi.webhook'), $payload)->assertOk();

        $product = $order->items()->first()->product->refresh();
        expect($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_APPROVED)
            ->and($product->stock_quantity)->toBe(1);
    }

    public function test_wompi_transaction_with_unexpected_currency_does_not_approve_order(): void
    {
        $order = $this->pendingOrder();

        $approved = app(WompiCheckoutService::class)->applyTransactionToOrder($order, [
            'id' => 'txn_usd',
            'status' => 'APPROVED',
            'amount_in_cents' => 4500000,
            'currency' => 'USD',
            'reference' => $order->admin_token,
        ]);

        expect($approved)->toBeFalse()
            ->and($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING)
            ->and($order->status)->toBe('pendiente')
            ->and($order->paid_at)->toBeNull();
    }

    public function test_wompi_transaction_with_unexpected_amount_does_not_approve_order(): void
    {
        $order = $this->pendingOrder();

        $approved = app(WompiCheckoutService::class)->applyTransactionToOrder($order, [
            'id' => 'txn_low_amount',
            'status' => 'APPROVED',
            'amount_in_cents' => 1000,
            'currency' => 'COP',
            'reference' => $order->admin_token,
        ]);

        expect($approved)->toBeFalse()
            ->and($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING);
    }

    public function test_wompi_transaction_with_wrong_reference_does_not_approve_order(): void
    {
        $order = $this->pendingOrder();

        $approved = app(WompiCheckoutService::class)->applyTransactionToOrder($order, [
            'id' => 'txn_wrong_reference',
            'status' => 'APPROVED',
            'amount_in_cents' => 4500000,
            'currency' => 'COP',
            'reference' => 'wrong-reference',
        ]);

        expect($approved)->toBeFalse()
            ->and($order->refresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING);
    }

    public function test_store_payment_account_ignores_sensitive_mass_assignment(): void
    {
        [$storeA, $storeB] = [$this->store('store-a'), $this->store('store-b')];
        $account = StorePaymentAccount::updateWompiCredentials($storeA, [
            'public_key' => 'pub_a',
            'private_key' => 'priv_a',
            'events_secret' => 'events_a',
            'integrity_secret' => 'integrity_a',
            'mode' => StorePaymentAccount::MODE_SANDBOX,
            'status' => StorePaymentAccount::STATUS_CONNECTED,
            'connected_at' => now(),
            'disconnected_at' => null,
        ]);

        $account->fill([
            'store_id' => $storeB->id,
            'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
            'status' => StorePaymentAccount::STATUS_DISCONNECTED,
            'public_key' => 'pub_b',
            'private_key' => 'priv_b',
            'events_secret' => 'events_b',
            'integrity_secret' => 'integrity_b',
            'mode' => StorePaymentAccount::MODE_PRODUCTION,
        ])->save();

        $account->refresh();

        expect($account->store_id)->toBe($storeA->id)
            ->and($account->provider)->toBe(StorePaymentAccount::PROVIDER_WOMPI)
            ->and($account->status)->toBe(StorePaymentAccount::STATUS_CONNECTED)
            ->and($account->public_key)->toBe('pub_a')
            ->and($account->private_key)->toBe('priv_a')
            ->and($account->events_secret)->toBe('events_a')
            ->and($account->integrity_secret)->toBe('integrity_a')
            ->and($account->mode)->toBe(StorePaymentAccount::MODE_PRODUCTION);
    }

    public function test_store_user_cannot_access_or_modify_another_store_payment_account(): void
    {
        $userA = User::factory()->create(['role' => 'store']);
        $userB = User::factory()->create(['role' => 'store']);
        $storeA = $this->store('store-a', $userA);
        $storeB = $this->store('store-b', $userB);
        StorePaymentAccount::updateWompiCredentials($storeB, [
            'public_key' => 'pub_b',
            'private_key' => 'priv_b',
            'events_secret' => 'events_b',
            'integrity_secret' => 'integrity_b',
            'mode' => StorePaymentAccount::MODE_SANDBOX,
            'status' => StorePaymentAccount::STATUS_CONNECTED,
            'connected_at' => now(),
            'disconnected_at' => null,
        ]);

        $this->actingAs($userA)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('admin.payments.wompi.update'), [
                'enabled' => '1',
                'mode' => StorePaymentAccount::MODE_PRODUCTION,
                'public_key' => 'pub_a',
                'private_key' => 'priv_a',
                'events_secret' => 'events_a',
                'integrity_secret' => 'integrity_a',
                'store_id' => $storeB->id,
                'provider' => StorePaymentAccount::PROVIDER_MERCADOPAGO,
                'status' => StorePaymentAccount::STATUS_DISCONNECTED,
            ])->assertRedirect(route('admin.payments.index'));

        expect($storeA->wompiAccount()->first()?->public_key)->toBe('pub_a')
            ->and($storeB->wompiAccount()->first()?->public_key)->toBe('pub_b')
            ->and($storeB->wompiAccount()->first()?->provider)->toBe(StorePaymentAccount::PROVIDER_WOMPI)
            ->and($storeB->wompiAccount()->first()?->status)->toBe(StorePaymentAccount::STATUS_CONNECTED);
    }

    public function test_non_admin_user_cannot_access_admin_only_routes(): void
    {
        $user = User::factory()->create(['role' => 'store']);
        $this->store('store-user', $user);

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_sensitive_payment_updates_require_password_confirmation(): void
    {
        $user = User::factory()->create(['role' => 'store', 'password' => Hash::make('password')]);
        $this->store('store-confirm', $user);

        $this->actingAs($user)
            ->post(route('admin.payments.wompi.update'), [
                'enabled' => '1',
                'mode' => StorePaymentAccount::MODE_SANDBOX,
                'public_key' => 'pub',
                'private_key' => 'priv',
                'events_secret' => 'events',
                'integrity_secret' => 'integrity',
            ])
            ->assertRedirect(route('password.confirm'));
    }

    private function pendingOrder(string $paymentMethod = Order::PAYMENT_METHOD_WOMPI, bool $withStock = false): Order
    {
        $store = $this->store('tienda-seguridad-pagos-'.uniqid());

        $order = Order::create([
            'store_id' => $store->id,
            'customer_name' => 'Cliente Seguridad',
            'customer_phone' => '3001234567',
            'customer_address' => 'Calle 1',
            'customer_city' => 'Bogota',
            'customer_document' => '123456',
            'status' => 'pendiente',
            'payment_method' => $paymentMethod,
            'payment_provider' => $paymentMethod,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'payment_expires_at' => now()->addMinutes(30),
            'total' => 45000,
        ]);

        if ($withStock) {
            $product = Product::create([
                'store_id' => $store->id,
                'user_id' => $store->user_id,
                'name' => 'Producto stock',
                'price' => 45000,
                'stock_quantity' => 1,
                'is_sold_out' => false,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => 1,
                'price' => 45000,
            ]);
        }

        return $order;
    }

    private function store(string $slug, ?User $user = null): Store
    {
        $user ??= User::factory()->create(['role' => 'store']);

        return Store::create([
            'user_id' => $user->id,
            'name' => 'Tienda '.$slug,
            'slug' => $slug,
            'business_type' => 'technology',
            'plan' => Store::PLAN_PREMIUM,
            'subscription_status' => Store::SUBSCRIPTION_ACTIVE,
            'whatsapp' => '573001112233',
            'is_active' => true,
        ]);
    }

    private function mercadoPagoAccount(Store $store): StorePaymentAccount
    {
        return StorePaymentAccount::updateMercadoPagoCredentials($store, [
            'access_token' => 'mp_access',
            'refresh_token' => 'mp_refresh',
            'public_key' => 'mp_public',
            'provider_user_id' => 'provider_user',
            'expires_at' => now()->addHour(),
            'connected_at' => now(),
            'disconnected_at' => null,
            'status' => StorePaymentAccount::STATUS_CONNECTED,
        ]);
    }

    private function wompiAccount(Store $store): StorePaymentAccount
    {
        return StorePaymentAccount::updateWompiCredentials($store, [
            'public_key' => 'wompi_public',
            'private_key' => 'wompi_private',
            'events_secret' => 'wompi_events',
            'integrity_secret' => 'wompi_integrity',
            'mode' => StorePaymentAccount::MODE_SANDBOX,
            'connected_at' => now(),
            'disconnected_at' => null,
            'status' => StorePaymentAccount::STATUS_CONNECTED,
        ]);
    }

    private function mercadoPagoHeaders(string $paymentId, ?int $timestamp = null, string $requestId = 'req_123'): array
    {
        $timestamp ??= now()->timestamp;
        $manifest = "id:{$paymentId};request-id:{$requestId};ts:{$timestamp};";
        $hash = hash_hmac('sha256', $manifest, (string) config('services.mercadopago.webhook_secret'));

        return [
            'x-request-id' => $requestId,
            'x-signature' => "ts={$timestamp},v1={$hash}",
        ];
    }

    private function mercadoPagoPayment(Order $order, string $paymentId): array
    {
        return [
            'id' => $paymentId,
            'status' => 'approved',
            'transaction_amount' => 45000,
            'currency_id' => 'COP',
            'external_reference' => $order->admin_token,
        ];
    }

    private function signedWompiPayload(Order $order, ?int $timestamp = null, array $transactionOverrides = []): array
    {
        $timestamp ??= now()->timestamp;
        $transaction = array_merge([
            'id' => 'txn_'.$order->id,
            'status' => 'APPROVED',
            'amount_in_cents' => 4500000,
            'currency' => 'COP',
            'reference' => $order->admin_token,
            'finalized_at' => now()->toIso8601String(),
        ], $transactionOverrides);
        $properties = [
            'transaction.id',
            'transaction.status',
            'transaction.amount_in_cents',
            'transaction.reference',
            'transaction.currency',
        ];
        $payload = collect($properties)
            ->map(fn (string $property) => data_get(['transaction' => $transaction], $property, ''))
            ->implode('');
        $checksum = hash('sha256', $payload.$timestamp.'wompi_events');

        return [
            'event' => 'transaction.updated',
            'data' => [
                'transaction' => $transaction,
            ],
            'signature' => [
                'properties' => $properties,
                'checksum' => $checksum,
                'timestamp' => (string) $timestamp,
            ],
        ];
    }
}
