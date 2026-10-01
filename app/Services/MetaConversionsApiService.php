<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class MetaConversionsApiService
{
    public function subscriptionEvent(
        Store $store,
        string $eventName,
        ?Request $request = null,
        array $customData = [],
        ?string $eventId = null,
        ?int $eventTime = null,
        array $eventPayload = [],
        array $userData = [],
        bool $throwOnFailure = false,
        string $actionSource = 'system_generated',
    ): void {
        $this->subscriptionEventSnapshot(
            (int) $store->id,
            $eventName,
            $userData !== [] ? $userData : self::userDataForStore($store),
            $customData,
            $eventId ?? self::subscriptionEventId($store, $eventName, $customData),
            $eventTime,
            $eventPayload !== [] ? $eventPayload : self::subscriptionPayloadForStore($store),
            $throwOnFailure,
            $actionSource
        );
    }

    public function subscriptionEventSnapshot(
        int $storeId,
        string $eventName,
        array $userData,
        array $customData = [],
        ?string $eventId = null,
        ?int $eventTime = null,
        array $eventPayload = [],
        bool $throwOnFailure = false,
        string $actionSource = 'system_generated',
    ): void {
        $pixelId = trim((string) config('services.meta.landing_pixel_id'));
        $accessToken = trim((string) config('services.meta.conversions_access_token'));

        if ($pixelId === '' || $accessToken === '' || ! preg_match('/^[0-9]+$/', $pixelId)) {
            return;
        }

        if (! preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $eventName)) {
            return;
        }

        $eventPayload = array_filter($eventPayload, fn ($value) => $value !== null && $value !== '');
        $eventPlan = (string) ($eventPayload['plan'] ?? '');

        if ($eventPlan === '') {
            return;
        }

        if (! in_array($eventPlan, [Store::PLAN_PRO, Store::PLAN_PREMIUM], true)) {
            return;
        }

        $normalizedUserData = $this->normalizedUserData($userData);

        if ($normalizedUserData === []) {
            if ($throwOnFailure) {
                throw new RuntimeException('Meta Conversions API no tiene datos de usuario para enviar el evento.');
            }

            return;
        }

        $eventId ??= self::snapshotEventId($storeId, $eventName, $eventPlan, $eventPayload['subscription_ends_at'] ?? null, $customData);

        $event = [
            'event_name' => $eventName,
            'event_time' => $eventTime ?: now()->timestamp,
            'event_id' => $eventId,
            'action_source' => $this->normalizeActionSource($actionSource),
            'user_data' => $normalizedUserData,
            'custom_data' => array_filter(array_merge([
                'plan' => $eventPlan,
                'business_type' => (string) ($eventPayload['business_type'] ?? ''),
                'subscription_status' => (string) ($eventPayload['subscription_status'] ?? ''),
                'subscription_ends_at' => $eventPayload['subscription_ends_at'] ?? null,
            ], $customData), fn ($value) => $value !== null && $value !== ''),
        ];

        $payload = [
            'data' => [$event],
            'access_token' => $accessToken,
        ];

        $testEventCode = trim((string) config('services.meta.test_event_code'));

        if ($testEventCode !== '') {
            $payload['test_event_code'] = $testEventCode;
        }

        try {
            $version = $this->graphVersion();
            Http::timeout(6)
                ->asJson()
                ->post("https://graph.facebook.com/{$version}/{$pixelId}/events", $payload)
                ->throw();
        } catch (RequestException $exception) {
            $status = $exception->response?->status();

            Log::warning('Meta Conversions API rechazo el evento.', [
                'store_id' => $storeId,
                'event_name' => $eventName,
                'event_id' => $eventId,
                'status' => $status,
                'exception' => $exception::class,
            ]);

            if ($throwOnFailure && ($status === null || $status === 429 || $status >= 500)) {
                throw $exception;
            }
        } catch (Throwable $exception) {
            Log::warning('No se pudo enviar evento a Meta Conversions API.', [
                'store_id' => $storeId,
                'event_name' => $eventName,
                'event_id' => $eventId,
                'exception' => $exception::class,
            ]);

            if ($throwOnFailure) {
                throw $exception;
            }
        }
    }

    public static function subscriptionEventId(Store $store, string $eventName, array $customData = []): string
    {
        return self::snapshotEventId(
            (int) $store->id,
            $eventName,
            (string) $store->plan,
            optional($store->subscription_ends_at)->timestamp ?: 'no-end',
            $customData
        );
    }

    public static function subscriptionPayloadForStore(Store $store): array
    {
        return [
            'plan' => (string) $store->plan,
            'business_type' => (string) $store->business_type,
            'subscription_status' => $store->subscriptionStatus(),
            'subscription_ends_at' => optional($store->subscription_ends_at)->toDateString(),
        ];
    }

    public static function userDataForStore(Store $store): array
    {
        $store->loadMissing('user');
        $email = trim((string) $store->user?->email);
        $phone = preg_replace('/\D+/', '', (string) $store->whatsapp);

        return array_filter([
            'em' => $email !== '' ? hash('sha256', mb_strtolower($email)) : null,
            'ph' => $phone !== '' ? hash('sha256', $phone) : null,
            'external_id' => hash('sha256', 'store:' . $store->id),
        ]);
    }

    private function normalizedUserData(array $userData): array
    {
        $allowedKeys = ['em', 'ph', 'external_id', 'client_ip_address', 'client_user_agent', 'fbp', 'fbc'];
        $userData = array_map(
            fn ($value) => is_string($value) ? trim($value) : $value,
            array_intersect_key($userData, array_flip($allowedKeys))
        );

        foreach (['em', 'ph', 'external_id'] as $hashedKey) {
            if (isset($userData[$hashedKey])) {
                $userData[$hashedKey] = mb_strtolower((string) $userData[$hashedKey]);
            }

            if (isset($userData[$hashedKey]) && ! preg_match('/^[a-f0-9]{64}$/', $userData[$hashedKey])) {
                unset($userData[$hashedKey]);
            }
        }

        if (isset($userData['client_ip_address']) && ! filter_var($userData['client_ip_address'], FILTER_VALIDATE_IP)) {
            unset($userData['client_ip_address']);
        }

        foreach (['client_user_agent', 'fbp', 'fbc'] as $limitedKey) {
            if (isset($userData[$limitedKey])) {
                $userData[$limitedKey] = mb_substr((string) $userData[$limitedKey], 0, 500);
            }
        }

        return array_filter($userData, fn ($value) => is_string($value) && $value !== '');
    }

    private function graphVersion(): string
    {
        $version = trim((string) config('services.meta.graph_version', 'v24.0'));

        return preg_match('/^v[0-9]+\.[0-9]+$/', $version) ? $version : 'v24.0';
    }

    private function normalizeActionSource(string $actionSource): string
    {
        $actionSource = trim($actionSource);
        $allowed = [
            'business_messaging',
            'chat',
            'email',
            'other',
            'phone_call',
            'physical_store',
            'system_generated',
            'website',
        ];

        return in_array($actionSource, $allowed, true) ? $actionSource : 'system_generated';
    }

    private static function snapshotEventId(int $storeId, string $eventName, string $plan, mixed $subscriptionEndsAt, array $customData): string
    {
        return implode('-', array_filter([
            'vendly',
            $eventName,
            $storeId,
            $plan,
            $subscriptionEndsAt ?: 'no-end',
            substr(sha1(json_encode($customData)), 0, 12),
        ]));
    }
}
