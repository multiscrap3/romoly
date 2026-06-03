<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\UserAchievement;
use App\Models\UserChallenge;
use App\Models\UserGamification;
use App\Models\WeeklyReview;
use App\Models\XpLog;
use App\Services\ChallengeService;
use App\Services\LevelService;
use App\Services\MomentumService;
use App\Services\WeeklyReviewService;

class GamificationController extends Controller
{
    public function __construct(
        private readonly WeeklyReviewService $weeklyReviewService,
        private readonly ChallengeService $challengeService,
        private readonly MomentumService $momentumService,
    ) {}

    public function index()
    {
        $user         = auth()->user();
        $gamification = UserGamification::firstOrCreate(
            ['user_id' => $user->id],
            ['total_xp' => 0, 'level' => 1, 'momentum_score' => 50.0]
        );

        $progressPercent = LevelService::progressPercent($gamification->total_xp, $gamification->level);
        $xpToNext        = LevelService::xpToNextLevel($gamification->level);
        $momentumStatus  = $this->momentumService->getStatus($gamification->momentum_score);

        $achievements = UserAchievement::where('user_id', $user->id)
            ->with('achievement')
            ->orderByDesc('earned_at')
            ->get();

        $earnedMap = $achievements->keyBy('achievement_id');

        $allAchievements = Achievement::orderByRaw("
            CASE rarity
                WHEN 'platinum' THEN 1
                WHEN 'gold'     THEN 2
                WHEN 'silver'   THEN 3
                ELSE 4
            END
        ")->get();

        $newMajorAchievements = UserAchievement::where('user_id', $user->id)
            ->where('earned_at', '>=', now()->subMinutes(30))
            ->whereHas('achievement', fn ($q) => $q->where('is_major', true))
            ->with('achievement')
            ->get();

        // Assign challenge baru dulu agar yang fresh ikut tampil periode ini
        $this->challengeService->assignForUser($user);

        $activeChallenges = UserChallenge::where('user_id', $user->id)
            ->where('expires_at', '>=', now())
            ->whereNull('completed_at')
            ->with('challenge')
            ->get()
            ->map(function (UserChallenge $uc) use ($user) {
                $uc->setRelation('challenge', $uc->challenge);
                $uc->progress_view = $this->challengeService->progressFor($user, $uc);
                return $uc;
            });

        $latestReview = WeeklyReview::where('user_id', $user->id)
            ->orderByDesc('week_start')
            ->first();

        // ── Statistik motivasi (header & koleksi) ─────────────────────
        $nextTitle = $gamification->level < LevelService::MAX_LEVEL
            ? LevelService::title($gamification->level + 1)
            : null;

        $stats = [
            'level'             => $gamification->level,
            'title'            => LevelService::title($gamification->level),
            'next_title'       => $nextTitle,
            'total_xp'         => (int) $gamification->total_xp,
            'momentum'         => round((float) $gamification->momentum_score),
            'xp_week'          => (int) XpLog::where('user_id', $user->id)
                                    ->where('created_at', '>=', now()->startOfWeek())
                                    ->sum('xp_amount'),
            'achievements'     => $earnedMap->count(),
            'achievements_all' => $allAchievements->count(),
            'completion'       => $allAchievements->count() > 0
                                    ? round($earnedMap->count() / $allAchievements->count() * 100)
                                    : 0,
            'challenges_done'  => UserChallenge::where('user_id', $user->id)
                                    ->whereNotNull('completed_at')->count(),
            'active_count'     => $activeChallenges->count(),
        ];

        // Hitung koleksi per-rarity (earned vs total) untuk progress koleksi
        $rarityCounts = [];
        foreach (['platinum', 'gold', 'silver', 'bronze'] as $r) {
            $totalR  = $allAchievements->where('rarity', $r)->count();
            $earnedR = $allAchievements->where('rarity', $r)
                ->filter(fn ($a) => isset($earnedMap[$a->id]))->count();
            if ($totalR > 0) {
                $rarityCounts[$r] = ['earned' => $earnedR, 'total' => $totalR];
            }
        }

        return view('gamification.index', compact(
            'gamification', 'progressPercent', 'xpToNext',
            'momentumStatus', 'achievements', 'earnedMap',
            'allAchievements', 'newMajorAchievements',
            'activeChallenges', 'latestReview',
            'stats', 'rarityCounts'
        ));
    }

    public function weeklyReview(int $id)
    {
        $user   = auth()->user();
        $review = WeeklyReview::where('user_id', $user->id)->findOrFail($id);
        $this->weeklyReviewService->markViewed($review, $user);
        return view('gamification.weekly_review', compact('review'));
    }
}
