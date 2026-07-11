<?php

namespace App\Services;

use App\Models\DiscountCoupon;
use App\Models\Store;
use Illuminate\Validation\ValidationException;

class DiscountCouponService
{
    public function preview(Store $store, ?string $code, float $subtotal): array
    {
        $code = DiscountCoupon::normalizeCode($code);

        if ($code === '') {
            return $this->emptyDiscount();
        }

        $coupon = $this->findValidCoupon($store, $code, $subtotal);

        return $this->couponPayload($coupon, $subtotal);
    }

    public function redeem(Store $store, ?string $code, float $subtotal): array
    {
        $code = DiscountCoupon::normalizeCode($code);

        if ($code === '') {
            return $this->emptyDiscount();
        }

        $coupon = $this->findValidCoupon($store, $code, $subtotal, lock: true);
        $payload = $this->couponPayload($coupon, $subtotal);

        $coupon->increment('used_count');

        return $payload;
    }

    private function findValidCoupon(Store $store, string $code, float $subtotal, bool $lock = false): DiscountCoupon
    {
        if (! $store->allowsDiscountCoupons()) {
            throw ValidationException::withMessages([
                'discount_code' => 'Los cupones estan disponibles solo en el plan Premium.',
            ]);
        }

        if (! DiscountCoupon::supportsTable()) {
            throw ValidationException::withMessages([
                'discount_code' => 'Los cupones todavia no estan disponibles.',
            ]);
        }

        $query = $store->discountCoupons()->where('code', $code);

        if ($lock) {
            $query->lockForUpdate();
        }

        $coupon = $query->first();

        if (! $coupon || ! $coupon->is_active) {
            throw ValidationException::withMessages([
                'discount_code' => 'El cupon no existe o no esta activo.',
            ]);
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            throw ValidationException::withMessages([
                'discount_code' => 'Este cupon aun no esta disponible.',
            ]);
        }

        if ($coupon->expires_at && $coupon->expires_at->endOfDay()->isPast()) {
            throw ValidationException::withMessages([
                'discount_code' => 'Este cupon ya vencio.',
            ]);
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            throw ValidationException::withMessages([
                'discount_code' => 'Este cupon ya alcanzo su limite de uso.',
            ]);
        }

        if ($subtotal < (float) $coupon->min_subtotal) {
            throw ValidationException::withMessages([
                'discount_code' => 'Este cupon requiere una compra minima de $ ' . number_format((float) $coupon->min_subtotal, 0, ',', '.'),
            ]);
        }

        return $coupon;
    }

    private function couponPayload(DiscountCoupon $coupon, float $subtotal): array
    {
        $amount = $this->discountAmount($coupon, $subtotal);

        return [
            'coupon' => $coupon,
            'code' => $coupon->code,
            'amount' => $amount,
            'snapshot' => $coupon->formattedValue() . ' - compra minima $ ' . number_format((float) $coupon->min_subtotal, 0, ',', '.'),
        ];
    }

    private function discountAmount(DiscountCoupon $coupon, float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0;
        }

        $amount = $coupon->type === DiscountCoupon::TYPE_PERCENT
            ? $subtotal * ((float) $coupon->value / 100)
            : (float) $coupon->value;

        if ($coupon->max_discount_amount !== null && (float) $coupon->max_discount_amount > 0) {
            $amount = min($amount, (float) $coupon->max_discount_amount);
        }

        return round(min(max(0, $amount), $subtotal), 2);
    }

    private function emptyDiscount(): array
    {
        return [
            'coupon' => null,
            'code' => null,
            'amount' => 0,
            'snapshot' => null,
        ];
    }
}
