<?php

namespace Tests\Feature\Billing;

use App\Contracts\PaymentGateway;
use App\Models\Household;
use App\Models\Plan;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeGateway;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private FakeGateway $fake;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fake = new FakeGateway();
        $this->app->instance(PaymentGateway::class, $this->fake);
    }

    private function seedPlans(): array
    {
        $mk = fn (string $s, float $h) => Plan::create([
            'nama' => ucfirst($s), 'slug' => $s, 'harga' => $h,
            'max_anggota' => 5, 'max_transaksi' => -1, 'max_ocr' => 10, 'fitur' => [], 'is_active' => true,
        ]);
        return ['free' => $mk('free', 0), 'basic' => $mk('basic', 29000)];
    }

    private function user(Plan $plan): User
    {
        $h = Household::create(['nama' => 'Kel', 'slug' => 'kel-' . uniqid(), 'plan_id' => $plan->id, 'status' => 'active']);
        return User::factory()->create(['household_id' => $h->id]);
    }

    public function test_checkout_creates_pending_payment_with_merchant_ref(): void
    {
        $plans = $this->seedPlans();
        $user  = $this->user($plans['free']);

        $payment = app(PaymentService::class)->checkout($user, $plans['basic'], 'monthly');

        $this->assertSame('pending', $payment->status);
        $this->assertEquals(29000, (int) $payment->amount);
        $this->assertStringStartsWith('ROM-', $payment->merchant_ref);
        $this->assertNotEmpty($payment->checkout_url);
        $this->assertDatabaseHas('payment_history', ['merchant_ref' => $payment->merchant_ref, 'status' => 'pending']);
    }

    public function test_apply_payment_activates_plan_and_is_idempotent(): void
    {
        $plans   = $this->seedPlans();
        $user    = $this->user($plans['free']);
        $svc     = app(PaymentService::class);
        $payment = $svc->checkout($user, $plans['basic'], 'monthly');

        $first  = $svc->applyPayment($payment, 29000, ['x' => 1]);
        $second = $svc->applyPayment($payment->fresh(), 29000, ['x' => 1]); // ulang (webhook+cron)

        $this->assertTrue($first);
        $this->assertFalse($second); // sudah paid → tidak dobel proses
        $this->assertSame($plans['basic']->id, $user->household->refresh()->plan_id);
        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_apply_payment_rejects_amount_mismatch(): void
    {
        $plans   = $this->seedPlans();
        $user    = $this->user($plans['free']);
        $svc     = app(PaymentService::class);
        $payment = $svc->checkout($user, $plans['basic'], 'monthly');

        $ok = $svc->applyPayment($payment, 1000, ['tampered' => true]); // bayar Rp 1.000

        $this->assertFalse($ok);
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame($plans['free']->id, $user->household->refresh()->plan_id); // plan TIDAK naik
    }

    public function test_sync_status_applies_when_gateway_reports_paid(): void
    {
        $plans   = $this->seedPlans();
        $user    = $this->user($plans['free']);
        $svc     = app(PaymentService::class);
        $payment = $svc->checkout($user, $plans['basic'], 'monthly');

        $this->fake->nextStatus = 'paid';
        $status = $svc->syncStatus($payment);

        $this->assertSame('paid', $status);
        $this->assertSame($plans['basic']->id, $user->household->refresh()->plan_id);
    }
}
