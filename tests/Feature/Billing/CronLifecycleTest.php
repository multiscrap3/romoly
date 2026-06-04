<?php

namespace Tests\Feature\Billing;

use App\Contracts\PaymentGateway;
use App\Models\Household;
use App\Models\PaymentHistory;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\FakeGateway;
use Tests\TestCase;

class CronLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private FakeGateway $fakeGateway;
    private string $secret;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeGateway = new FakeGateway();
        $this->app->instance(PaymentGateway::class, $this->fakeGateway);
        $this->secret = 'test-cron-secret';
        config(['app.cron_secret_key' => $this->secret]);
    }

    private function seedData(): array
    {
        $freePlan = Plan::create([
            'nama' => 'Free', 'slug' => 'free', 'harga' => 0,
            'max_anggota' => 2, 'max_transaksi' => 50, 'max_ocr' => 0,
            'fitur' => [], 'is_active' => true,
        ]);
        $basicPlan = Plan::create([
            'nama' => 'Basic', 'slug' => 'basic', 'harga' => 29000,
            'max_anggota' => 5, 'max_transaksi' => 500, 'max_ocr' => 10,
            'fitur' => [], 'is_active' => true,
        ]);

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $user = User::factory()->create([
            'email_verified_at'  => now(),
            'email_notifications' => true,
        ]);
        $household = Household::create([
            'nama'    => 'Test Household',
            'slug'    => 'test-household',
            'plan_id' => $freePlan->id,
            'status'  => 'active',
        ]);
        $user->update(['household_id' => $household->id]);
        $user->assignRole('admin');

        return compact('freePlan', 'basicPlan', 'user', 'household');
    }

    // ── reconcilePayments ──────────────────────────────────────────────────

    public function test_reconcile_activates_pending_payment_when_gateway_returns_paid(): void
    {
        ['basicPlan' => $plan, 'household' => $household] = $this->seedData();

        $payment = PaymentHistory::create([
            'household_id'   => $household->id,
            'plan_id'        => $plan->id,
            'gateway'        => 'duitku',
            'merchant_ref'   => 'ROM-test-reconcile',
            'amount'         => 29000,
            'billing_period' => 'monthly',
            'payment_method' => 'lainnya',
            'status'         => 'pending',
            'period_start'   => today()->toDateString(),
            'period_end'     => today()->addMonth()->toDateString(),
            'expired_at'     => now()->addDay(),
            'created_at'     => now()->subMinutes(10),
        ]);
        $payment->setCreatedAt(now()->subMinutes(10))->save();

        $this->fakeGateway->nextStatus = 'paid';

        $response = $this->withHeaders(['X-Cron-Secret' => $this->secret])
            ->postJson('/cron/reconcile-payments');

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('payment_history', [
            'merchant_ref' => 'ROM-test-reconcile',
            'status'       => 'paid',
        ]);
        $this->assertEquals($plan->id, $household->fresh()->plan_id);
    }

    public function test_reconcile_marks_expired_pending_as_failed(): void
    {
        ['basicPlan' => $plan, 'household' => $household] = $this->seedData();

        PaymentHistory::create([
            'household_id'   => $household->id,
            'plan_id'        => $plan->id,
            'gateway'        => 'duitku',
            'merchant_ref'   => 'ROM-test-expired',
            'amount'         => 29000,
            'billing_period' => 'monthly',
            'payment_method' => 'lainnya',
            'status'         => 'pending',
            'period_start'   => today()->toDateString(),
            'period_end'     => today()->addMonth()->toDateString(),
            'expired_at'     => now()->subHour(),
        ]);

        $response = $this->withHeaders(['X-Cron-Secret' => $this->secret])
            ->postJson('/cron/reconcile-payments');

        $response->assertOk();
        $this->assertDatabaseHas('payment_history', [
            'merchant_ref' => 'ROM-test-expired',
            'status'       => 'failed',
        ]);
    }

    // ── processExpiredSubscriptions ────────────────────────────────────────

    public function test_process_expired_subscriptions_downgrades_expired_household(): void
    {
        ['freePlan' => $freePlan, 'basicPlan' => $basicPlan, 'household' => $household] = $this->seedData();

        $household->forceFill([
            'plan_id'          => $basicPlan->id,
            'subscription_end' => today()->subDay()->toDateString(),
            'status'           => 'active',
        ])->save();

        $response = $this->withHeaders(['X-Cron-Secret' => $this->secret])
            ->postJson('/cron/process-expired-subscriptions');

        $response->assertOk()->assertJsonPath('success', true);

        $fresh = $household->fresh();
        $this->assertEquals($freePlan->id, $fresh->plan_id);
        $this->assertNull($fresh->subscription_end);
        $this->assertNull($fresh->renewal_reminded_at);
    }

    public function test_process_expired_subscriptions_skips_already_free(): void
    {
        ['freePlan' => $freePlan, 'household' => $household] = $this->seedData();

        $household->forceFill([
            'plan_id'          => $freePlan->id,
            'subscription_end' => null,
        ])->save();

        $response = $this->withHeaders(['X-Cron-Secret' => $this->secret])
            ->postJson('/cron/process-expired-subscriptions');

        $response->assertOk();
        $response->assertJsonPath('data.downgraded', 0);
    }

    // ── sendRenewalReminders ───────────────────────────────────────────────

    public function test_send_renewal_reminders_queues_mail_and_records_reminded_at(): void
    {
        Mail::fake();

        ['basicPlan' => $basicPlan, 'household' => $household, 'user' => $user] = $this->seedData();

        $household->forceFill([
            'plan_id'             => $basicPlan->id,
            'subscription_end'    => today()->addDays(5)->toDateString(),
            'renewal_reminded_at' => null,
        ])->save();

        $response = $this->withHeaders(['X-Cron-Secret' => $this->secret])
            ->postJson('/cron/send-renewal-reminders');

        $response->assertOk()->assertJsonPath('success', true);
        $response->assertJsonPath('data.sent', 1);

        Mail::assertQueued(\App\Mail\GeneralNotificationMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        $this->assertNotNull($household->fresh()->renewal_reminded_at);
    }

    public function test_send_renewal_reminders_skips_if_reminded_recently(): void
    {
        Mail::fake();

        ['basicPlan' => $basicPlan, 'household' => $household] = $this->seedData();

        $household->forceFill([
            'plan_id'             => $basicPlan->id,
            'subscription_end'    => today()->addDays(5)->toDateString(),
            'renewal_reminded_at' => now()->subDays(2),
        ])->save();

        $response = $this->withHeaders(['X-Cron-Secret' => $this->secret])
            ->postJson('/cron/send-renewal-reminders');

        $response->assertOk();
        $response->assertJsonPath('data.sent', 0);
        Mail::assertNothingQueued();
    }
}
