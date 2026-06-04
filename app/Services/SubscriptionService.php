<?php

namespace App\Services;

use App\Models\Household;
use App\Models\Plan;
use Carbon\Carbon;

class SubscriptionService
{
    /**
     * Aktifkan/perpanjang langganan household ke sebuah plan.
     * Jika masih aktif, perpanjang dari subscription_end lama (bukan dari hari ini).
     */
    public function activate(Household $household, Plan $plan, string $period): void
    {
        $months = (int) config("payment.periods.$period", 1);

        $base = ($household->subscription_end && $household->subscription_end->isFuture())
            ? $household->subscription_end->copy()
            : Carbon::now();

        $household->forceFill([
            'plan_id'            => $plan->id,
            'subscription_start' => Carbon::now()->toDateString(),
            'subscription_end'   => $base->addMonths($months)->toDateString(),
            'status'             => 'active',
        ])->save();
    }
}
