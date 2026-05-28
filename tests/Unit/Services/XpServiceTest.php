<?php

namespace Tests\Unit\Services;

use App\Models\Notifikasi;
use App\Models\User;
use App\Models\UserGamification;
use App\Models\XpLog;
use App\Services\LevelService;
use App\Services\NotifikasiService;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XpServiceTest extends TestCase
{
    use RefreshDatabase;

    private XpService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $notifikasi = $this->mock(NotifikasiService::class);
        $notifikasi->shouldReceive('send')->andReturn(new Notifikasi())->byDefault();
        $this->service = new XpService(new LevelService(), $notifikasi);
    }

    public function test_award_creates_xp_log(): void
    {
        $user = User::factory()->create();
        $this->service->award($user, 'transaction');
        $this->assertDatabaseHas('xp_logs', ['user_id' => $user->id, 'source' => 'transaction']);
    }

    public function test_award_returns_correct_xp_amount(): void
    {
        $user = User::factory()->create();
        $xp = $this->service->award($user, 'transaction');
        $this->assertSame(XpService::XP_AMOUNTS['transaction'], $xp);
    }

    public function test_daily_cap_stops_transaction_xp_at_limit(): void
    {
        $user = User::factory()->create();
        // 20 XP cap / 2 XP per transaction = 10 transactions to fill cap
        for ($i = 0; $i < 10; $i++) {
            $this->service->award($user, 'transaction');
        }
        $earned = $this->service->award($user, 'transaction');
        $this->assertSame(0, $earned);
    }

    public function test_daily_cap_does_not_affect_non_transaction_sources(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 10; $i++) {
            $this->service->award($user, 'transaction');
        }
        $xp = $this->service->award($user, 'daily_review');
        $this->assertSame(XpService::XP_AMOUNTS['daily_review'], $xp);
    }

    public function test_award_unknown_source_returns_zero(): void
    {
        $user = User::factory()->create();
        $this->assertSame(0, $this->service->award($user, 'nonexistent_source'));
    }

    public function test_total_xp_accumulates_in_user_gamification(): void
    {
        $user = User::factory()->create();
        $this->service->award($user, 'transaction');
        $this->service->award($user, 'daily_review');
        $gamification = UserGamification::where('user_id', $user->id)->first();
        $expected = XpService::XP_AMOUNTS['transaction'] + XpService::XP_AMOUNTS['daily_review'];
        $this->assertSame($expected, $gamification->total_xp);
    }

    public function test_level_updates_when_xp_threshold_crossed(): void
    {
        $user = User::factory()->create();
        // Level 2 threshold = 50 XP. consistency_7day = 30 XP * 2 = 60 XP → level 2
        $this->service->award($user, 'consistency_7day');
        $this->service->award($user, 'consistency_7day');
        $gamification = UserGamification::where('user_id', $user->id)->first();
        $this->assertSame(2, $gamification->level);
    }
}
