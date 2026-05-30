<?php

namespace App\Services;

use App\Models\UserChallenge;
use App\Models\UserGamification;
use App\Models\WeeklyReview;
use App\Models\XpLog;
use App\Models\User;

class GamificationDashboardService
{
    /**
     * Kumpulkan insight gamifikasi untuk ditampilkan di dashboard.
     * Maks 4 item, diurutkan berdasarkan prioritas (1 = paling urgent).
     *
     * @return array{icon:string, color:string, text:string, link:string, priority:int}[]
     */
    public function getInsights(User $user): array
    {
        $insights = [];
        $gami     = UserGamification::where('user_id', $user->id)->first();

        // --- 1. Challenge berakhir hari ini ---
        $todayCount = UserChallenge::where('user_id', $user->id)
            ->whereNull('completed_at')
            ->whereBetween('expires_at', [now()->startOfDay(), now()->endOfDay()])
            ->count();

        if ($todayCount > 0) {
            $insights[] = [
                'icon'     => 'bi-alarm',
                'color'    => 'danger',
                'text'     => $todayCount === 1
                    ? '1 tantangan berakhir hari ini'
                    : "{$todayCount} tantangan berakhir hari ini",
                'link'     => route('gamifikasi.index'),
                'priority' => 1,
            ];
        } else {
            // Challenge berakhir besok
            $tomorrowCount = UserChallenge::where('user_id', $user->id)
                ->whereNull('completed_at')
                ->whereBetween('expires_at', [
                    now()->addDay()->startOfDay(),
                    now()->addDay()->endOfDay(),
                ])
                ->count();

            if ($tomorrowCount > 0) {
                $insights[] = [
                    'icon'     => 'bi-alarm',
                    'color'    => 'warning',
                    'text'     => 'Ada tantangan berakhir besok',
                    'link'     => route('gamifikasi.index'),
                    'priority' => 2,
                ];
            }
        }

        // --- 2. Weekly review belum dibaca ---
        $unreadReview = WeeklyReview::where('user_id', $user->id)
            ->whereNull('viewed_at')
            ->orderByDesc('week_start')
            ->first();

        if ($unreadReview) {
            $insights[] = [
                'icon'     => 'bi-bar-chart-line',
                'color'    => 'primary',
                'text'     => 'Weekly review tersedia — '
                    . $unreadReview->week_start->format('d M'),
                'link'     => route('gamifikasi.review.show', $unreadReview->id),
                'priority' => 2,
            ];
        }

        // --- 3. Hampir naik level (progress >= 85%) ---
        if ($gami && $gami->level < 10) {
            $progress = LevelService::progressPercent($gami->total_xp, $gami->level);

            if ($progress >= 85) {
                $threshold   = LevelService::xpThreshold($gami->level);
                $levelXp     = LevelService::xpToNextLevel($gami->level);
                $xpRemaining = ($threshold + $levelXp) - $gami->total_xp;

                $insights[] = [
                    'icon'     => 'bi-trophy',
                    'color'    => 'primary',
                    'text'     => number_format($xpRemaining) . ' XP lagi ke Level '
                        . ($gami->level + 1),
                    'link'     => route('gamifikasi.index'),
                    'priority' => 3,
                ];
            }
        }

        // --- 4. Momentum melemah / hilang ---
        if ($gami) {
            $score = (float) $gami->momentum_score;

            if ($score < 40) {
                $insights[] = [
                    'icon'     => 'bi-lightning',
                    'color'    => 'warning',
                    'text'     => 'Momentum melemah — catat transaksi hari ini',
                    'link'     => route('gamifikasi.index'),
                    'priority' => 3,
                ];
            } elseif ($score >= 90) {
                $insights[] = [
                    'icon'     => 'bi-lightning-charge',
                    'color'    => 'success',
                    'text'     => 'Strong momentum · ' . round($score) . '/100',
                    'link'     => route('gamifikasi.index'),
                    'priority' => 5,
                ];
            }
        }

        // --- 5. Belum catat transaksi hari ini (muncul >= 18:00, momentum < 70) ---
        if (now()->hour >= 18 && $gami && $gami->momentum_score < 70) {
            $loggedToday = XpLog::where('user_id', $user->id)
                ->where('source', 'transaction')
                ->whereDate('created_at', today())
                ->exists();

            if (!$loggedToday) {
                $insights[] = [
                    'icon'     => 'bi-pencil-square',
                    'color'    => 'secondary',
                    'text'     => 'Belum ada transaksi dicatat hari ini',
                    'link'     => route('transaksi.index'),
                    'priority' => 4,
                ];
            }
        }

        usort($insights, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        return array_slice($insights, 0, 4);
    }

    /**
     * Data ringkasan level + momentum untuk header widget.
     */
    public function getSummary(User $user): array
    {
        $gami = UserGamification::where('user_id', $user->id)->first();

        if (!$gami) {
            return [
                'level'             => 1,
                'level_title'       => LevelService::title(1),
                'progress_percent'  => 0,
                'xp_remaining'      => LevelService::xpToNextLevel(1),
                'total_xp'          => 0,
                'momentum_score'    => 50,
                'momentum_label'    => 'Stable',
                'momentum_color'    => 'primary',
                'earned_count'      => 0,
                'active_challenges' => 0,
            ];
        }

        $score = (float) $gami->momentum_score;
        $level = $gami->level;

        [$momentumLabel, $momentumColor] = match(true) {
            $score >= 90 => ['Strong', 'success'],
            $score >= 70 => ['Stable', 'primary'],
            $score >= 40 => ['Weakening', 'warning'],
            default      => ['Lost Focus', 'danger'],
        };

        $progressPercent = LevelService::progressPercent($gami->total_xp, $level);
        $xpRemaining     = $level < 10
            ? (LevelService::xpThreshold($level) + LevelService::xpToNextLevel($level)) - $gami->total_xp
            : 0;

        $earnedCount = \App\Models\UserAchievement::where('user_id', $user->id)->count();

        $activeChallenges = UserChallenge::where('user_id', $user->id)
            ->whereNull('completed_at')
            ->where('expires_at', '>=', now())
            ->count();

        return [
            'level'             => $level,
            'level_title'       => LevelService::title($level),
            'progress_percent'  => (int) round($progressPercent),
            'xp_remaining'      => max(0, $xpRemaining),
            'total_xp'          => $gami->total_xp,
            'momentum_score'    => round($score),
            'momentum_label'    => $momentumLabel,
            'momentum_color'    => $momentumColor,
            'earned_count'      => $earnedCount,
            'active_challenges' => $activeChallenges,
        ];
    }
}
