<?php

namespace App\Services;

use App\Models\Transaksi;
use App\Models\User;

class GamificationInsightService
{
    /**
     * Generate rule-based insights for a user's current week.
     * All thresholds use per-user 30-day rolling baseline.
     */
    public function generateForUser(User $user): array
    {
        return array_values(array_filter([
            $this->checkOverspending($user),
            $this->checkSubscriptionPattern($user),
            $this->checkFoodDeliveryDominance($user),
            $this->checkNightSpendingPattern($user),
        ]));
    }

    private function checkOverspending(User $user): ?array
    {
        $thisWeek = $this->weeklyExpense($user, 0);
        $lastWeek = $this->weeklyExpense($user, 1);

        if ($lastWeek > 0 && $thisWeek > $lastWeek * 1.2) {
            $increase = round(($thisWeek - $lastWeek) / $lastWeek * 100);
            return [
                'type'    => 'overspending',
                'message' => "Pengeluaran meningkat {$increase}% dibanding minggu lalu.",
                'data'    => ['this_week' => $thisWeek, 'last_week' => $lastWeek],
            ];
        }
        return null;
    }

    private function checkSubscriptionPattern(User $user): ?array
    {
        $recurring = Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->whereMonth('tanggal', now()->month)
            ->selectRaw('jumlah, COUNT(*) as cnt')
            ->groupBy('jumlah')
            ->havingRaw('cnt >= 3')
            ->get();

        if ($recurring->isNotEmpty()) {
            return [
                'type'    => 'subscription_detected',
                'message' => 'Langganan rutin terdeteksi. Pastikan semua masih aktif kamu gunakan.',
                'data'    => ['amounts' => $recurring->pluck('jumlah')->toArray()],
            ];
        }
        return null;
    }

    private function checkFoodDeliveryDominance(User $user): ?array
    {
        $total = Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->whereBetween('tanggal', [now()->startOfWeek(), now()])
            ->sum('jumlah');

        if ($total <= 0) return null;

        $foodDelivery = Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->whereBetween('tanggal', [now()->startOfWeek(), now()])
            ->whereHas('kategori', fn($q) => $q->whereRaw("LOWER(nama) LIKE '%delivery%' OR LOWER(nama) LIKE '%ojek%'"))
            ->sum('jumlah');

        if (($foodDelivery / $total) > 0.30) {
            return [
                'type'    => 'food_delivery_dominant',
                'message' => 'Food delivery menjadi pengeluaran dominan minggu ini (>30% total).',
                'data'    => ['food_delivery' => $foodDelivery, 'total' => $total],
            ];
        }
        return null;
    }

    private function checkNightSpendingPattern(User $user): ?array
    {
        // Per-user 30-day rolling baseline — adaptive threshold
        $baseline = Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->where('created_at', '>=', now()->subDays(30))
            ->whereRaw('HOUR(created_at) >= 21')
            ->avg('jumlah') ?? 0;

        if ($baseline <= 0) return null;

        $thisWeekNight = Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->whereBetween('tanggal', [now()->startOfWeek(), now()])
            ->whereRaw('HOUR(created_at) >= 21')
            ->avg('jumlah') ?? 0;

        if ($thisWeekNight > $baseline * 1.3) {
            return [
                'type'    => 'night_spending_elevated',
                'message' => 'Pengeluaran malam meningkat dibanding kebiasaanmu.',
                'data'    => ['this_week_avg' => $thisWeekNight, 'baseline' => $baseline],
            ];
        }
        return null;
    }

    private function weeklyExpense(User $user, int $weeksAgo): float
    {
        $start = now()->subWeeks($weeksAgo)->startOfWeek();
        $end   = now()->subWeeks($weeksAgo)->endOfWeek();

        return Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->whereBetween('tanggal', [$start, $end])
            ->sum('jumlah');
    }
}
