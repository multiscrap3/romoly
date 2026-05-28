<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Gamification cron
Schedule::command('gamification:daily-decay')->dailyAt('00:05');
Schedule::command('gamification:generate-weekly-reviews')->weeklyOn(0, '23:00');
Schedule::command('gamification:generate-challenges')->weeklyOn(1, '00:10');
