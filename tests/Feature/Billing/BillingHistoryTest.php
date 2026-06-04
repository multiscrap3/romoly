<?php

namespace Tests\Feature\Billing;

use App\Models\Household;
use App\Models\PaymentHistory;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function seedData(): array
    {
        $plan = Plan::create([
            'nama' => 'Basic', 'slug' => 'basic', 'harga' => 29000,
            'max_anggota' => 5, 'max_transaksi' => 500, 'max_ocr' => 10,
            'fitur' => [], 'is_active' => true,
        ]);
        $household = Household::create([
            'nama' => 'Test', 'slug' => 'test', 'plan_id' => $plan->id, 'status' => 'active',
        ]);
        $user = User::factory()->create(['household_id' => $household->id]);
        return compact('plan', 'household', 'user');
    }

    public function test_history_page_returns_200_for_authenticated_user(): void
    {
        ['user' => $user] = $this->seedData();
        $response = $this->actingAs($user)->get('/billing/history');
        $response->assertOk();
        $response->assertViewIs('billing.history');
    }

    public function test_history_passes_payments_to_view(): void
    {
        ['user' => $user, 'plan' => $plan, 'household' => $household] = $this->seedData();

        PaymentHistory::create([
            'household_id'   => $household->id,
            'plan_id'        => $plan->id,
            'gateway'        => 'duitku',
            'merchant_ref'   => 'ROM-test-history',
            'amount'         => 29000,
            'billing_period' => 'monthly',
            'payment_method' => 'lainnya',
            'status'         => 'paid',
            'period_start'   => today()->toDateString(),
            'period_end'     => today()->addMonth()->toDateString(),
            'expired_at'     => now()->addDay(),
            'payment_date'   => today()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get('/billing/history');
        $response->assertOk();
        $response->assertViewHas('payments');
    }

    public function test_history_requires_auth(): void
    {
        $response = $this->get('/billing/history');
        $response->assertRedirect('/login');
    }
}
