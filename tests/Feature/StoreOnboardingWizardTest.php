<?php

use App\Models\Store;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Str;

function createOnboardingStore(array $storeOverrides = []): array
{
    $user = User::factory()->create([
        'role' => 'store',
        'is_active' => true,
    ]);

    $store = Store::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Tienda Wizard',
        'slug' => 'tienda-wizard-' . Str::random(6),
        'subdomain' => 'tienda-wizard-' . Str::random(6),
        'business_type' => 'store',
        'plan' => Store::PLAN_PRO,
        'whatsapp' => '573001234567',
        'is_active' => true,
    ], $storeOverrides));

    return [$user, $store];
}

test('onboarding wizard shows only available steps for pro stores', function () {
    [$user] = createOnboardingStore([
        'name' => '',
        'subdomain' => null,
    ]);

    $this->actingAs($user)
        ->get(route('admin.store.onboarding.wizard'))
        ->assertOk()
        ->assertSee('Paso 1 de 5')
        ->assertSee('Información básica')
        ->assertSee('Identidad visual')
        ->assertSee('Primer producto')
        ->assertSee('Pedidos y entregas')
        ->assertSee('Revisión final')
        ->assertDontSee('Métodos de pago');
});

test('onboarding wizard saves basic step and resumes from next step', function () {
    [$user, $store] = createOnboardingStore([
        'name' => '',
        'subdomain' => null,
        'whatsapp' => '573001234567',
    ]);

    $this->actingAs($user)
        ->post(route('admin.store.onboarding.update'), [
            'step' => 'basic',
            'intent' => 'continue',
            'name' => 'Mi Tienda Guiada',
            'subdomain' => 'mi-tienda-guiada',
            'whatsapp' => '3001234567',
            'location' => 'Cali',
        ])
        ->assertRedirect(route('admin.store.onboarding.wizard', ['step' => 'identity']));

    $store->refresh();

    expect($store->name)->toBe('Mi Tienda Guiada')
        ->and($store->business_type)->toBe('store')
        ->and($store->subdomain)->toBe('mi-tienda-guiada')
        ->and($store->whatsapp)->toBe('573001234567')
        ->and($store->location)->toBe('Cali')
        ->and($store->onboarding_last_step)->toBe('identity');

    $this->actingAs($user)
        ->get(route('admin.store.onboarding.wizard'))
        ->assertOk()
        ->assertSee('Identidad visual')
        ->assertSee('Paso 2 de 5');
});

test('onboarding wizard starts at basic information when there is no saved progress', function () {
    [$user] = createOnboardingStore([
        'name' => 'Tienda ya nombrada',
        'subdomain' => 'tienda-ya-nombrada',
        'whatsapp' => '573001234567',
        'logo_image' => 'stores/logo.webp',
        'brand_color' => '#ff6b00',
        'onboarding_last_step' => null,
        'onboarding_completed_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('admin.store.onboarding.wizard'))
        ->assertOk()
        ->assertSee('Paso 1 de 5')
        ->assertSee('Información básica')
        ->assertSee('Nombre de tienda')
        ->assertDontSee('Tipo de negocio')
        ->assertDontSee('name="business_type"', false);
});

test('onboarding wizard still resumes saved progress', function () {
    [$user] = createOnboardingStore([
        'onboarding_last_step' => 'product',
        'onboarding_completed_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('admin.store.onboarding.wizard'))
        ->assertOk()
        ->assertSee('Paso 3 de 5')
        ->assertSee('Primer producto');
});

test('onboarding wizard cannot be finished when required steps are incomplete', function () {
    [$user, $store] = createOnboardingStore([
        'name' => '',
        'subdomain' => null,
        'logo_image' => null,
        'cover_image' => null,
        'brand_color' => null,
        'shop_copy' => null,
    ]);

    $this->actingAs($user)
        ->post(route('admin.store.onboarding.update'), [
            'step' => 'review',
            'intent' => 'finish',
        ])
        ->assertRedirect(route('admin.store.onboarding.wizard', ['step' => 'basic']));

    expect($store->fresh()->onboarding_completed_at)->toBeNull()
        ->and($store->fresh()->onboarding_last_step)->toBe('basic');
});

test('onboarding wizard rejects unavailable posted steps before processing data', function () {
    [$user, $store] = createOnboardingStore([
        'name' => '',
        'subdomain' => null,
        'whatsapp' => '573001234567',
    ]);

    $this->actingAs($user)
        ->post(route('admin.store.onboarding.update'), [
            'step' => 'payments',
            'intent' => 'continue',
        ])
        ->assertRedirect(route('admin.store.onboarding.wizard', ['step' => 'basic']));

    expect($store->fresh()->name)->toBe('')
        ->and($store->fresh()->onboarding_completed_at)->toBeNull()
        ->and($store->fresh()->onboarding_last_step)->toBe('basic');
});

test('onboarding wizard does not validate fields from unavailable steps', function () {
    [$user, $store] = createOnboardingStore([
        'plan' => Store::PLAN_BASIC,
        'name' => 'Tienda Básica',
        'subdomain' => null,
        'whatsapp' => '573001234567',
    ]);

    $this->actingAs($user)
        ->post(route('admin.store.onboarding.update'), [
            'step' => 'orders',
            'intent' => 'continue',
            'shipping_methods' => [
                ['name' => 'Envío local', 'cost' => 'costo inválido'],
            ],
        ])
        ->assertRedirect(route('admin.store.onboarding.wizard', ['step' => 'basic']))
        ->assertSessionHasNoErrors();

    expect($store->fresh()->onboarding_completed_at)->toBeNull()
        ->and($store->fresh()->onboarding_last_step)->toBe('basic');
});

test('onboarding wizard can create the first product inline', function () {
    [$user, $store] = createOnboardingStore([
        'logo_image' => 'stores/logo.webp',
        'brand_color' => '#ff6b00',
    ]);

    $this->actingAs($user)
        ->post(route('admin.store.onboarding.update'), [
            'step' => 'product',
            'intent' => 'continue',
            'product_name' => 'Camiseta inicial',
            'product_price' => '45000',
            'product_description' => "Algodón suave\nLista para publicar",
            'product_sizes' => 'S, M, L',
            'product_colors' => 'Negro, Blanco',
        ])
        ->assertRedirect(route('admin.store.onboarding.wizard', ['step' => 'orders']));

    $product = Product::where('store_id', $store->id)->firstOrFail();

    expect($product->name)->toBe('Camiseta inicial')
        ->and((float) $product->price)->toBe(45000.0)
        ->and($product->description)->toContain('Algodón suave')
        ->and($product->sizes)->toBe(['S', 'M', 'L'])
        ->and($product->colors)->toBe(['Negro', 'Blanco'])
        ->and($store->fresh()->onboarding_last_step)->toBe('orders');
});

test('onboarding wizard ignores product categories when categories are unavailable', function () {
    [$user, $store] = createOnboardingStore([
        'plan' => Store::PLAN_BASIC,
        'logo_image' => 'stores/logo.webp',
        'brand_color' => '#ff6b00',
    ]);

    $this->actingAs($user)
        ->post(route('admin.store.onboarding.update'), [
            'step' => 'product',
            'intent' => 'continue',
            'product_name' => 'Producto sin categorías',
            'product_price' => '25000',
            'product_category' => 'Categoría manipulada',
        ])
        ->assertRedirect(route('admin.store.onboarding.wizard', ['step' => 'review']))
        ->assertSessionHasNoErrors();

    $product = Product::where('store_id', $store->id)->firstOrFail();

    expect($product->category)->toBeNull()
        ->and($store->fresh()->onboarding_last_step)->toBe('review');
});

test('store onboarding progress uses the setup checklist', function () {
    [, $store] = createOnboardingStore([
        'logo_image' => 'stores/logo.webp',
        'brand_color' => '#ff6b00',
    ]);

    expect(array_keys($store->onboardingChecklist()))->toBe(['setup_product', 'setup_basic', 'setup_identity', 'setup_delivery', 'setup_share'])
        // The fixture already completes basic information and identity: two of five tasks.
        ->and($store->onboardingProgress())->toBe(40);
});

test('new products are inserted at the beginning of the store order', function () {
    [$user, $store] = createOnboardingStore();

    $first = Product::create([
        'name' => 'Producto anterior',
        'slug' => 'producto-anterior-' . Str::random(6),
        'price' => 10000,
        'store_id' => $store->id,
        'user_id' => $user->id,
    ]);

    $newest = Product::create([
        'name' => 'Producto nuevo',
        'slug' => 'producto-nuevo-' . Str::random(6),
        'price' => 20000,
        'store_id' => $store->id,
        'user_id' => $user->id,
    ]);

    expect($newest->fresh()->sort_order)->toBe(1)
        ->and($first->fresh()->sort_order)->toBe(2)
        ->and(Product::where('store_id', $store->id)->orderedForDisplay()->value('id'))->toBe($newest->id);
});
