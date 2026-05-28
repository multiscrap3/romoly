<?php

namespace App\Services;

use App\Models\Challenge;
use App\Models\TabunganTransaksi;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\UserChallenge;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ChallengeService
{
    const MAX_ACTIVE_WEEKLY  = 2;
    const MAX_ACTIVE_MONTHLY = 1;

    public function __construct(
        private readonly XpService $xpService,
        private readonly MomentumService $momentumService,
        private readonly NotifikasiService $notifikasiService,
    ) {}

    /**
     * Assign unattempted challenges to a user for the current period.
     */
    public function assignForUser(User $user): void
    {
        $this->assignByType($user, 'weekly', self::MAX_ACTIVE_WEEKLY, now()->endOfWeek());
        $this->assignByType($user, 'monthly', self::MAX_ACTIVE_MONTHLY, now()->endOfMonth());
    }

    /**
     * Check active challenges and complete those whose conditions are met.
     * Returns slugs of completed challenges.
     */
    public function evaluateActive(User $user): array
    {
        $active = UserChallenge::where('user_id', $user->id)
            ->where('expires_at', '>=', now())
            ->whereNull('completed_at')
            ->with('challenge')
            ->get();

        $completed = [];

        foreach ($active as $userChallenge) {
            if ($this->conditionMet($user, $userChallenge->challenge)) {
                $this->complete($user, $userChallenge);
                $completed[] = $userChallenge->challenge->slug;
            }
        }

        return $completed;
    }

    private function assignByType(User $user, string $type, int $max, Carbon $expiresAt): void
    {
        $activeCount = UserChallenge::where('user_id', $user->id)
            ->where('expires_at', '>=', now())
            ->whereNull('completed_at')
            ->whereHas('challenge', fn($q) => $q->where('type', $type))
            ->count();

        if ($activeCount >= $max) return;

        $attempted = UserChallenge::where('user_id', $user->id)
            ->whereHas('challenge', fn($q) => $q->where('type', $type))
            ->pluck('challenge_id');

        $candidates = Challenge::where('type', $type)
            ->whereNotIn('id', $attempted)
            ->inRandomOrder()
            ->take($max - $activeCount)
            ->get();

        foreach ($candidates as $challenge) {
            UserChallenge::create([
                'user_id'      => $user->id,
                'challenge_id' => $challenge->id,
                'started_at'   => now(),
                'expires_at'   => $expiresAt,
            ]);
        }
    }

    private function complete(User $user, UserChallenge $userChallenge): void
    {
        DB::transaction(function () use ($user, $userChallenge) {
            $userChallenge->completed_at = now();
            $userChallenge->save();

            $challenge = $userChallenge->challenge;

            $this->xpService->award($user, 'budget_weekly', ['challenge_id' => $challenge->id]);
            $this->momentumService->recordActivity($user, 'budget_compliance');

            $this->notifikasiService->send(
                $user->id,
                'Tantangan Selesai!',
                'Kamu berhasil menyelesaikan: ' . $challenge->title,
                'achievement'
            );
        });
    }

    private function conditionMet(User $user, Challenge $challenge): bool
    {
        $val = $challenge->condition_value;

        return match ($challenge->condition_type) {
            'no_food_delivery_days'       => $this->checkNoFoodDelivery($user, $val['days']),
            'daily_transaction_logged'    => $this->checkDailyLogging($user, $val['days']),
            'category_budget_limit'       => $this->checkCategoryLimit($user, $val['category'], $val['limit']),
            'saving_ratio'                => $this->checkSavingRatio($user, $val['percent']),
            'no_budget_exceeded'          => $this->checkNoBudgetExceeded($user),
            'emergency_fund_contribution' => $this->checkEmergencyContribution($user, $val['min_amount']),
            default                       => false,
        };
    }

    private function checkNoFoodDelivery(User $user, int $days): bool
    {
        return !Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->where('tanggal', '>=', now()->subDays($days))
            ->whereHas('kategori', fn($q) => $q->whereRaw("LOWER(nama) LIKE '%delivery%' OR LOWER(nama) LIKE '%ojek%'"))
            ->exists();
    }

    private function checkDailyLogging(User $user, int $days): bool
    {
        $logged = Transaksi::where('user_id', $user->id)
            ->where('tanggal', '>=', now()->subDays($days))
            ->selectRaw('DATE(tanggal) as day')
            ->distinct()
            ->count();
        return $logged >= $days;
    }

    private function checkCategoryLimit(User $user, string $categoryName, int $limit): bool
    {
        $spent = Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->whereHas('kategori', fn($q) => $q->whereRaw('LOWER(nama) LIKE ?', ['%' . strtolower($categoryName) . '%']))
            ->whereBetween('tanggal', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('jumlah');
        return $spent <= $limit;
    }

    private function checkSavingRatio(User $user, int $percent): bool
    {
        $income = Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pemasukan')
            ->whereMonth('tanggal', now()->month)
            ->sum('jumlah');

        if ($income <= 0) return false;

        $saved = TabunganTransaksi::where('user_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->sum('jumlah');

        return ($saved / $income * 100) >= $percent;
    }

    private function checkNoBudgetExceeded(User $user): bool
    {
        $budgets = \App\Models\Anggaran::where('household_id', $user->household_id)->get();

        foreach ($budgets as $budget) {
            $spent = Transaksi::where('household_id', $user->household_id)
                ->where('kategori_id', $budget->kategori_id)
                ->where('jenis', 'pengeluaran')
                ->whereMonth('tanggal', now()->month)
                ->sum('jumlah');
            if ($spent > $budget->jumlah) return false;
        }
        return true;
    }

    private function checkEmergencyContribution(User $user, int $minAmount): bool
    {
        return TabunganTransaksi::where('user_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereHas('tabungan', fn($q) => $q->whereRaw("LOWER(nama) LIKE '%darurat%'"))
            ->sum('jumlah') >= $minAmount;
    }
}
