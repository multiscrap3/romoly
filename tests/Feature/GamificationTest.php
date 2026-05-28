<?php

namespace Tests\Feature;

use App\Models\Notifikasi;
use App\Models\User;
use App\Services\NotifikasiService;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $notifikasi = $this->mock(NotifikasiService::class);
        $notifikasi->shouldReceive('send')->andReturn(new Notifikasi())->byDefault();
    }

    public function test_gamification_page_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('gamifikasi.index'))
            ->assertOk()
            ->assertViewIs('gamification.index');
    }

    public function test_gamification_page_redirects_for_guest(): void
    {
        $this->get(route('gamifikasi.index'))
            ->assertRedirect(route('login'));
    }

    public function test_awarding_xp_creates_log_and_updates_gamification(): void
    {
        $user      = User::factory()->create();
        $xpService = app(XpService::class);
        $xp        = $xpService->award($user, 'transaction');

        $this->assertSame(XpService::XP_AMOUNTS['transaction'], $xp);
        $this->assertDatabaseHas('xp_logs', ['user_id' => $user->id, 'source' => 'transaction']);
        $this->assertDatabaseHas('user_gamification', ['user_id' => $user->id]);
    }

    public function test_user_gamification_record_is_created_on_first_xp_award(): void
    {
        $user = User::factory()->create();
        $this->assertDatabaseMissing('user_gamification', ['user_id' => $user->id]);
        app(XpService::class)->award($user, 'transaction');
        $this->assertDatabaseHas('user_gamification', ['user_id' => $user->id]);
    }

    public function test_gamification_index_shows_level_and_momentum(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('gamifikasi.index'))
            ->assertOk()
            ->assertViewHas('gamification')
            ->assertViewHas('momentumStatus')
            ->assertViewHas('progressPercent');
    }
}
