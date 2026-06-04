<?php

namespace Tests\Feature\Billing;

use App\Contracts\PaymentGateway;
use App\Models\Household;
use App\Models\PaymentHistory;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeGateway;
use Tests\TestCase;

class BillingFlowTest extends TestCase
{
    use RefreshDatabase;

    private FakeGateway $fake;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fake = new FakeGateway();
        $this->app->instance(PaymentGateway::class, $this->fake);
    }

    private function seedData(): array
    {
        $mk = fn (string $s, float $h) => Plan::create([
            'nama' => ucfirst($s), 'slug' => $s, 'harga' => $h,
            'max_anggota' => 5, 'max_transaksi' => -1, 'max_ocr' => 10, 'fitur' => [], 'is_active' => true,
        ]);
        $free  = $mk('free', 0);
        $basic = $mk('basic', 29000);
        $h     = Household::create(['nama' => 'Kel', 'slug' => 'kel-x', 'plan_id' => $free->id, 'status' => 'active']);
        $user  = User::factory()->create(['household_id' => $h->id]);
        return [$free, $basic, $user];
    }

    public function test_pricing_page_loads(): void
    {
        [, , $user] = $this->seedData();
        $this->actingAs($user)->get(route('billing.index'))->assertOk()->assertViewIs('billing.index');
    }

    public function test_pay_redirects_to_checkout_url(): void
    {
        [, $basic, $user] = $this->seedData();

        $res = $this->actingAs($user)->post(route('billing.pay'), [
            'plan' => 'basic', 'period' => 'monthly',
        ]);

        $res->assertRedirect();
        $this->assertStringContainsString('sandbox.test/pay/', $res->headers->get('Location'));
        $this->assertDatabaseHas('payment_history', ['plan_id' => $basic->id, 'status' => 'pending']);
    }

    public function test_status_endpoint_activates_plan_when_paid(): void
    {
        [$free, $basic, $user] = $this->seedData();
        $this->actingAs($user)->post(route('billing.pay'), ['plan' => 'basic', 'period' => 'monthly']);
        $ref = PaymentHistory::first()->merchant_ref;

        $this->fake->nextStatus = 'paid';
        $this->actingAs($user)->getJson(route('billing.status', ['ref' => $ref]))
            ->assertOk()->assertJson(['status' => 'paid']);

        $this->assertSame($basic->id, $user->household->refresh()->plan_id);
    }

    public function test_webhook_activates_plan_with_valid_callback(): void
    {
        [$free, $basic, $user] = $this->seedData();
        $this->actingAs($user)->post(route('billing.pay'), ['plan' => 'basic', 'period' => 'monthly']);
        $ref = PaymentHistory::first()->merchant_ref;

        // FakeGateway->verifyCallback membaca input request
        $this->post(route('billing.webhook'), [
            'merchantOrderId' => $ref, 'resultCode' => '00', 'amount' => 29000,
        ])->assertOk();

        $this->assertSame($basic->id, $user->household->refresh()->plan_id);
    }
}
