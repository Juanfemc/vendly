<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Collection;

class SubscriptionStatsService
{
    private const MONTHLY_PLAN_PRICES = [
        Store::PLAN_PRO => 10000,
        Store::PLAN_PREMIUM => 25000,
    ];

    public function summary(): array
    {
        if (! Store::supportsSubscriptionColumns()) {
            return [
                'trial_active' => 0,
                'trial_ending_soon' => 0,
                'trial_expired' => 0,
                'paid_active' => 0,
                'paid_ending_soon' => 0,
                'paid_expired' => 0,
                'conversion_rate' => 0,
                'mrr' => 0,
                'attention_stores' => collect(),
            ];
        }

        $stores = Store::with('user')
            ->select([
                'id',
                'name',
                'user_id',
                'plan',
                'subscription_status',
                'trial_starts_at',
                'trial_ends_at',
                'subscription_ends_at',
                'whatsapp',
                'is_active',
                'created_at',
            ])
            ->latest()
            ->get();

        $trialActive = $stores->filter(fn (Store $store) => $this->isPublishedStore($store) && $store->isTrialing());
        $trialExpired = $stores->filter(fn (Store $store) => $this->isPublishedStore($store) && $this->isTrialExpired($store));
        $paidActive = $stores->filter(fn (Store $store) => $this->isPaidActive($store));
        $paidExpired = $stores->filter(fn (Store $store) => $this->isPaidExpired($store));
        $trialEndingSoon = $trialActive->filter(fn (Store $store) => $store->subscriptionEndsSoon(3));
        $paidEndingSoon = $paidActive->filter(fn (Store $store) => $store->subscriptionEndsSoon(3));

        $conversionBase = $trialActive->count() + $trialExpired->count() + $paidActive->count() + $paidExpired->count();
        $paidTotal = $paidActive->count() + $paidExpired->count();

        return [
            'trial_active' => $trialActive->count(),
            'trial_ending_soon' => $trialEndingSoon->count(),
            'trial_expired' => $trialExpired->count(),
            'paid_active' => $paidActive->count(),
            'paid_ending_soon' => $paidEndingSoon->count(),
            'paid_expired' => $paidExpired->count(),
            'conversion_rate' => $conversionBase > 0 ? round(($paidTotal / $conversionBase) * 100, 1) : 0,
            'mrr' => $this->monthlyRecurringRevenue($paidActive),
            'attention_stores' => $this->attentionStores($stores),
        ];
    }

    private function isPaidActive(Store $store): bool
    {
        return $this->isPublishedStore($store)
            && $this->isPaidPlan($store)
            && $store->subscriptionStatus() === Store::SUBSCRIPTION_ACTIVE
            && $store->hasActiveSubscription();
    }

    private function isPaidExpired(Store $store): bool
    {
        return $this->isPublishedStore($store)
            && $this->isPaidPlan($store)
            && $store->subscription_ends_at
            && $store->subscriptionExpired()
            && ! $this->isTrialExpired($store);
    }

    private function isTrialExpired(Store $store): bool
    {
        return $store->trial_ends_at
            && ! $store->subscription_ends_at
            && $store->trial_ends_at->copy()->endOfDay()->isPast()
            && in_array($store->subscriptionStatus(), [Store::SUBSCRIPTION_TRIALING, Store::SUBSCRIPTION_EXPIRED], true);
    }

    private function isPaidPlan(Store $store): bool
    {
        return in_array($store->plan, [Store::PLAN_PRO, Store::PLAN_PREMIUM], true);
    }

    private function isPublishedStore(Store $store): bool
    {
        return (bool) $store->is_active;
    }

    private function monthlyRecurringRevenue(Collection $paidActive): int
    {
        return (int) $paidActive->sum(fn (Store $store) => self::MONTHLY_PLAN_PRICES[$store->plan] ?? 0);
    }

    private function attentionStores(Collection $stores): Collection
    {
        return $stores
            ->filter(fn (Store $store) => $this->shouldShowAttentionStore($store))
            ->sortBy(function (Store $store) {
                $endsAt = $store->subscriptionStatus() === Store::SUBSCRIPTION_TRIALING
                    ? $store->trial_ends_at
                    : $store->subscription_ends_at;

                return $endsAt?->timestamp ?? PHP_INT_MAX;
            })
            ->take(10)
            ->values();
    }

    private function shouldShowAttentionStore(Store $store): bool
    {
        return $this->isPublishedStore($store)
            && ($store->isTrialing() || $this->isPaidPlan($store) || $this->isTrialExpired($store))
            && ($store->subscriptionExpired() || $store->subscriptionEndsSoon(3));
    }
}
