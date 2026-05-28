<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\WeeklyReviewService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateWeeklyReviewsCommand extends Command
{
    protected $signature   = 'gamification:generate-weekly-reviews';
    protected $description = 'Pre-generate weekly review data for all active users';

    public function handle(WeeklyReviewService $service): void
    {
        $weekStart = Carbon::now()->startOfWeek();
        $users     = User::where('is_active', true)->get();

        foreach ($users as $user) {
            $service->generateForUser($user, $weekStart);
        }

        $this->info('Weekly reviews generated for ' . $users->count() . ' users.');
    }
}
