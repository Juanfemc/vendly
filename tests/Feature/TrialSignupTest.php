<?php

use App\Exceptions\TrialPhoneHashConfigurationException;
use App\Http\Requests\TrialSignupRequest;
use App\Jobs\SendWhatsAppTemplate;
use App\Models\Store;
use App\Models\TrialSignupClaim;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\TrialPhoneHashService;
use App\Services\WhatsAppPhoneVerificationService;
use App\Services\WhatsAppRegistrationNotifier;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

function createStoreOwnerForWhatsAppVerification(string $phone = '573001234560'): array
{
    $user = User::factory()->create([
        'role' => 'store',
        'is_active' => true,
    ]);

    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda verificacion',
        'slug' => 'tienda-verificacion-'.Str::random(6),
        'business_type' => 'store',
        'plan' => Store::PLAN_PREMIUM,
        'whatsapp' => $phone,
        'is_active' => true,
    ]);

    return [$user, $store];
}

test('trial signup does not ask for store type', function () {
    $this->get(route('trial-signup.create'))
        ->assertOk()
        ->assertDontSee('Tipo de negocio')
        ->assertDontSee('name="business_type"', false);
});

test('trial signup shows controlled warning when required turnstile is not ready', function () {
    config([
        'services.turnstile.required' => true,
        'services.turnstile.site_key' => '',
        'services.turnstile.secret_key' => 'secret-key',
        'services.whatsapp.require_phone_verification' => true,
    ]);

    $this->get(route('trial-signup.create'))
        ->assertOk()
        ->assertSee('La protección anti abuso no está configurada. Intenta nuevamente más tarde.')
        ->assertSee('class="signup-submit" type="submit" disabled', false);
});

test('trial signup always creates a normal store', function () {
    $this->post(route('trial-signup.store'), [
        'user_name' => 'Cliente Demo',
        'user_email' => 'cliente-demo@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'store_name' => 'Tienda Normal Demo',
        'business_type' => Store::PLAN_PREMIUM,
        'whatsapp' => '573001112233',
        'location' => 'Bogota',
        'whatsapp_consent' => '1',
    ])->assertRedirect(route('admin.store.onboarding'));

    $user = User::where('email', 'cliente-demo@example.com')->firstOrFail();
    $store = Store::where('user_id', $user->id)->firstOrFail();

    expect($store->business_type)->toBe('store')
        ->and($store->name)->toBe('Tienda Normal Demo')
        ->and($store->subdomain)->toBe('tienda-normal-demo')
        ->and($store->subscriptionStatus())->toBe(Store::SUBSCRIPTION_TRIALING)
        ->and($store->whatsapp_consent_at)->not->toBeNull()
        ->and($store->whatsapp_consent_version)->toBe('registration_v1')
        ->and($store->whatsapp_consent_text)->toBe(TrialSignupRequest::WHATSAPP_CONSENT_TEXT)
        ->and($store->whatsapp_consent_source)->toBe('trial_signup')
        ->and(strlen($store->whatsapp_consent_ip_hash))->toBe(64);

    $this->get(route('admin.store.onboarding'))
        ->assertOk()
        ->assertDontSee('Tipo de negocio')
        ->assertDontSee('name="business_type"', false);
});

test('trial signup queues admin and customer whatsapp templates when configured', function () {
    Queue::fake();

    config([
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '123456',
        'services.whatsapp.admin_phone' => '573170613664',
        'services.whatsapp.admin_registration_template' => 'vendly_nuevo_registro',
        'services.whatsapp.customer_welcome_template' => 'vendly_bienvenida_cliente',
        'services.whatsapp.phone_verification_template' => '',
    ]);

    $this->post(route('trial-signup.store'), [
        'user_name' => 'Cliente WhatsApp',
        'user_email' => 'cliente-whatsapp@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'store_name' => 'Tienda WhatsApp',
        'whatsapp' => '+57 300 111 2233',
        'location' => 'Bogota',
        'whatsapp_consent' => '1',
    ])->assertRedirect(route('admin.store.onboarding'));

    Queue::assertPushed(SendWhatsAppTemplate::class, 2);
    expect(WhatsAppMessage::where('audience', 'admin')->firstOrFail()->template)
        ->toBe('vendly_nuevo_registro')
        ->and(WhatsAppMessage::where('audience', 'customer')->firstOrFail()->template)
        ->toBe('vendly_bienvenida_cliente')
        ->and(new SendWhatsAppTemplate(1))->toBeInstanceOf(ShouldBeEncrypted::class);

    app(WhatsAppRegistrationNotifier::class)->notify(
        User::where('email', 'cliente-whatsapp@example.com')->firstOrFail(),
        Store::where('name', 'Tienda WhatsApp')->firstOrFail(),
    );

    Queue::assertPushed(SendWhatsAppTemplate::class, 2);
    expect(WhatsAppMessage::count())->toBe(2);
});

test('trial signup rejects invalid whatsapp numbers', function () {
    $this->post(route('trial-signup.store'), [
        'user_name' => 'Cliente Invalido',
        'user_email' => 'cliente-invalido@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'store_name' => 'Tienda Invalida',
        'whatsapp' => 'telefono incorrecto',
        'whatsapp_consent' => '1',
    ])->assertSessionHasErrors('whatsapp');

    $this->assertDatabaseMissing('users', ['email' => 'cliente-invalido@example.com']);
});

test('trial signup succeeds when whatsapp notification scheduling fails', function () {
    $this->mock(WhatsAppRegistrationNotifier::class)
        ->shouldReceive('notify')
        ->once()
        ->andThrow(new RuntimeException('Queue unavailable'));

    $this->post(route('trial-signup.store'), [
        'user_name' => 'Cliente Cola',
        'user_email' => 'cliente-cola@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'store_name' => 'Tienda Cola',
        'whatsapp' => '3001112233',
        'whatsapp_consent' => '1',
    ])->assertRedirect(route('admin.store.onboarding'));

    $this->assertDatabaseHas('users', ['email' => 'cliente-cola@example.com']);
});

test('missing admin whatsapp does not prevent the customer welcome message', function () {
    Queue::fake();

    config([
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '123456',
        'services.whatsapp.admin_phone' => '',
        'services.whatsapp.customer_welcome_template' => 'vendly_bienvenida_cliente',
        'services.whatsapp.phone_verification_template' => '',
    ]);

    $user = User::factory()->create();
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Tienda bienvenida',
        'slug' => 'tienda-bienvenida',
        'business_type' => 'store',
        'plan' => Store::PLAN_PREMIUM,
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);

    app(WhatsAppRegistrationNotifier::class)->notify($user, $store);

    Queue::assertPushed(SendWhatsAppTemplate::class, 1);
    expect(WhatsAppMessage::count())->toBe(1)
        ->and(WhatsAppMessage::firstOrFail()->audience)->toBe('customer');
});

test('trial signup permanently limits free trials for the same whatsapp number', function () {
    $phone = '573009998877';

    $this->post(route('trial-signup.store'), [
        'user_name' => 'Primer Cliente',
        'user_email' => 'primer-cliente@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'store_name' => 'Primera Tienda',
        'whatsapp' => $phone,
        'whatsapp_consent' => '1',
    ])->assertRedirect(route('admin.store.onboarding'));

    auth()->logout();
    Cache::flush();

    $this->post(route('trial-signup.store'), [
        'user_name' => 'Cliente Repetido',
        'user_email' => 'cliente-repetido@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'store_name' => 'Tienda Repetida',
        'whatsapp' => $phone,
        'whatsapp_consent' => '1',
    ])->assertSessionHasErrors('whatsapp');

    $this->assertDatabaseMissing('users', ['email' => 'cliente-repetido@example.com']);
    expect(Store::where('whatsapp', $phone)->count())->toBe(1);
});

test('deleting a trial store does not release its phone claim', function () {
    $phone = '573009998878';

    $this->post(route('trial-signup.store'), [
        'user_name' => 'Cliente Eliminado',
        'user_email' => 'cliente-eliminado@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'store_name' => 'Tienda Eliminada',
        'whatsapp' => $phone,
        'whatsapp_consent' => '1',
    ])->assertRedirect(route('admin.store.onboarding'));

    Store::where('whatsapp', $phone)->firstOrFail()->delete();
    auth()->logout();

    expect(TrialSignupClaim::count())->toBe(1)
        ->and(TrialSignupClaim::firstOrFail()->store_id)->toBeNull();

    $this->post(route('trial-signup.store'), [
        'user_name' => 'Cliente Nuevo',
        'user_email' => 'cliente-nuevo@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'store_name' => 'Tienda Nueva',
        'whatsapp' => $phone,
        'whatsapp_consent' => '1',
    ])->assertSessionHasErrors('whatsapp');
});

test('trial signup defers whatsapp verification to onboarding when api is configured', function () {
    config([
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '123456',
        'services.whatsapp.phone_verification_template' => 'vendly_verificar_numero',
        'services.whatsapp.require_phone_verification' => true,
    ]);

    $this->post(route('trial-signup.store'), [
        'user_name' => 'Cliente Sin Codigo',
        'user_email' => 'cliente-sin-codigo@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'store_name' => 'Tienda Sin Codigo',
        'whatsapp' => '573001234567',
        'whatsapp_consent' => '1',
    ])->assertRedirect(route('admin.store.onboarding'));

    $user = User::where('email', 'cliente-sin-codigo@example.com')->firstOrFail();
    $store = Store::where('user_id', $user->id)->firstOrFail();

    expect($store->whatsapp_verified_at)->toBeNull();
});

test('onboarding whatsapp verification sends a code without exposing it', function () {
    config([
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '123456',
        'services.whatsapp.phone_verification_template' => 'vendly_verificar_numero',
        'services.whatsapp.require_phone_verification' => true,
    ]);
    Http::fake([
        'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.verification']]], 200),
    ]);

    [$user] = createStoreOwnerForWhatsAppVerification('573001234569');

    $this->actingAs($user)
        ->postJson(route('admin.store.onboarding.whatsapp.send'), [
            'whatsapp' => '300 123 4569',
        ])->assertOk()
        ->assertJsonMissing(['code']);

    Http::assertSent(fn ($request) => $request['to'] === '573001234569'
        && $request['template']['name'] === 'vendly_verificar_numero'
    );
});

test('onboarding verified whatsapp code marks the store as verified and consumes the code', function () {
    config([
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '123456',
        'services.whatsapp.phone_verification_template' => 'vendly_verificar_numero',
        'services.whatsapp.require_phone_verification' => true,
    ]);
    Http::fake([
        'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.verification']]], 200),
    ]);

    [$user, $store] = createStoreOwnerForWhatsAppVerification('573001234568');
    $verification = $this->actingAs($user)
        ->postJson(route('admin.store.onboarding.whatsapp.send'), ['whatsapp' => $store->whatsapp])
        ->assertOk()
        ->json();
    $code = Http::recorded()->last()[0]['template']['components'][0]['parameters'][0]['text'];

    $this->actingAs($user)
        ->postJson(route('admin.store.onboarding.whatsapp.verify'), [
            'whatsapp' => $store->whatsapp,
            'whatsapp_verification_code' => $code,
            'whatsapp_verification_token' => $verification['verification_token'],
        ])->assertOk();

    expect($store->fresh()->whatsapp_verified_at)->not->toBeNull();

    $this->actingAs($user)
        ->postJson(route('admin.store.onboarding.whatsapp.verify'), [
            'whatsapp' => $store->whatsapp,
            'whatsapp_verification_code' => $code,
            'whatsapp_verification_token' => $verification['verification_token'],
        ])->assertUnprocessable()
        ->assertJsonValidationErrors('whatsapp_verification_code');
});

test('onboarding whatsapp verification does not message phones claimed by another store', function () {
    config([
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '123456',
        'services.whatsapp.phone_verification_template' => 'vendly_verificar_numero',
        'services.whatsapp.require_phone_verification' => true,
    ]);
    Http::fake();
    $phone = '573001234575';
    TrialSignupClaim::create([
        'phone_hash' => app(TrialPhoneHashService::class)->make($phone),
        'source' => 'trial_signup',
        'claimed_at' => now(),
    ]);
    [$user] = createStoreOwnerForWhatsAppVerification($phone);

    $this->actingAs($user)
        ->postJson(route('admin.store.onboarding.whatsapp.send'), ['whatsapp' => $phone])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('whatsapp');

    Http::assertNothingSent();
});

test('onboarding whatsapp verification applies request and phone rate limits', function () {
    config([
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '123456',
        'services.whatsapp.phone_verification_template' => 'vendly_verificar_numero',
        'services.whatsapp.require_phone_verification' => true,
    ]);
    Http::fake([
        'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.verification']]], 200),
    ]);
    [$user] = createStoreOwnerForWhatsAppVerification('573001234584');

    foreach (range(1, 3) as $attempt) {
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->actingAs($user)
            ->postJson(route('admin.store.onboarding.whatsapp.send'), ['whatsapp' => '573001234584'])
            ->assertOk();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
        ->actingAs($user)
        ->postJson(route('admin.store.onboarding.whatsapp.send'), ['whatsapp' => '573001234584'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('whatsapp');

    Http::assertSentCount(3);
});

test('whatsapp eligibility is checked inside the send lock before calling meta', function () {
    config([
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '123456',
        'services.whatsapp.phone_verification_template' => 'vendly_verificar_numero',
        'services.whatsapp.require_phone_verification' => true,
    ]);
    Http::fake();

    expect(fn () => app(WhatsAppPhoneVerificationService::class)->send(
        '573001234578',
        fn () => throw ValidationException::withMessages(['whatsapp' => 'No elegible.']),
    ))->toThrow(ValidationException::class);

    Http::assertNothingSent();
});

test('onboarding whatsapp verification returns a controlled error when meta rejects the phone', function () {
    config([
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '123456',
        'services.whatsapp.phone_verification_template' => 'vendly_verificar_numero',
        'services.whatsapp.require_phone_verification' => true,
    ]);
    Http::fake([
        'graph.facebook.com/*' => Http::response(['error' => ['message' => 'Template not approved']], 400),
    ]);
    [$user] = createStoreOwnerForWhatsAppVerification('573001234570');

    $this->actingAs($user)
        ->postJson(route('admin.store.onboarding.whatsapp.send'), [
            'whatsapp' => '300 123 4570',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('whatsapp');
});

test('onboarding whatsapp verification fails closed when meta is not configured', function () {
    config([
        'services.whatsapp.access_token' => '',
        'services.whatsapp.phone_number_id' => '',
        'services.whatsapp.phone_verification_template' => 'vendly_verificar_numero',
        'services.whatsapp.require_phone_verification' => true,
    ]);
    [$user, $store] = createStoreOwnerForWhatsAppVerification('573001234571');

    $this->actingAs($user)
        ->postJson(route('admin.store.onboarding.whatsapp.send'), [
        'whatsapp' => '573001234571',
        ])->assertUnprocessable()
        ->assertJsonValidationErrors('whatsapp');

    expect($store->fresh()->whatsapp_verified_at)->toBeNull();
});

test('whatsapp verification code is invalidated after five failed attempts', function () {
    config([
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '123456',
        'services.whatsapp.phone_verification_template' => 'vendly_verificar_numero',
        'services.whatsapp.require_phone_verification' => true,
    ]);
    Http::fake([
        'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.verification']]], 200),
    ]);
    $phone = '573001234572';
    $token = app(WhatsAppPhoneVerificationService::class)->send($phone);
    $request = Http::recorded()->last()[0];
    $correctCode = $request['template']['components'][0]['parameters'][0]['text'];
    $service = app(WhatsAppPhoneVerificationService::class);

    foreach (range(1, 5) as $attempt) {
        expect(fn () => $service->runVerified($phone, $token, '000000', fn () => true))
            ->toThrow(ValidationException::class);
    }

    expect(fn () => $service->runVerified($phone, $token, $correctCode, fn () => true))
        ->toThrow(ValidationException::class);
});

test('requesting a new whatsapp code does not invalidate the previous code', function () {
    config([
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '123456',
        'services.whatsapp.phone_verification_template' => 'vendly_verificar_numero',
        'services.whatsapp.require_phone_verification' => true,
    ]);
    Http::fake([
        'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.verification']]], 200),
    ]);
    $phone = '573001234573';
    $service = app(WhatsAppPhoneVerificationService::class);
    $oldToken = $service->send($phone);
    $oldCode = Http::recorded()->last()[0]['template']['components'][0]['parameters'][0]['text'];
    $newToken = $service->send($phone);
    $newCode = Http::recorded()->last()[0]['template']['components'][0]['parameters'][0]['text'];

    expect($service->runVerified($phone, $oldToken, $oldCode, fn () => 'verified-old'))
        ->toBe('verified-old')
        ->and($service->runVerified($phone, $newToken, $newCode, fn () => 'verified'))
        ->toBe('verified');
});

test('valid whatsapp code is restored when signup callback fails', function () {
    config([
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '123456',
        'services.whatsapp.phone_verification_template' => 'vendly_verificar_numero',
        'services.whatsapp.require_phone_verification' => true,
    ]);
    Http::fake([
        'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.verification']]], 200),
    ]);
    $phone = '573001234574';
    $service = app(WhatsAppPhoneVerificationService::class);
    $token = $service->send($phone);
    $code = Http::recorded()->last()[0]['template']['components'][0]['parameters'][0]['text'];

    expect(fn () => $service->runVerified($phone, $token, $code, fn () => throw new RuntimeException('Database unavailable')))
        ->toThrow(RuntimeException::class)
        ->and($service->runVerified($phone, $token, $code, fn () => 'retried'))
        ->toBe('retried');
});

test('restored whatsapp code keeps its original expiration', function () {
    config([
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '123456',
        'services.whatsapp.phone_verification_template' => 'vendly_verificar_numero',
        'services.whatsapp.require_phone_verification' => true,
    ]);
    Http::fake([
        'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.verification']]], 200),
    ]);
    $phone = '573001234576';
    $service = app(WhatsAppPhoneVerificationService::class);
    $token = $service->send($phone);
    $code = Http::recorded()->last()[0]['template']['components'][0]['parameters'][0]['text'];

    $this->travel(9)->minutes();
    expect(fn () => $service->runVerified($phone, $token, $code, fn () => throw new RuntimeException('Database unavailable')))
        ->toThrow(RuntimeException::class);

    $this->travel(2)->minutes();
    expect(fn () => $service->runVerified($phone, $token, $code, fn () => true))
        ->toThrow(ValidationException::class);

    expect(Cache::get('whatsapp-verification:'.hash('sha256', $phone.'|'.$token)))->toBeNull();
});

test('changing the trial phone hash key fails closed', function () {
    DB::table('trial_signup_key_guards')->where('id', 1)->update([
        'key_fingerprint' => hash('sha256', 'original-key'),
    ]);
    config(['services.trial.phone_hash_key' => 'different-key']);

    expect(fn () => app(TrialPhoneHashService::class)->make('573001234577'))
        ->toThrow(TrialPhoneHashConfigurationException::class, 'TRIAL_PHONE_HASH_KEY no coincide');
});

test('missing trial key guard fails closed', function () {
    Schema::drop('trial_signup_key_guards');

    expect(fn () => app(TrialPhoneHashService::class)->make('573001234579'))
        ->toThrow(TrialPhoneHashConfigurationException::class, 'Falta la guardia');
});

test('trial key guard migration recovers a partially created table', function () {
    DB::table('trial_signup_key_guards')->delete();
    $migration = require database_path('migrations/2026_06_04_140000_create_trial_signup_key_guards_table.php');

    $migration->up();

    expect(DB::table('trial_signup_key_guards')->where('id', 1)->value('key_fingerprint'))
        ->toBe(hash('sha256', (string) config('services.trial.phone_hash_key')));
});

test('trial key configuration failures return a controlled validation error', function () {
    config([
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '123456',
        'services.whatsapp.phone_verification_template' => 'vendly_verificar_numero',
        'services.whatsapp.require_phone_verification' => true,
        'services.trial.phone_hash_key' => 'different-key',
    ]);
    Http::fake();
    [$user] = createStoreOwnerForWhatsAppVerification('573001234580');

    $this->actingAs($user)
        ->postJson(route('admin.store.onboarding.whatsapp.send'), [
            'whatsapp' => '573001234580',
        ])->assertUnprocessable()
        ->assertJsonValidationErrors('whatsapp');

    Http::assertNothingSent();
});
