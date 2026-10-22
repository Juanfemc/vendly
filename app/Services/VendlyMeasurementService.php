<?php

namespace App\Services;

use App\Models\Store;
use App\Models\VendlyMeasurementEvent;

class VendlyMeasurementService
{
    public function capture(Store $store, string $name, string $id, array $data = [], mixed $occurredAt = null): VendlyMeasurementEvent
    {
        $userData = MetaConversionsApiService::userDataForStore($store);
        $actionSource = $name === 'StoreConfigured' ? 'system_generated' : 'other';
        $sourceUrl = null;
        // Only the owner's request may contribute browser matching data, never an admin's.
        if ($name === 'StoreConfigured' && request()->user()
            && (int) request()->user()->id === (int) $store->user_id && filled(request()->userAgent())) {
            $actionSource = 'website';
            $sourceUrl = request()->url();
            $userData = array_filter([...$userData,
                'client_ip_address' => request()->ip(), 'client_user_agent' => request()->userAgent(),
                'fbp' => request()->cookie('_fbp'), 'fbc' => request()->cookie('_fbc'),
            ], fn ($value) => filled($value));
        }

        return VendlyMeasurementEvent::firstOrCreate(['event_id' => $id], [
            'store_id' => $store->id,
            'source_store_id' => $store->id,
            'event_name' => $name,
            'action_source' => $actionSource,
            'event_source_url' => $sourceUrl,
            'custom_data' => ['measurement_version' => 2, ...$data],
            'store_payload' => MetaConversionsApiService::subscriptionPayloadForStore($store),
            'user_data' => $userData,
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }

    public function storeConfigured(Store $store): void
    {
        // A saved settings form or a completed wizard is not proof of activation.
        if (! $store->isAvailable() || $store->user?->isAdmin()
            || trim((string) $store->name) === '' || trim((string) $store->slug) === ''
            || ! preg_match('/^[1-9][0-9]{6,14}$/', preg_replace('/\D/', '', (string) $store->whatsapp))
            || ! $store->products()->where('price', '>', 0)
                ->whereNotNull('name')->whereRaw("TRIM(name) <> ''")
                ->whereNotNull('image')->whereRaw("TRIM(image) <> ''")->exists()) {
            return;
        }

        $this->capture($store, 'StoreConfigured', 'store-configured-v2-'.$store->id, [
            'activation_definition' => 'available_store_whatsapp_product_photo_price',
        ]);
    }
}
