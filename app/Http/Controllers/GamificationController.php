<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\UserAchievement;
use App\Models\UserChallenge;
use App\Models\UserGamification;
use App\Models\WeeklyReview;
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

        $activeChallenges = UserChallenge::where('user_id', $user->id)
            ->where('expires_at', '>=', now())
            ->whereNull('completed_at')
            ->with('challenge')
            ->get();

        $latestReview = WeeklyReview::where('user_id', $user->id)
            ->orderByDesc('week_start')
            ->first();

        $this->challengeService->assignForUser($user);

        return view('gamification.index', compact(
            'gamification', 'progressPercent', 'xpToNext',
            'momentumStatus', 'achievements', 'earnedMap',
            'allAchievements', 'newMajorAchievements',
            'activeChallenges', 'latestReview'
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
