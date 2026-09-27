<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Services\MercadoPagoCheckoutService;
use App\Services\WompiCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    private function pendingOrder(): Order
    {
        $user = User::factory()->create();

        $store = Store::create([
            'user_id' => $user->id,
            'name' => 'Tienda Seguridad Pagos',
            'slug' => 'tienda-seguridad-pagos',
            'whatsapp' => '573001112233',
            'is_active' => true,
        ]);

        return Order::create([
            'store_id' => $store->id,
            'customer_name' => 'Cliente Seguridad',
            'customer_phone' => '3001234567',
            'customer_address' => 'Calle 1',
            'customer_city' => 'Bogota',
            'customer_document' => '123456',
            'status' => 'pendiente',
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'total' => 45000,
        ]);
    }
}
