<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ChallengeService;
use Illuminate\Console\Command;

class GenerateChallengesCommand extends Command
{
    protected $signature   = 'gamification:generate-challenges';
    protected $description = 'Assign new weekly/monthly challenges to all active users';

    public function handle(ChallengeService $service): void
    {
        $users = User::where('is_active', true)->get();
        foreach ($users as $user) {
            $service->assignForUser($user);
        }
        $this->info('Challenges assigned to ' . $users->count() . ' users.');
    }
}
