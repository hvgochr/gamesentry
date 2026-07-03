<?php

namespace App\Services\Billing;

use App\Enums\Plan;
use App\Models\User;

class SubscriptionPlanService
{
    public function sync(User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $user->refresh();

        $user->forceFill([
            'plan' => $this->hasActiveProSubscription($user) ? Plan::Pro : Plan::Free,
        ])->save();
    }

    public function hasActiveProSubscription(User $user): bool
    {
        $priceId = config('services.stripe.pro_price_id');

        if (is_string($priceId) && $priceId !== '') {
            return $user->subscribedToPrice($priceId, 'default');
        }

        return $user->subscribed('default');
    }
}
