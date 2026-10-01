<?php

use App\Models\Product;
use App\Models\Store;
use App\Models\StoreLanding;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('store user can configure landing without publishing it', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Landing Borrador',
        'slug' => 'landing-borrador',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Producto Landing',
        'price' => 99000,
    ]);

    $this->actingAs($user)
        ->post(route('admin.store-landing.update'), [
            'product_id' => $product->id,
            'headline' => 'Mas que un producto',
            'subtitle' => 'Oferta especial',
            'bundle_enabled' => '1',
            'bundle_quantity' => 2,
            'request_activation' => '1',
        ])
        ->assertRedirect();

    $landing = StoreLanding::firstWhere('store_id', $store->id);

    expect($landing)->not->toBeNull()
        ->and($landing->enabled_by_admin)->toBeFalse()
        ->and($landing->activation_requested_at)->not->toBeNull();

    $this->get('/landing-borrador')
        ->assertOk()
        ->assertSee('Producto Landing')
        ->assertDontSee('Mas que un producto');
});

test('only admin activation makes the single product landing public', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Landing Activa',
        'slug' => 'landing-activa',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Camiseta Landing',
        'price' => 120000,
    ]);
    StoreLanding::create([
        'store_id' => $store->id,
        'product_id' => $product->id,
        'headline' => 'Camiseta de alto rendimiento',
        'bundle_enabled' => true,
        'bundle_quantity' => 2,
        'bundle_price' => 220000,
    ]);

    $this->actingAs($user)
        ->patch(route('admin.stores.landing.activation', $store), ['enabled' => '1'])
        ->assertForbidden();

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->patch(route('admin.stores.landing.activation', $store), ['enabled' => '1'])
        ->assertRedirect();

    expect($store->singleProductLanding()->first()->enabled_by_admin)->toBeTrue();

    $this->get('/landing-activa')
        ->assertOk()
        ->assertSee('Camiseta de alto rendimiento')
        ->assertSee('Pack x2')
        ->assertSee('Comprar ahora');
});

test('landing pack displays the same price used by the cart', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Landing Precio Real',
        'slug' => 'landing-precio-real',
        'whatsapp' => '573001112233',
        'is_active' => true,
        'plan' => 'premium',
    ]);
    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Pack Validado',
        'price' => 250000,
        'has_wholesale_price' => true,
        'wholesale_min_quantity' => 2,
        'wholesale_price' => 235000,
    ]);

    StoreLanding::create([
        'store_id' => $store->id,
        'product_id' => $product->id,
        'headline' => 'Pack sin precio paralelo',
        'bundle_enabled' => true,
        'bundle_quantity' => 2,
        'bundle_price' => 1,
    ]);

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->patch(route('admin.stores.landing.activation', $store), ['enabled' => '1'])
        ->assertRedirect();

    $this->get('/landing-precio-real')
        ->assertOk()
        ->assertSee('$470.000');

    $this->post(route('cart.buy_now', $product->id), ['quantity' => 2])
        ->assertRedirect(route('cart.index', ['store' => $store->slug]));

    $cart = session('carts.' . $store->id);
    $item = collect($cart)->first();

    expect($item['price'])->toBe(235000.0)
        ->and($item['quantity'])->toBe(2);
});

test('store user cannot assign another store product to landing', function () {
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $otherUser = User::factory()->create();
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Landing Propia',
        'slug' => 'landing-propia',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    $otherStore = Store::create([
        'user_id' => $otherUser->id,
        'name' => 'Landing Ajena',
        'slug' => 'landing-ajena',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    $otherProduct = Product::create([
        'user_id' => $otherUser->id,
        'store_id' => $otherStore->id,
        'name' => 'Producto Ajeno',
        'price' => 50000,
    ]);

    $this->actingAs($user)
        ->post(route('admin.store-landing.update'), [
            'product_id' => $otherProduct->id,
            'headline' => 'No permitido',
        ])
        ->assertSessionHasErrors('product_id');

    expect(StoreLanding::where('store_id', $store->id)->exists())->toBeFalse();
});

test('landing review uploads only store available slots', function () {
    Storage::fake('public');
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=');

    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Landing Reseñas',
        'slug' => 'landing-resenas',
        'whatsapp' => '573001112233',
        'is_active' => true,
    ]);
    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Producto con Reseñas',
        'price' => 99000,
    ]);
    $existingImages = collect(range(1, 11))
        ->map(fn ($number) => "store-landings/{$store->id}/reviews/existing-{$number}.jpg")
        ->all();

    StoreLanding::create([
        'store_id' => $store->id,
        'product_id' => $product->id,
        'review_images' => $existingImages,
    ]);

    $this->actingAs($user)
        ->post(route('admin.store-landing.update'), [
            'product_id' => $product->id,
            'review_images' => [
                UploadedFile::fake()->createWithContent('nueva-1.png', $png),
                UploadedFile::fake()->createWithContent('nueva-2.png', $png),
            ],
        ])
        ->assertRedirect();

    $landing = StoreLanding::firstWhere('store_id', $store->id);
    $newImages = collect($landing->reviewImages())
        ->reject(fn ($path) => in_array($path, $existingImages, true))
        ->values();

    expect($landing->reviewImages())->toHaveCount(12)
        ->and($newImages)->toHaveCount(1);

    Storage::disk('public')->assertExists($newImages->first());
});

test('active landing store uses landing checkout design', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create([
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDay(),
    ]);
    $store = Store::create([
        'user_id' => $user->id,
        'name' => 'Landing Checkout',
        'slug' => 'landing-checkout',
        'whatsapp' => '573001112233',
        'is_active' => true,
        'business_type' => 'fashion',
    ]);
    $product = Product::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'name' => 'Producto Checkout',
        'price' => 150000,
    ]);

    StoreLanding::create([
        'store_id' => $store->id,
        'product_id' => $product->id,
        'headline' => 'Checkout directo',
    ]);

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->patch(route('admin.stores.landing.activation', $store), ['enabled' => '1'])
        ->assertRedirect();

    $this->post(route('cart.buy_now', $product->id), ['quantity' => 1])
        ->assertRedirect(route('cart.index', ['store' => $store->slug]));

    $this->get(route('cart.index', ['store' => $store->slug]))
        ->assertOk()
        ->assertSee('cart-page--single-product-landing', false)
        ->assertSee('cart-single-product-landing-checkout.css', false)
        ->assertSee('Checkout de venta directa')
        ->assertDontSee('fashion-checkout-grid', false);
});
