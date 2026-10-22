<?php

namespace App\Services;

use App\Models\VendlyMeasurementEvent;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class DeliverVendlyMeasurement
{
    public function send(int $id): void
    {
        // A durable outbox and row lock keep scheduler retries on the same event ID.
        DB::transaction(function () use ($id) {
            $event = VendlyMeasurementEvent::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($event->sent_at || $event->discarded_at || ($event->next_attempt_at && $event->next_attempt_at->isFuture())) {
                return;
            }
            // Keep historical receipts locally; never change their date to look like new acquisitions.
            if ($event->occurred_at->lt(now()->subDays(6))) {
                $event->update(['discarded_at' => now(), 'last_error' => 'Histórico: fuera de la ventana conservadora de envío de 6 días.']);

                return;
            }

            try {
                if (! preg_match('/^[0-9]+$/', trim((string) config('services.meta.landing_pixel_id')))
                    || ! filled(config('services.meta.conversions_access_token')) || $event->user_data === []) {
                    throw new RuntimeException('missing_configuration');
                }
                if (filled(config('services.meta.test_event_code'))) {
                    throw new RuntimeException('test_event_code_enabled');
                }
                app(MetaConversionsApiService::class)->subscriptionEventSnapshot(
                    $event->source_store_id, $event->event_name, $event->user_data,
                    $event->custom_data, $event->event_id, $event->occurred_at->timestamp,
                    $event->store_payload, true, $event->action_source, $event->event_source_url,
                );
                $event->update(['sent_at' => now(), 'last_error' => null, 'next_attempt_at' => null,
                    'attempts' => $event->attempts + 1]);
            } catch (Throwable $exception) {
                // Avoid persisting API response bodies, access tokens or customer matching data.
                $reason = $exception instanceof RuntimeException && in_array($exception->getMessage(), [
                    'missing_configuration', 'test_event_code_enabled',
                ], true) ? $exception->getMessage() : class_basename($exception);
                $event->update(['last_error' => $reason, 'attempts' => $event->attempts + 1,
                    'next_attempt_at' => now()->addMinutes(min(60, 5 * max(1, $event->attempts + 1)))]);
            }
        });
    }
}
