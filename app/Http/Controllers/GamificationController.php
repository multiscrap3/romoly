<?php

namespace App\Http\Controllers;

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
            'momentumStatus', 'achievements', 'activeChallenges', 'latestReview'
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
