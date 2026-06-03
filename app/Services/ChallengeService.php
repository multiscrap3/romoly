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

    /**
     * Hitung progress numerik sebuah challenge untuk ditampilkan di UI (progress bar).
     *
     * @return array{current: float, target: float, percent: float, display: string, mode: string, cta_label: string, cta_route: string}
     */
    public function progressFor(User $user, UserChallenge $userChallenge): array
    {
        $challenge = $userChallenge->challenge;
        $val       = $challenge->condition_value ?? [];

        // default: tantangan berbasis transaksi
        $ctaLabel = 'Catat Transaksi';
        $ctaRoute = 'transaksi.create';
        $mode     = 'accumulate'; // makin tinggi makin baik

        [$current, $target, $display] = match ($challenge->condition_type) {
            'daily_transaction_logged'    => $this->progressDailyLogging($user, (int) ($val['days'] ?? 1)),
            'no_food_delivery_days'       => $this->progressNoFoodDelivery($user, $userChallenge, (int) ($val['days'] ?? 1)),
            'saving_ratio'                => $this->progressSavingRatio($user, (int) ($val['percent'] ?? 1)),
            'emergency_fund_contribution' => $this->progressEmergency($user, (int) ($val['min_amount'] ?? 1)),
            'no_budget_exceeded'          => $this->progressNoBudgetExceeded($user),
            'category_budget_limit'       => $this->progressCategoryLimit($user, (string) ($val['category'] ?? ''), (int) ($val['limit'] ?? 1)),
            default                       => [0.0, 1.0, ''],
        };

        // mode "limit": angka rendah lebih baik (pengeluaran di bawah batas)
        if ($challenge->condition_type === 'category_budget_limit') {
            $mode = 'limit';
        }

        if (in_array($challenge->condition_type, ['saving_ratio', 'emergency_fund_contribution'], true)) {
            $ctaLabel = 'Setor Tabungan';
            $ctaRoute = 'tabungan.index';
        }
        if (in_array($challenge->condition_type, ['no_budget_exceeded', 'category_budget_limit'], true)) {
            $ctaLabel = 'Lihat Anggaran';
            $ctaRoute = 'anggaran.index';
        }

        $target  = max(0.0001, (float) $target);
        $current = (float) $current;

        // Untuk mode "limit" bar mewakili pemakaian budget; untuk mode lain bar = progress.
        $percent = min(100.0, round($current / $target * 100, 1));

        return [
            'current'   => $current,
            'target'    => $target,
            'percent'   => $percent,
            'display'   => $display,
            'mode'      => $mode,
            'cta_label' => $ctaLabel,
            'cta_route' => $ctaRoute,
        ];
    }

    private function progressDailyLogging(User $user, int $days): array
    {
        $logged = Transaksi::where('user_id', $user->id)
            ->where('tanggal', '>=', now()->subDays($days))
            ->selectRaw('DATE(tanggal) as day')
            ->distinct()
            ->count();
        $logged = min($logged, $days);
        return [$logged, $days, "{$logged} / {$days} hari tercatat"];
    }

    private function progressNoFoodDelivery(User $user, UserChallenge $uc, int $days): array
    {
        $hasDelivery = Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->where('tanggal', '>=', now()->subDays($days))
            ->whereHas('kategori', fn($q) => $q->whereRaw("LOWER(nama) LIKE '%delivery%' OR LOWER(nama) LIKE '%ojek%'"))
            ->exists();

        $elapsed = min($days, max(0, (int) $uc->started_at->diffInDays(now())));
        $clean   = $hasDelivery ? 0 : $elapsed;
        return [$clean, $days, "{$clean} / {$days} hari bebas delivery"];
    }

    private function progressSavingRatio(User $user, int $percent): array
    {
        $income = Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pemasukan')
            ->whereMonth('tanggal', now()->month)
            ->sum('jumlah');

        $saved = TabunganTransaksi::where('user_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->sum('jumlah');

        $ratio = $income > 0 ? round($saved / $income * 100, 1) : 0;
        return [$ratio, $percent, "{$ratio}% / {$percent}% dari pemasukan"];
    }

    private function progressEmergency(User $user, int $minAmount): array
    {
        $saved = (float) TabunganTransaksi::where('user_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereHas('tabungan', fn($q) => $q->whereRaw("LOWER(nama) LIKE '%darurat%'"))
            ->sum('jumlah');

        $cur = 'Rp ' . number_format(min($saved, $minAmount), 0, ',', '.');
        $tgt = 'Rp ' . number_format($minAmount, 0, ',', '.');
        return [$saved, $minAmount, "{$cur} / {$tgt}"];
    }

    private function progressNoBudgetExceeded(User $user): array
    {
        $budgets = \App\Models\Anggaran::where('household_id', $user->household_id)->get();
        $total   = $budgets->count();
        if ($total === 0) {
            return [0, 1, 'Belum ada anggaran'];
        }

        $safe = 0;
        foreach ($budgets as $budget) {
            $spent = Transaksi::where('household_id', $user->household_id)
                ->where('kategori_id', $budget->kategori_id)
                ->where('jenis', 'pengeluaran')
                ->whereMonth('tanggal', now()->month)
                ->sum('jumlah');
            if ($spent <= $budget->jumlah) $safe++;
        }
        return [$safe, $total, "{$safe} / {$total} anggaran aman"];
    }

    private function progressCategoryLimit(User $user, string $categoryName, int $limit): array
    {
        $spent = (float) Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->whereHas('kategori', fn($q) => $q->whereRaw('LOWER(nama) LIKE ?', ['%' . strtolower($categoryName) . '%']))
            ->whereBetween('tanggal', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('jumlah');

        $cur = 'Rp ' . number_format($spent, 0, ',', '.');
        $tgt = 'Rp ' . number_format($limit, 0, ',', '.');
        return [$spent, $limit, "{$cur} / {$tgt} terpakai"];
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
