<?php

namespace App\Services;

use App\Models\Anggaran;
use App\Models\Tabungan;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\UserGamification;
use App\Models\WeeklyReview;
use App\Models\XpLog;
use Carbon\Carbon;

class WeeklyReviewService
{
    public function __construct(
        private readonly MomentumService $momentumService,
        private readonly GamificationInsightService $insightService,
        private readonly XpService $xpService,
    ) {}

    public function generateForUser(User $user, Carbon $weekStart): WeeklyReview
    {
        $weekEnd = $weekStart->copy()->endOfWeek();

        $data = [
            'spending_comparison'    => $this->spendingComparison($user, $weekStart, $weekEnd),
            'budget_status'          => $this->budgetStatus($user, $weekStart, $weekEnd),
            'saving_progress'        => $this->savingProgress($user),
            'top_spending_category'  => $this->topSpendingCategory($user, $weekStart, $weekEnd),
            'unusual_spending'       => $this->insightService->generateForUser($user),
            'momentum_trend'         => $this->momentumTrend($user),
            'xp_gained_this_week'    => $this->xpGainedThisWeek($user, $weekStart, $weekEnd),
            'achievements_this_week' => $this->achievementsThisWeek($user, $weekStart, $weekEnd),
        ];

        return WeeklyReview::updateOrCreate(
            ['user_id' => $user->id, 'week_start' => $weekStart->toDateString()],
            ['week_end' => $weekEnd->toDateString(), 'data' => $data]
        );
    }

    public function markViewed(WeeklyReview $review, User $user): void
    {
        $isFirstView = $review->viewed_at === null;
        $review->markViewed();

        if ($isFirstView) {
            $this->xpService->award($user, 'weekly_summary_viewed');
            $this->momentumService->recordActivity($user, 'weekly_review');
        }
    }

    private function spendingComparison(User $user, Carbon $start, Carbon $end): array
    {
        $thisWeek = Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->whereBetween('tanggal', [$start, $end])
            ->sum('jumlah');

        $lastWeek = Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->whereBetween('tanggal', [$start->copy()->subWeek(), $end->copy()->subWeek()])
            ->sum('jumlah');

        $diff = $lastWeek > 0 ? round(($thisWeek - $lastWeek) / $lastWeek * 100, 1) : 0;

        return [
            'this_week'    => $thisWeek,
            'last_week'    => $lastWeek,
            'diff_percent' => $diff,
            'improved'     => $diff <= 0,
        ];
    }

    private function budgetStatus(User $user, Carbon $start, Carbon $end): array
    {
        $budgets = Anggaran::where('household_id', $user->household_id)->with('kategori')->get();
        $result  = [];

        foreach ($budgets as $budget) {
            $spent = Transaksi::where('household_id', $user->household_id)
                ->where('kategori_id', $budget->kategori_id)
                ->where('jenis', 'pengeluaran')
                ->whereBetween('tanggal', [$start, $end])
                ->sum('jumlah');

            $weeklyAllocation = $budget->jumlah / 4;
            $result[] = [
                'kategori'    => $budget->kategori->nama ?? '-',
                'allocated'   => $weeklyAllocation,
                'spent'       => $spent,
                'over_budget' => $spent > $weeklyAllocation,
            ];
        }

        return $result;
    }

    private function savingProgress(User $user): array
    {
        return Tabungan::where('household_id', $user->household_id)
            ->select(['nama', 'target_jumlah', 'terkumpul'])
            ->get()
            ->map(fn($t) => [
                'nama'    => $t->nama,
                'target'  => $t->target_jumlah,
                'saved'   => $t->terkumpul,
                'percent' => $t->target_jumlah > 0
                    ? round($t->terkumpul / $t->target_jumlah * 100, 1)
                    : 0,
            ])
            ->toArray();
    }

    private function topSpendingCategory(User $user, Carbon $start, Carbon $end): array
    {
        return Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->whereBetween('tanggal', [$start, $end])
            ->selectRaw('kategori_id, SUM(jumlah) as total')
            ->with('kategori:id,nama')
            ->groupBy('kategori_id')
            ->orderByDesc('total')
            ->take(3)
            ->get()
            ->map(fn($t) => [
                'kategori' => $t->kategori->nama ?? '-',
                'total'    => $t->total,
            ])
            ->toArray();
    }

    private function momentumTrend(User $user): array
    {
        $g = UserGamification::where('user_id', $user->id)->first();
        return [
            'score'  => $g?->momentum_score ?? 50,
            'status' => $this->momentumService->getStatus($g?->momentum_score ?? 50),
        ];
    }

    private function xpGainedThisWeek(User $user, Carbon $start, Carbon $end): int
    {
        return XpLog::where('user_id', $user->id)
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->sum('xp_amount');
    }

    private function achievementsThisWeek(User $user, Carbon $start, Carbon $end): array
    {
        return UserAchievement::where('user_id', $user->id)
            ->whereBetween('earned_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->with('achievement:id,name,category')
            ->get()
            ->map(fn($ua) => $ua->achievement->name)
            ->toArray();
    }
}
