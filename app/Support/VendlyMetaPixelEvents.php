<?php

namespace App\Support;

use App\Models\Store;
use App\Models\User;

class VendlyMetaPixelEvents
{
    public const HIGH_INTENT_MIN_PROGRESS = 60;
    public const HIGH_INTENT_MIN_PRODUCTS = 5;

    public static function shouldTrackForUser(?User $user, ?Store $store): bool
    {
        if (! $user || ! $store || $user->isAdmin()) {
            return false;
        }

        return (int) $store->user_id === (int) $user->id;
    }

    public static function storeCreated(Store $store): array
    {
        return self::event('StoreCreated', $store, eventKey: 'store-created-' . $store->id);
    }

    public static function completeRegistration(Store $store): array
    {
        return self::event('CompleteRegistration', $store, eventKey: 'complete-registration-' . $store->id);
    }

    public static function startTrial(Store $store): array
    {
        return self::event('StartTrial', $store, eventKey: 'start-trial-' . $store->id);
    }

    public static function firstProductCreated(Store $store): array
    {
        return self::event('FirstProductCreated', $store, eventKey: 'first-product-' . $store->id);
    }

    public static function templateSelected(Store $store): array
    {
        return self::event('TemplateSelected', $store, [
            'template' => (string) $store->business_type,
        ], 'template-selected-' . $store->id . '-' . $store->business_type);
    }

    public static function storeConfigured(Store $store): array
    {
        return self::event('StoreConfigured', $store, eventKey: 'store-configured-' . $store->id);
    }

    public static function highIntent(Store $store): ?array
    {
        $productCount = (int) $store->products()->count();
        $progress = (int) $store->onboardingProgress();

        if ($productCount < self::HIGH_INTENT_MIN_PRODUCTS || $progress < self::HIGH_INTENT_MIN_PROGRESS) {
            return null;
        }

        return self::event('HighIntent', $store, [
            'product_count' => $productCount,
            'onboarding_progress' => $progress,
        ], 'high-intent-' . $store->id);
    }

    public static function eventsWithHighIntent(Store $store, array $events): array
    {
        $highIntent = self::highIntent($store);

        if ($highIntent) {
            $events[] = $highIntent;
        }

        return $events;
    }

    private static function event(string $event, Store $store, array $payload = [], ?string $eventKey = null): array
    {
        return [
            'event' => $event,
            'eventKey' => $eventKey,
            'payload' => array_filter(array_merge(self::storePayload($store), $payload), fn ($value) => $value !== null && $value !== ''),
        ];
    }

    private static function storePayload(Store $store): array
    {
        return [
            'plan' => (string) ($store->plan ?? Store::PLAN_PRO),
            'business_type' => (string) $store->business_type,
            'subscription_status' => $store->subscriptionStatus(),
        ];
    }
}
