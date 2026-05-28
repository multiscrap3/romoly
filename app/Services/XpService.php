<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserGamification;
use App\Models\XpLog;
use Illuminate\Support\Facades\DB;

class XpService
{
    const DAILY_CAP_TRANSACTION = 20;

    const XP_AMOUNTS = [
        'transaction'              => 2,
        'daily_review'             => 5,
        'categorize'               => 5,
        'weekly_summary_viewed'    => 10,
        'complete_weekly_tracking' => 20,
        'budget_daily'             => 10,
        'budget_weekly'            => 30,
        'consistency_7day'         => 30,
        'monthly_saving_reached'   => 100,
        'no_overspend_14days'      => 120,
        'expense_reduced'          => 150,
        'emergency_fund_milestone' => 300,
        'debt_fully_paid'          => 500,
    ];

    public function __construct(
        private readonly LevelService $levelService,
        private readonly NotifikasiService $notifikasiService,
    ) {}

    public function award(User $user, string $source, array $metadata = []): int
    {
        $xpAmount = self::XP_AMOUNTS[$source] ?? 0;
        if ($xpAmount <= 0) return 0;
        if ($this->isCapReached($user, $source)) return 0;

        return DB::transaction(function () use ($user, $source, $xpAmount, $metadata) {
            XpLog::create([
                'user_id'   => $user->id,
                'source'    => $source,
                'xp_amount' => $xpAmount,
                'metadata'  => $metadata ?: null,
            ]);

            $gamification = UserGamification::firstOrCreate(
                ['user_id' => $user->id],
                ['total_xp' => 0, 'level' => 1, 'momentum_score' => 50.0]
            );

            $oldLevel              = $gamification->level;
            $gamification->total_xp += $xpAmount;
            $newLevel              = LevelService::levelFromXp($gamification->total_xp);
            $gamification->level   = $newLevel;
            $gamification->save();

            if ($newLevel > $oldLevel) {
                $this->notifikasiService->send(
                    $user->id,
                    'Level Up!',
                    'Kamu naik ke level ' . $newLevel . ': ' . LevelService::title($newLevel),
                    'achievement'
                );
            }

            return $xpAmount;
        });
    }

    private function isCapReached(User $user, string $source): bool
    {
        if ($source !== 'transaction') return false;

        $earned = XpLog::where('user_id', $user->id)
            ->where('source', 'transaction')
            ->whereDate('created_at', today())
            ->sum('xp_amount');

        return $earned >= self::DAILY_CAP_TRANSACTION;
    }
}
