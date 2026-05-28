<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MomentumService;
use Illuminate\Console\Command;

class GamificationDailyDecayCommand extends Command
{
    protected $signature   = 'gamification:daily-decay';
    protected $description = 'Apply daily momentum decay for inactive users';

    public function handle(MomentumService $momentumService): void
    {
        $users = User::where('is_active', true)->get();
        foreach ($users as $user) {
            $momentumService->applyDailyDecay($user);
        }
        $this->info('Daily momentum decay applied to ' . $users->count() . ' users.');
    }
}
