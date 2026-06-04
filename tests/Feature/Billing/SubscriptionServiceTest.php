<?php

namespace Tests\Feature\Billing;

use App\Models\Household;
use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function plan(string $slug, float $harga): Plan
    {
        return Plan::create([
            'nama' => ucfirst($slug), 'slug' => $slug, 'harga' => $harga,
            'max_anggota' => 5, 'max_transaksi' => -1, 'max_ocr' => 10,
            'fitur' => [], 'is_active' => true,
        ]);
    }

    public function test_activate_sets_plan_and_one_month_window(): void
    {
        $free  = $this->plan('free', 0);
        $basic = $this->plan('basic', 29000);
        $h = Household::create([
            'nama' => 'Kel A', 'slug' => 'kel-a', 'plan_id' => $free->id, 'status' => 'active',
        ]);

        app(SubscriptionService::class)->activate($h, $basic, 'monthly');
        $h->refresh();

        $this->assertSame($basic->id, $h->plan_id);
        $this->assertSame('active', $h->status);
        $this->assertEquals(now()->addMonth()->toDateString(), $h->subscription_end->toDateString());
    }

    public function test_activate_extends_from_existing_end_when_still_active(): void
    {
        $basic  = $this->plan('basic', 29000);
        $future = now()->addDays(10)->toDateString();
        $h = Household::create([
            'nama' => 'Kel B', 'slug' => 'kel-b', 'plan_id' => $basic->id, 'status' => 'active',
            'subscription_start' => now()->toDateString(), 'subscription_end' => $future,
        ]);

        app(SubscriptionService::class)->activate($h, $basic, 'monthly');
        $h->refresh();

        // Diperpanjang dari subscription_end lama, bukan dari hari ini
        $this->assertEquals(
            now()->addDays(10)->addMonth()->toDateString(),
            $h->subscription_end->toDateString()
        );
    }
}
