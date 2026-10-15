<?php

namespace App\Support;

use App\Models\Product;
use App\Models\Store;
use App\Models\StoreCategory;
use App\Models\StorePaymentAccount;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class StoreOnboardingSteps
{
    public const BASIC = 'basic';
    public const IDENTITY = 'identity';
    public const PRODUCT = 'product';
    public const ORDERS = 'orders';
    public const PAYMENTS = 'payments';
    public const REVIEW = 'review';
    public const SETUP_PRODUCT = 'setup_product';
    public const SETUP_BASIC = 'setup_basic';
    public const SETUP_IDENTITY = 'setup_identity';
    public const SETUP_DELIVERY = 'setup_delivery';
    public const SETUP_SHARE = 'setup_share';

    public static function allKeys(): array
    {
        return [
            self::BASIC,
            self::IDENTITY,
            self::PRODUCT,
            self::ORDERS,
            self::PAYMENTS,
            self::REVIEW,
        ];
    }

    public static function available(Store $store): array
    {
        $steps = [
            self::BASIC => [
                'label' => 'Información básica',
                'summary' => 'Nombre, enlace público y WhatsApp.',
                'complete' => self::basicComplete($store),
            ],
            self::IDENTITY => [
                'label' => 'Identidad visual',
                'summary' => 'Logo, portada, colores y plantilla disponible.',
                'complete' => self::identityComplete($store),
            ],
        ];

        if (self::productsAvailable()) {
            $steps[self::PRODUCT] = [
                'label' => 'Primer producto',
                'summary' => 'Publica o revisa tu primer producto.',
                'complete' => self::productComplete($store),
            ];
        }

        if ($store->allowsShippingMethods() && Store::supportsShippingMethodsColumn()) {
            $steps[self::ORDERS] = [
                'label' => 'Pedidos y entregas',
                'summary' => 'Opciones de envío y campos del checkout.',
                'complete' => self::ordersComplete($store),
            ];
        }

        if ($store->allowsOnlinePayments() && self::paymentsAvailable()) {
            $steps[self::PAYMENTS] = [
                'label' => 'Métodos de pago',
                'summary' => 'WhatsApp, Mercado Pago y Wompi si los conectas.',
                'complete' => self::paymentsComplete($store),
            ];
        }

        $steps[self::REVIEW] = [
            'label' => 'Revisión final',
            'summary' => 'Confirma y comparte tu tienda.',
            'complete' => Store::supportsOnboardingStateColumns() && filled($store->onboarding_completed_at),
        ];

        return $steps;
    }

    public static function keys(Store $store): array
    {
        return array_keys(self::available($store));
    }

    public static function checklist(Store $store): array
    {
        return self::setupTasks($store);
    }

    public static function progress(Store $store): int
    {
        if (Store::supportsOnboardingStateColumns() && $store->onboarding_completed_at) {
            return 100;
        }

        $checklist = collect(self::checklist($store));

        if ($checklist->isEmpty()) {
            return 100;
        }

        $completed = $checklist
            ->filter(fn (array $item) => (bool) ($item['complete'] ?? false))
            ->count();

        return (int) round(($completed / $checklist->count()) * 100);
    }

    public static function needsOnboarding(Store $store): bool
    {
        if (Store::supportsOnboardingStateColumns() && $store->onboarding_completed_at) {
            return false;
        }

        if (
            Store::supportsOnboardingSetupColumns()
            && blank($store->onboarding_choice)
            && self::productComplete($store)
            && self::identityComplete($store)
            && self::basicComplete($store)
            && self::deliveryComplete($store)
        ) {
            return false;
        }

        return self::progress($store) < 100;
    }

    public static function setupTasks(Store $store): array
    {
        return [
            self::SETUP_PRODUCT => [
                'label' => 'Sube tu primer producto',
                'title' => 'Sube tu primer producto',
                'description' => 'Publica al menos un producto con nombre, precio e imagen para que tu tienda tenga algo listo para vender.',
                'action' => 'Subir producto',
                'href' => route('admin.products.create'),
                'complete' => self::productComplete($store),
            ],
            self::SETUP_BASIC => [
                'label' => 'Información de tu negocio',
                'title' => 'Completa la información de tu negocio',
                'description' => 'Agrega el nombre, WhatsApp y enlace público para que tus clientes sepan dónde encontrarte.',
                'action' => 'Completar información',
                'href' => route('admin.store.onboarding.wizard', ['step' => self::BASIC]),
                'complete' => self::basicComplete($store),
            ],
            self::SETUP_IDENTITY => [
                'label' => 'Personaliza tu tienda',
                'title' => 'Personaliza tu tienda',
                'description' => 'Ajusta logo, portada, colores o texto para que la tienda se vea como tu marca. El logo y la portada son opcionales.',
                'action' => 'Personalizar tienda',
                'href' => url('/admin/store-settings'),
                'complete' => self::identityComplete($store),
            ],
            self::SETUP_DELIVERY => [
                'label' => 'Configurar WhatsApp y entregas',
                'title' => 'Configura WhatsApp y entregas',
                'description' => 'Confirma el WhatsApp de pedidos y agrega las opciones de envío o entrega que tus clientes deben elegir.',
                'action' => 'Configurar entregas',
                'href' => url('/admin/store-settings#delivery-settings'),
                'complete' => self::deliveryComplete($store),
            ],
            self::SETUP_SHARE => [
                'label' => 'Compartir la tienda',
                'title' => 'Comparte tu tienda',
                'description' => 'Copia el enlace o compártelo por WhatsApp para empezar a recibir visitas reales.',
                'action' => 'Compartir tienda',
                'href' => route('admin.store.onboarding'),
                'complete' => self::shareComplete($store),
            ],
        ];
    }

    public static function firstIncompleteRequiredStep(Store $store): ?string
    {
        return self::basicComplete($store) ? null : self::BASIC;
    }

    public static function basicComplete(Store $store): bool
    {
        return trim((string) $store->name) !== ''
            && trim((string) $store->whatsapp) !== ''
            && (
                ! Store::supportsSubdomainColumn()
                || ! $store->allowsSubdomain()
                || trim((string) $store->subdomain) !== ''
            );
    }

    public static function identityComplete(Store $store): bool
    {
        return trim((string) $store->logo_image) !== ''
            || trim((string) $store->cover_image) !== ''
            || trim((string) $store->brand_color) !== ''
            || trim((string) $store->shop_copy) !== '';
    }

    public static function productComplete(Store $store): bool
    {
        return self::productsAvailable() && $store->products()->exists();
    }

    public static function ordersComplete(Store $store): bool
    {
        return count($store->shippingMethods()) > 0 || $store->localDeliveryEnabled();
    }

    public static function deliveryComplete(Store $store): bool
    {
        return trim((string) $store->whatsapp) !== '' && self::ordersComplete($store);
    }

    public static function shareComplete(Store $store): bool
    {
        return (Store::supportsOnboardingSetupColumns() && filled($store->onboarding_store_shared_at))
            || ((int) ($store->views_count ?? 0) > 0);
    }

    public static function paymentsComplete(Store $store): bool
    {
        if (! self::paymentsAvailable()) {
            return false;
        }

        return $store->paymentAccounts()
            ->get()
            ->contains(fn (StorePaymentAccount $account) => $account->isConnected());
    }

    public static function productsAvailable(): bool
    {
        return class_exists(Product::class)
            && Schema::hasTable('products')
            && Route::has('admin.products.create');
    }

    public static function paymentsAvailable(): bool
    {
        return class_exists(StorePaymentAccount::class)
            && Schema::hasTable('store_payment_accounts')
            && Route::has('admin.payments.index');
    }

    public static function categoriesAvailable(Store $store): bool
    {
        return $store->allowsCategories()
            && class_exists(StoreCategory::class)
            && Schema::hasTable('store_categories');
    }
}
