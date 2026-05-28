<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\Anggaran;
use App\Models\HutangPiutang;
use App\Models\Tabungan;
use App\Models\TabunganTransaksi;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\WeeklyReview;
use App\Models\XpLog;
use Illuminate\Support\Facades\DB;

class AchievementService
{
    public function __construct(
        private readonly XpService $xpService,
        private readonly NotifikasiService $notifikasiService,
    ) {}

    /**
     * Evaluate all unearned achievements for a user.
     * Returns slugs of newly awarded achievements.
     */
    public function evaluate(User $user): array
    {
        $earnedIds  = UserAchievement::where('user_id', $user->id)->pluck('achievement_id');
        $candidates = Achievement::whereNotIn('id', $earnedIds)->get();
        $awarded    = [];

        foreach ($candidates as $achievement) {
            if ($this->conditionMet($user, $achievement)) {
                $this->award($user, $achievement);
                $awarded[] = $achievement->slug;
            }
        }

        return $awarded;
    }

    private function award(User $user, Achievement $achievement): void
    {
        DB::transaction(function () use ($user, $achievement) {
            UserAchievement::create([
                'user_id'        => $user->id,
                'achievement_id' => $achievement->id,
                'earned_at'      => now(),
            ]);

            // Award XP sesuai tier type (awareness = kecil, financial = besar)
            $source = $achievement->tier_type === 'awareness'
                ? 'categorize'          // 5 XP proxy untuk awareness
                : 'budget_daily';       // 10 XP proxy untuk financial

            $this->xpService->award($user, $source, ['achievement_slug' => $achievement->slug]);

            $this->notifikasiService->send(
                $user->id,
                'Achievement Unlocked!',
                'Kamu mendapatkan: ' . $achievement->name,
                'achievement'
            );
        });
    }

    private function conditionMet(User $user, Achievement $achievement): bool
    {
        $val = $achievement->condition_value;

        return match ($achievement->condition_type) {
            'days_tracked_consecutive' => $this->checkConsecutiveDays($user, $val['days']),
            'days_in_month'            => $this->checkDaysInMonth($user, $val['days']),
            'weekly_reviews_completed' => $this->checkWeeklyReviews($user, $val['count']),
            'within_weekly_budget'     => $this->checkWeeklyBudget($user),
            'within_monthly_budget'    => $this->checkMonthlyBudget($user),
            'no_unplanned_expense'     => $this->checkNoImpulse($user, $val['days']),
            'saving_target_reached'    => $this->checkSavingTarget($user, $val['count']),
            'emergency_fund_started'   => $this->checkEmergencyFund($user, $val['min_amount']),
            'consistent_saving_months' => $this->checkConsistentSaving($user, $val['months']),
            'debt_reduced_percent'     => $this->checkDebtReduction($user, $val['percent']),
            'no_new_debt_months'       => $this->checkNoNewDebt($user, $val['months']),
            'all_debt_paid'            => $this->checkDebtFree($user),
            default                    => false,
        };
    }

    private function checkConsecutiveDays(User $user, int $days): bool
    {
        $dates = Transaksi::where('user_id', $user->id)
            ->where('tanggal', '>=', now()->subDays($days))
            ->selectRaw('DATE(tanggal) as day')
            ->distinct()
            ->pluck('day')
            ->map(fn($d) => \Carbon\Carbon::parse($d))
            ->sortByDesc(fn($d) => $d->timestamp)
            ->values();

        if ($dates->count() < $days) return false;

        for ($i = 0; $i < $days; $i++) {
            if (!isset($dates[$i])) return false;
            if ($dates[$i]->toDateString() !== now()->subDays($i)->toDateString()) return false;
        }
        return true;
    }

    private function checkDaysInMonth(User $user, int $days): bool
    {
        return XpLog::where('user_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->selectRaw('DATE(created_at) as day')
            ->distinct()
            ->count() >= $days;
    }

    private function checkWeeklyReviews(User $user, int $count): bool
    {
        return WeeklyReview::where('user_id', $user->id)
            ->whereNotNull('viewed_at')
            ->where('week_start', '>=', now()->subWeeks($count)->startOfWeek())
            ->count() >= $count;
    }

    private function checkWeeklyBudget(User $user): bool
    {
        $weekStart = now()->startOfWeek();
        $weekEnd   = now()->endOfWeek();
        $budgets   = Anggaran::where('household_id', $user->household_id)->get();

        foreach ($budgets as $budget) {
            $spent = Transaksi::where('household_id', $user->household_id)
                ->where('kategori_id', $budget->kategori_id)
                ->where('jenis', 'pengeluaran')
                ->whereBetween('tanggal', [$weekStart, $weekEnd])
                ->sum('jumlah');
            if ($spent > ($budget->jumlah / 4)) return false;
        }
        return true;
    }

    private function checkMonthlyBudget(User $user): bool
    {
        $budgets = Anggaran::where('household_id', $user->household_id)->get();

        foreach ($budgets as $budget) {
            $spent = Transaksi::where('household_id', $user->household_id)
                ->where('kategori_id', $budget->kategori_id)
                ->where('jenis', 'pengeluaran')
                ->whereMonth('tanggal', now()->month)
                ->whereYear('tanggal', now()->year)
                ->sum('jumlah');
            if ($spent > $budget->jumlah) return false;
        }
        return true;
    }

    private function checkNoImpulse(User $user, int $days): bool
    {
        return !Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->whereNull('recurring_id')
            ->where('tanggal', '>=', now()->subDays($days))
            ->exists();
    }

    private function checkSavingTarget(User $user, int $count): bool
    {
        return Tabungan::where('household_id', $user->household_id)
            ->where('status', 'selesai')
            ->count() >= $count;
    }

    private function checkEmergencyFund(User $user, int $minAmount): bool
    {
        return Tabungan::where('household_id', $user->household_id)
            ->whereRaw("LOWER(nama) LIKE '%darurat%'")
            ->where('terkumpul', '>=', $minAmount)
            ->exists();
    }

    private function checkConsistentSaving(User $user, int $months): bool
    {
        for ($i = 0; $i < $months; $i++) {
            $month = now()->subMonths($i);
            $hasSaving = TabunganTransaksi::where('user_id', $user->id)
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->exists();
            if (!$hasSaving) return false;
        }
        return true;
    }

    private function checkDebtReduction(User $user, int $percent): bool
    {
        $debt = HutangPiutang::where('household_id', $user->household_id)
            ->where('jenis', 'hutang')
            ->where('status', 'aktif')
            ->first();

        if (!$debt || $debt->jumlah_total <= 0) return false;

        return ($debt->jumlah_terbayar / $debt->jumlah_total * 100) >= $percent;
    }

    private function checkNoNewDebt(User $user, int $months): bool
    {
        return !HutangPiutang::where('household_id', $user->household_id)
            ->where('jenis', 'hutang')
            ->where('created_at', '>=', now()->subMonths($months))
            ->exists();
    }

    private function checkDebtFree(User $user): bool
    {
        return !HutangPiutang::where('household_id', $user->household_id)
            ->where('jenis', 'hutang')
            ->where('status', '!=', 'lunas')
            ->exists();
    }
}
