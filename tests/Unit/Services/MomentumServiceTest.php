<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\UserGamification;
use App\Services\MomentumService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MomentumServiceTest extends TestCase
{
    use RefreshDatabase;

    private MomentumService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MomentumService();
    }

    public function test_record_activity_increases_momentum(): void
    {
        $user = User::factory()->create();
        UserGamification::create(['user_id' => $user->id, 'momentum_score' => 50.0]);
        $this->service->recordActivity($user, 'transaction_logged');
        $g = UserGamification::where('user_id', $user->id)->first();
        $this->assertSame('52.00', $g->momentum_score);
    }

    public function test_momentum_does_not_exceed_100(): void
    {
        $user = User::factory()->create();
        UserGamification::create(['user_id' => $user->id, 'momentum_score' => 99.0]);
        $this->service->recordActivity($user, 'weekly_review');
        $g = UserGamification::where('user_id', $user->id)->first();
        $this->assertSame('100.00', $g->momentum_score);
    }

    public function test_decay_reduces_momentum_when_inactive(): void
    {
        $user = User::factory()->create();
        UserGamification::create([
            'user_id'            => $user->id,
            'momentum_score'     => 60.0,
            'last_active_date'   => now()->subDays(2)->toDateString(),
            'grace_days_used'    => 1,
            'grace_period_start' => now()->startOfWeek()->toDateString(),
        ]);
        $this->service->applyDailyDecay($user);
        $g = UserGamification::where('user_id', $user->id)->first();
        $this->assertSame('58.00', $g->momentum_score);
    }

    public function test_grace_day_prevents_decay_once_per_week(): void
    {
        $user = User::factory()->create();
        UserGamification::create([
            'user_id'            => $user->id,
            'momentum_score'     => 60.0,
            'last_active_date'   => now()->subDays(2)->toDateString(),
            'grace_days_used'    => 0,
            'grace_period_start' => now()->startOfWeek()->toDateString(),
        ]);
        $this->service->applyDailyDecay($user);
        $g = UserGamification::where('user_id', $user->id)->first();
        $this->assertSame('60.00', $g->momentum_score);
        $this->assertSame(1, $g->grace_days_used);
    }

    public function test_decay_never_goes_below_zero(): void
    {
        $user = User::factory()->create();
        UserGamification::create([
            'user_id'            => $user->id,
            'momentum_score'     => 1.0,
            'last_active_date'   => now()->subDays(2)->toDateString(),
            'grace_days_used'    => 1,
            'grace_period_start' => now()->startOfWeek()->toDateString(),
        ]);
        $this->service->applyDailyDecay($user);
        $g = UserGamification::where('user_id', $user->id)->first();
        $this->assertSame('0.00', $g->momentum_score);
    }

    public function test_get_status_returns_correct_label(): void
    {
        $this->assertSame('Strong Momentum', $this->service->getStatus(95));
        $this->assertSame('Stable', $this->service->getStatus(75));
        $this->assertSame('Weakening', $this->service->getStatus(55));
        $this->assertSame('Lost Focus', $this->service->getStatus(20));
    }
}
