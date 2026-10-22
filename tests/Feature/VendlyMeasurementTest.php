<?php

use App\Jobs\SendMetaConversionsEvent;
use App\Models\Product;
use App\Models\Store;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Models\VendlyMeasurementEvent;
use App\Services\DeliverVendlyMeasurement;
use App\Services\MetaConversionsApiService;
use App\Services\SubscriptionStatsService;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    config(['services.meta.landing_pixel_id' => '1234567890', 'services.meta.conversions_access_token' => 'test-token',
        'services.meta.test_event_code' => null]);
    Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1], 200)]);
    Queue::fake();
});

function measurementFixture(): array
{
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user = User::factory()->create(['role' => 'store', 'is_active' => true]);
    $store = Store::create(['user_id' => $user->id, 'name' => 'Tienda real', 'slug' => 'tienda-'.Str::random(8),
        'whatsapp' => '573001234567', 'plan' => Store::PLAN_PRO, 'business_type' => 'store', 'is_active' => true,
        'subscription_status' => Store::SUBSCRIPTION_ACTIVE, 'subscription_ends_at' => now()->addMonth()]);

    return [$admin, $user, $store];
}

function measurementReceipt(Store $store, array $overrides = []): array
{
    return [...[
        'store_id' => $store->id, 'plan' => Store::PLAN_PRO, 'amount' => 10000, 'method' => 'transfer',
        'reference' => 'REF-'.Str::uuid(), 'idempotency_key' => (string) Str::uuid(),
        'paid_at' => now('America/Bogota')->subMinute()->format('Y-m-d\TH:i'),
        'initial_kind' => 'first_payment', 'money_received' => '1',
    ], ...$overrides];
}

test('manual extensions and activations never report a subscription payment', function () {
    [$admin, $user, $store] = measurementFixture();
    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
        ->patch(route('admin.users.extend', $user), ['extend_days' => 30])->assertRedirect();
    $this->patch(route('admin.stores.subscription.activate', $store), ['duration_days' => 30, 'plan' => 'pro'])->assertRedirect();
    expect(SubscriptionPayment::count())->toBe(0)->and(VendlyMeasurementEvent::count())->toBe(0);
    Queue::assertNotPushed(SendMetaConversionsEvent::class);
    Http::assertNothingSent();
});

test('confirmed first receipt is idempotent and does not extend access', function () {
    [$admin, $user, $store] = measurementFixture();
    $ends = $store->subscription_ends_at->toDateTimeString();
    $data = measurementReceipt($store);
    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);
    $url = route('admin.users.subscription-payments.store', $user);
    $this->post($url, $data)->assertSessionHasNoErrors();
    $this->post($url, $data)->assertSessionHasNoErrors();
    $this->post($url, [...$data, 'idempotency_key' => (string) Str::uuid()])->assertSessionHasNoErrors();
    expect(SubscriptionPayment::count())->toBe(1)->and(VendlyMeasurementEvent::count())->toBe(1)
        ->and($store->refresh()->subscription_ends_at->toDateTimeString())->toBe($ends);
    $event = VendlyMeasurementEvent::firstOrFail();
    expect($event->event_name)->toBe('Subscribe')->and($event->custom_data['value'])->toBe(10000)
        ->and($event->custom_data['currency'])->toBe('COP')->and($event->custom_data['payment_kind'])->toBe('first_payment');
    Http::assertNothingSent();
});

test('expired customers and second stores renew at customer level rather than becoming new customers', function () {
    [$admin, $user, $store] = measurementFixture();
    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);
    $url = route('admin.users.subscription-payments.store', $user);
    $this->post($url, measurementReceipt($store))->assertSessionHasNoErrors();
    $store->update(['subscription_status' => Store::SUBSCRIPTION_EXPIRED, 'subscription_ends_at' => now()->subDay()]);
    $secondStore = $store->replicate(['admin_token', 'subdomain']);
    $secondStore->slug = 'otra-'.Str::random(8);
    $secondStore->save();
    $this->post($url, measurementReceipt($secondStore))->assertSessionHasNoErrors();
    expect(SubscriptionPayment::orderBy('id')->pluck('kind')->all())->toBe(['first_payment', 'renewal'])
        ->and(VendlyMeasurementEvent::orderBy('id')->pluck('event_name')->all())->toBe(['Subscribe', 'SubscriptionRenewed']);
});

test('first recorded receipt may be a historical customer renewal without inventing an acquisition', function () {
    [$admin, $user, $store] = measurementFixture();
    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('admin.users.subscription-payments.store', $user), measurementReceipt($store, ['initial_kind' => 'renewal']))
        ->assertSessionHasNoErrors();
    expect(VendlyMeasurementEvent::first()->event_name)->toBe('SubscriptionRenewed');
});

test('receipts require confirmed money and cannot use somebody elses store', function () {
    [$admin, $user, $store] = measurementFixture();
    [, , $otherStore] = measurementFixture();
    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);
    $url = route('admin.users.subscription-payments.store', $user);
    $this->post($url, measurementReceipt($store, ['money_received' => '0']))->assertSessionHasErrors('money_received');
    $this->post($url, measurementReceipt($otherStore))->assertSessionHasErrors('store_id');
    $this->post($url, measurementReceipt($store, ['amount' => 0]))->assertSessionHasErrors('amount');
    expect(SubscriptionPayment::count())->toBe(0);
    $this->actingAs($user)->post($url, measurementReceipt($store))->assertForbidden();
});

test('reused references with different amounts are rejected', function () {
    [$admin, $user, $store] = measurementFixture();
    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);
    $url = route('admin.users.subscription-payments.store', $user);
    $data = measurementReceipt($store);
    $this->post($url, $data)->assertSessionHasNoErrors();
    $this->post($url, [...$data, 'amount' => 25000])->assertSessionHasErrors('reference');
    expect(SubscriptionPayment::count())->toBe(1);
});

test('settings without a sellable product do not activate and a valid product activates once', function () {
    [, $user, $store] = measurementFixture();
    $store->update(['name' => 'Nombre actualizado']);
    expect(VendlyMeasurementEvent::count())->toBe(0);
    $product = Product::create(['store_id' => $store->id, 'user_id' => $user->id, 'name' => 'Camiseta', 'price' => 15000]);
    expect(VendlyMeasurementEvent::count())->toBe(0);
    $product->update(['image' => 'products/camiseta.jpg']);
    $store->update(['name' => 'Otra actualización']);
    $product->update(['price' => 16000]);
    expect(VendlyMeasurementEvent::count())->toBe(1)->and(VendlyMeasurementEvent::first()->event_name)->toBe('StoreConfigured');
});

test('unavailable stores and invalid whatsapp do not activate', function () {
    [, $user, $store] = measurementFixture();
    $store->update(['is_active' => false]);
    Product::create(['store_id' => $store->id, 'user_id' => $user->id, 'name' => 'Producto', 'price' => 1000, 'image' => 'real.jpg']);
    $store->update(['is_active' => true, 'whatsapp' => 'no configurado']);
    expect(VendlyMeasurementEvent::count())->toBe(0);
    $store->update(['whatsapp' => '573001234567']);
    expect(VendlyMeasurementEvent::count())->toBe(1);
});

test('delivery uses actual receipt amount and stable id without admin browser identity', function () {
    [$admin, $user, $store] = measurementFixture();
    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('admin.users.subscription-payments.store', $user), measurementReceipt($store))->assertSessionHasNoErrors();
    $event = VendlyMeasurementEvent::first();
    $sender = app(DeliverVendlyMeasurement::class);
    $sender->send($event->id);
    $sender->send($event->id);
    Http::assertSentCount(1);
    Http::assertSent(function ($request) use ($event, $user) {
        $data = $request->data()['data'][0];

        return $data['event_id'] === $event->event_id && $data['event_time'] === $event->occurred_at->timestamp
            && $data['custom_data']['value'] === 10000 && $data['custom_data']['currency'] === 'COP'
            && $data['user_data']['em'] === hash('sha256', mb_strtolower($user->email))
            && ! isset($data['user_data']['client_ip_address']) && ! isset($data['user_data']['client_user_agent']);
    });
    expect($event->refresh()->sent_at)->not->toBeNull();
    expect(DB::table('vendly_measurement_events')->value('user_data'))->not->toContain(hash('sha256', mb_strtolower($user->email)));
});

test('failed deliveries remain pending and retry with the same event id', function () {
    [$admin, $user, $store] = measurementFixture();
    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('admin.users.subscription-payments.store', $user), measurementReceipt($store))->assertSessionHasNoErrors();
    $event = VendlyMeasurementEvent::first();
    Http::swap(new Factory);
    Http::fake(['graph.facebook.com/*' => Http::sequence()->push([], 400)->push(['events_received' => 1], 200)]);
    app(DeliverVendlyMeasurement::class)->send($event->id);
    expect($event->refresh()->sent_at)->toBeNull()->and($event->last_error)->not->toBeNull();
    $event->update(['next_attempt_at' => now()->subMinute()]);
    app(DeliverVendlyMeasurement::class)->send($event->id);
    expect($event->refresh()->sent_at)->not->toBeNull();
    Http::assertSentCount(2);
    Http::assertSent(fn ($request) => $request->data()['data'][0]['event_id'] === $event->event_id);
});

test('historical money stays in receipts but is not sent as a current conversion', function () {
    [$admin, $user, $store] = measurementFixture();
    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('admin.users.subscription-payments.store', $user), measurementReceipt($store, [
            'paid_at' => now('America/Bogota')->subDays(10)->format('Y-m-d\TH:i'),
        ]))->assertSessionHasNoErrors();
    $this->artisan('vendly:send-measurements')->assertSuccessful();
    expect(SubscriptionPayment::count())->toBe(1)->and(VendlyMeasurementEvent::first()->discarded_at)->not->toBeNull();
    Http::assertNothingSent();
});

test('dashboard sums verified receipts separately from access plan estimates', function () {
    [$admin, $user, $store] = measurementFixture();
    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);
    $url = route('admin.users.subscription-payments.store', $user);
    $this->post($url, measurementReceipt($store))->assertSessionHasNoErrors();
    $this->post($url, measurementReceipt($store))->assertSessionHasNoErrors();
    $stats = app(SubscriptionStatsService::class)->summary();
    expect($stats['received_total'])->toBe(20000)->and($stats['first_payments'])->toBe(1)->and($stats['renewal_payments'])->toBe(1);
    $this->get(route('admin.users.edit', $user))->assertOk()->assertSee('Cobros verificados')->assertSee('Renovación');
    $this->get(route('dashboard'))->assertOk()->assertSee('Cobros confirmados de Vendly');
});

test('old queued activation jobs cannot emit unverified money events', function () {
    [, , $store] = measurementFixture();
    (new SendMetaConversionsEvent($store->id, 'SubscriptionActivated'))->handle(app(MetaConversionsApiService::class));
    (new SendMetaConversionsEvent($store->id, 'SubscriptionRenewed'))->handle(app(MetaConversionsApiService::class));
    Http::assertNothingSent();
});

test('missing configuration and test mode leave real events pending', function () {
    [$admin, $user, $store] = measurementFixture();
    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('admin.users.subscription-payments.store', $user), measurementReceipt($store))->assertSessionHasNoErrors();
    $event = VendlyMeasurementEvent::first();
    config(['services.meta.conversions_access_token' => null]);
    app(DeliverVendlyMeasurement::class)->send($event->id);
    expect($event->refresh()->sent_at)->toBeNull()->and($event->last_error)->toBe('missing_configuration');
    $event->update(['next_attempt_at' => null]);
    config(['services.meta.conversions_access_token' => 'test-token', 'services.meta.test_event_code' => 'TEST123']);
    app(DeliverVendlyMeasurement::class)->send($event->id);
    expect($event->refresh()->sent_at)->toBeNull()->and($event->last_error)->toBe('test_event_code_enabled');
    Http::assertNothingSent();
});

test('a successful http response without accepted events is not marked delivered', function () {
    [$admin, $user, $store] = measurementFixture();
    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('admin.users.subscription-payments.store', $user), measurementReceipt($store))->assertSessionHasNoErrors();
    Http::swap(new Factory);
    Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 0], 200)]);
    $event = VendlyMeasurementEvent::first();
    app(DeliverVendlyMeasurement::class)->send($event->id);
    expect($event->refresh()->sent_at)->toBeNull()->and($event->last_error)->toBe('RuntimeException');
});

test('baseline excludes already configured stores from new conversions and is idempotent', function () {
    [, $user, $store] = measurementFixture();
    Product::withoutEvents(fn () => Product::create(['store_id' => $store->id, 'user_id' => $user->id,
        'name' => 'Existente', 'price' => 1000, 'image' => 'existente.jpg', 'admin_token' => (string) Str::uuid()]));
    $this->artisan('vendly:baseline-measurements')->assertSuccessful();
    $this->artisan('vendly:baseline-measurements')->assertSuccessful();
    $this->artisan('vendly:send-measurements')->assertSuccessful();
    expect(VendlyMeasurementEvent::count())->toBe(1)
        ->and(VendlyMeasurementEvent::first()->last_error)->toBe('baseline_existing_store')
        ->and(app(SubscriptionStatsService::class)->summary()['measured_activations'])->toBe(0);
    Http::assertNothingSent();
});

test('owner activation includes its website source while admin-assisted setup has no admin identity', function () {
    [$admin, $user, $store] = measurementFixture();
    $request = Request::create('https://vendlysuite.com/admin/products', 'POST', [], [], [], [
        'HTTP_USER_AGENT' => 'CustomerBrowser', 'REMOTE_ADDR' => '203.0.113.4',
    ]);
    app()->instance('request', $request);
    $request->setUserResolver(fn () => $user);
    Product::create(['store_id' => $store->id, 'user_id' => $user->id, 'name' => 'Producto', 'price' => 1000, 'image' => 'real.jpg']);
    $event = VendlyMeasurementEvent::firstOrFail();
    expect($event->action_source)->toBe('website')->and($event->event_source_url)->toBe('https://vendlysuite.com/admin/products');
    app(DeliverVendlyMeasurement::class)->send($event->id);
    Http::assertSent(fn ($httpRequest) => $httpRequest->data()['data'][0]['event_source_url'] === 'https://vendlysuite.com/admin/products'
        && $httpRequest->data()['data'][0]['action_source'] === 'website'
        && $httpRequest->data()['data'][0]['user_data']['client_user_agent'] === 'CustomerBrowser');

    $request->setUserResolver(fn () => $admin);
    $otherStore = $store->replicate(['admin_token', 'subdomain']);
    $otherStore->slug = 'admin-'.Str::random(8);
    $otherStore->save();
    Product::create(['store_id' => $otherStore->id, 'user_id' => $user->id, 'name' => 'Otro', 'price' => 1000, 'image' => 'otro.jpg']);
    $adminEvent = VendlyMeasurementEvent::where('store_id', $otherStore->id)->firstOrFail();
    expect($adminEvent->action_source)->toBe('system_generated')
        ->and($adminEvent->event_source_url)->toBeNull()->and($adminEvent->user_data)->not->toHaveKey('client_user_agent');
});
