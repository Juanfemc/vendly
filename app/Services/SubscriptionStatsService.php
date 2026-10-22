<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class SubscriptionStatsService
{
    public function summary(?array $dateRange = null): array
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

        $trialActive = $stores->filter(fn (Store $store) => $this->isPublishedStore($store) && $store->isTrialing() && $this->matchesSubscriptionPeriod($store, $dateRange));
        $trialExpired = $stores->filter(fn (Store $store) => $this->isPublishedStore($store) && $this->isTrialExpired($store) && $this->matchesSubscriptionPeriod($store, $dateRange));
        $paidActive = $stores->filter(fn (Store $store) => $this->isPaidActive($store) && $this->matchesSubscriptionPeriod($store, $dateRange));
        $paidExpired = $stores->filter(fn (Store $store) => $this->isPaidExpired($store) && $this->matchesSubscriptionPeriod($store, $dateRange));
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
            ...$this->verifiedReceipts($dateRange),
        ];
    }

    private function verifiedReceipts(?array $dateRange): array
    {
        $result = ['received_total' => 0, 'first_payments' => 0, 'renewal_payments' => 0, 'measured_activations' => 0];
        if (! Schema::hasTable('subscription_payments') || ! Schema::hasTable('vendly_measurement_events')) {
            return $result;
        }
        $payments = \App\Models\SubscriptionPayment::where('currency', 'COP');
        $activations = \App\Models\VendlyMeasurementEvent::where('event_name', 'StoreConfigured')
            ->where(fn ($query) => $query->whereNull('last_error')->orWhere('last_error', '!=', 'baseline_existing_store'));
        if ($dateRange) {
            $payments->whereBetween('paid_at', $dateRange);
            $activations->whereBetween('occurred_at', $dateRange);
        }

        return [
            'received_total' => (int) (clone $payments)->sum('amount'),
            'first_payments' => (clone $payments)->where('kind', 'first_payment')->count(),
            'renewal_payments' => (clone $payments)->where('kind', 'renewal')->count(),
            'measured_activations' => $activations->count(),
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
        $monthlyPrices = config('plans.prices', []);

        return (int) $paidActive->sum(fn (Store $store) => $monthlyPrices[$store->plan]['monthly'] ?? 0);
    }

    private function matchesSubscriptionPeriod(Store $store, ?array $dateRange): bool
    {
        if (! $dateRange) {
            return true;
        }

        [$periodStartsAt, $periodEndsAt] = $dateRange;
        $status = $store->subscriptionStatus();

        if ($status === Store::SUBSCRIPTION_EXPIRED || $this->isTrialExpired($store)) {
            return $this->dateWithinPeriod(
                $store->subscription_ends_at ?: $store->trial_ends_at ?: $store->created_at,
                $periodStartsAt,
                $periodEndsAt,
            );
        }

        $subscriptionStartsAt = $status === Store::SUBSCRIPTION_TRIALING
            ? ($store->trial_starts_at ?: $store->created_at)
            : $store->created_at;
        $subscriptionEndsAt = $status === Store::SUBSCRIPTION_TRIALING
            ? $store->trial_ends_at
            : $store->subscription_ends_at;

        return $this->periodsOverlap($subscriptionStartsAt, $subscriptionEndsAt, $periodStartsAt, $periodEndsAt);
    }

    private function periodsOverlap($startsAt, $endsAt, $periodStartsAt, $periodEndsAt): bool
    {
        if (! $startsAt) {
            return false;
        }

        $startsAt = $startsAt->copy()->startOfDay();
        $endsAt = $endsAt?->copy()->endOfDay();

        return $startsAt->lte($periodEndsAt) && (! $endsAt || $endsAt->gte($periodStartsAt));
    }

    private function dateWithinPeriod($date, $periodStartsAt, $periodEndsAt): bool
    {
        if (! $date) {
            return false;
        }

        return $date->betweenIncluded($periodStartsAt, $periodEndsAt);
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
