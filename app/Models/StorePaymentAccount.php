<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorePaymentAccount extends Model
{
    public const PROVIDER_MERCADOPAGO = 'mercadopago';
    public const PROVIDER_WOMPI = 'wompi';

    public const STATUS_CONNECTED = 'connected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_DISCONNECTED = 'disconnected';

    public const MODE_SANDBOX = 'sandbox';
    public const MODE_PRODUCTION = 'production';

    protected $fillable = [
        'mode',
        'settings',
        'provider_user_id',
        'expires_at',
        'connected_at',
        'disconnected_at',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'public_key' => 'encrypted',
        'private_key' => 'encrypted',
        'events_secret' => 'encrypted',
        'integrity_secret' => 'encrypted',
        'settings' => 'array',
        'expires_at' => 'datetime',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
    ];

    public function isConnected(): bool
    {
        return $this->status === self::STATUS_CONNECTED
            && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function isWompiReady(): bool
    {
        return $this->provider === self::PROVIDER_WOMPI
            && $this->isConnected()
            && filled($this->public_key)
            && filled($this->private_key)
            && filled($this->events_secret)
            && filled($this->integrity_secret);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public static function updateWompiCredentials(Store $store, array $attributes): self
    {
        $account = self::firstOrNew([
            'store_id' => $store->id,
            'provider' => self::PROVIDER_WOMPI,
        ]);

        $account->forceFill([
            'store_id' => $store->id,
            'provider' => self::PROVIDER_WOMPI,
            'public_key' => $attributes['public_key'] ?? $account->public_key,
            'private_key' => $attributes['private_key'] ?? $account->private_key,
            'events_secret' => $attributes['events_secret'] ?? $account->events_secret,
            'integrity_secret' => $attributes['integrity_secret'] ?? $account->integrity_secret,
            'mode' => $attributes['mode'] ?? $account->mode,
            'connected_at' => $attributes['connected_at'] ?? $account->connected_at,
            'disconnected_at' => $attributes['disconnected_at'] ?? $account->disconnected_at,
            'status' => $attributes['status'] ?? $account->status,
        ])->save();

        return $account;
    }

    public static function updateMercadoPagoCredentials(Store $store, array $attributes): self
    {
        $account = self::firstOrNew([
            'store_id' => $store->id,
            'provider' => self::PROVIDER_MERCADOPAGO,
        ]);

        $account->forceFill([
            'store_id' => $store->id,
            'provider' => self::PROVIDER_MERCADOPAGO,
            'access_token' => $attributes['access_token'] ?? $account->access_token,
            'refresh_token' => $attributes['refresh_token'] ?? $account->refresh_token,
            'public_key' => $attributes['public_key'] ?? $account->public_key,
            'provider_user_id' => $attributes['provider_user_id'] ?? $account->provider_user_id,
            'expires_at' => $attributes['expires_at'] ?? $account->expires_at,
            'connected_at' => $attributes['connected_at'] ?? $account->connected_at,
            'disconnected_at' => $attributes['disconnected_at'] ?? $account->disconnected_at,
            'status' => $attributes['status'] ?? $account->status,
        ])->save();

        return $account;
    }

    public function setConnectionStatus(string $status, ?\DateTimeInterface $connectedAt = null, ?\DateTimeInterface $disconnectedAt = null): void
    {
        $this->forceFill([
            'status' => $status,
            'connected_at' => $connectedAt ?? $this->connected_at,
            'disconnected_at' => $disconnectedAt,
        ])->save();
    }
}
