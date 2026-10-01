<?php

namespace App\Jobs;

use App\Models\Store;
use App\Services\MetaConversionsApiService;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendMetaConversionsEvent implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 20;

    public function __construct(
        public int $storeId,
        public string $eventName,
        public array $customData = [],
        public array $eventPayload = [],
        public array $userData = [],
        public ?string $eventId = null,
        public ?int $eventTime = null,
        public string $actionSource = 'system_generated',
    ) {
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(MetaConversionsApiService $metaConversions): void
    {
        $store = Store::with('user')->find($this->storeId);

        if (! $store) {
            $metaConversions->subscriptionEventSnapshot(
                $this->storeId,
                $this->eventName,
                $this->userData,
                $this->customData,
                $this->eventId,
                $this->eventTime,
                $this->eventPayload,
                true,
                $this->actionSource
            );

            return;
        }

        $metaConversions->subscriptionEvent(
            $store,
            $this->eventName,
            null,
            $this->customData,
            $this->eventId,
            $this->eventTime,
            $this->eventPayload,
            $this->userData,
            true,
            $this->actionSource
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('No se pudo completar el job de Meta Conversions API.', [
            'store_id' => $this->storeId,
            'event_name' => $this->eventName,
            'event_id' => $this->eventId,
            'exception' => $exception::class,
        ]);
    }
}
