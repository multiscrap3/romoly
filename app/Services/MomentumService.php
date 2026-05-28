<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserGamification;

class MomentumService
{
    const DECAY_PER_INACTIVE_DAY = 2.0;
    const GRACE_DAYS_PER_WEEK    = 1;
    const MAX_SCORE              = 100.0;
    const MIN_SCORE              = 0.0;

    const ACTIVITY_GAINS = [
        'transaction_logged' => 2,
        'weekly_review'      => 5,
        'budget_compliance'  => 5,
        'saving_activity'    => 5,
    ];

    public function recordActivity(User $user, string $activityType): void
    {
        $gain = self::ACTIVITY_GAINS[$activityType] ?? 0;
        if ($gain <= 0) return;

        $g = $this->getOrCreate($user);
        $g->momentum_score      = min(self::MAX_SCORE, $g->momentum_score + $gain);
        $g->last_active_date    = today();
        $g->inactive_days_count = 0;
        $g->save();
    }

    public function applyDailyDecay(User $user): void
    {
        $g = UserGamification::where('user_id', $user->id)->first();
        if (!$g) return;
        if ($g->last_active_date?->isToday()) return;

        $this->refreshGracePeriodIfNeeded($g);

        if ($g->grace_days_used < self::GRACE_DAYS_PER_WEEK) {
            $g->grace_days_used++;
            $g->save();
            return;
        }

        $g->momentum_score      = max(self::MIN_SCORE, $g->momentum_score - self::DECAY_PER_INACTIVE_DAY);
        $g->inactive_days_count++;
        $g->save();
    }

    public function getStatus(float $score): string
    {
        return match (true) {
            $score >= 90 => 'Strong Momentum',
            $score >= 70 => 'Stable',
            $score >= 40 => 'Weakening',
            default      => 'Lost Focus',
        };
    }

    private function getOrCreate(User $user): UserGamification
    {
        return UserGamification::firstOrCreate(
            ['user_id' => $user->id],
            ['total_xp' => 0, 'level' => 1, 'momentum_score' => 50.0]
        );
    }

    private function refreshGracePeriodIfNeeded(UserGamification $g): void
    {
        $start = $g->grace_period_start ?? today()->startOfWeek();
        if (today()->diffInDays($start) >= 7) {
            $g->grace_period_start = today()->startOfWeek();
            $g->grace_days_used    = 0;
        }
    }
}
