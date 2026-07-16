<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DiscountCoupon extends Model
{
    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED = 'fixed';
    public const APPLIES_TO_PRODUCTS = 'products';
    public const APPLIES_TO_ORDER_WITH_SHIPPING = 'order_with_shipping';
    public const APPLIES_TO_SHIPPING = 'shipping';

    private static ?bool $supportsAppliesToColumn = null;

    protected $fillable = [
        'store_id',
        'code',
        'type',
        'applies_to',
        'value',
        'min_subtotal',
        'max_discount_amount',
        'usage_limit',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_subtotal' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public static function supportsTable(): bool
    {
        return Schema::hasTable('discount_coupons');
    }

    public static function supportsAppliesToColumn(): bool
    {
        return self::$supportsAppliesToColumn ??= self::supportsTable()
            && Schema::hasColumn('discount_coupons', 'applies_to');
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_PERCENT => 'Porcentaje',
            self::TYPE_FIXED => 'Valor fijo',
        ];
    }

    public static function appliesToOptions(): array
    {
        return [
            self::APPLIES_TO_PRODUCTS => 'Solo productos',
            self::APPLIES_TO_ORDER_WITH_SHIPPING => 'Productos y envio',
            self::APPLIES_TO_SHIPPING => 'Solo envio',
        ];
    }

    public static function normalizeCode(?string $code): string
    {
        return Str::upper(trim((string) $code));
    }

    public function typeLabel(): string
    {
        return self::typeOptions()[$this->type] ?? 'Descuento';
    }

    public function appliesTo(): string
    {
        if (! self::supportsAppliesToColumn()) {
            return self::APPLIES_TO_PRODUCTS;
        }

        return $this->applies_to ?: self::APPLIES_TO_PRODUCTS;
    }

    public function appliesToLabel(): string
    {
        return self::appliesToOptions()[$this->appliesTo()] ?? self::appliesToOptions()[self::APPLIES_TO_PRODUCTS];
    }

    public function statusLabel(): string
    {
        if (! $this->is_active) {
            return 'Inactivo';
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'Programado';
        }

        if ($this->expires_at && $this->expires_at->endOfDay()->isPast()) {
            return 'Vencido';
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return 'Agotado';
        }

        return 'Activo';
    }

    public function statusBadgeClass(): string
    {
        return $this->statusLabel() === 'Activo' ? 'resource-badge--success' : 'resource-badge--warning';
    }

    public function formattedValue(): string
    {
        if ($this->type === self::TYPE_PERCENT) {
            return rtrim(rtrim(number_format((float) $this->value, 2, ',', '.'), '0'), ',') . '%';
        }

        return '$ ' . number_format((float) $this->value, 0, ',', '.');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'discount_coupon_id');
    }
}
