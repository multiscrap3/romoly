<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Models\PaymentHistory;
use App\Models\Plan;
use App\Models\User;
use App\Services\Payment\DTO\Buyer;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly SubscriptionService $subscriptions,
    ) {}

    /**
     * Buat transaksi pending + minta URL bayar ke gateway.
     */
    public function checkout(User $user, Plan $plan, string $period): PaymentHistory
    {
        $months     = (int) config("payment.periods.$period", 1);
        $totalHarga = (int) $plan->harga * ($period === 'yearly' ? 10 : 1);

        $payment = PaymentHistory::create([
            'household_id'   => $user->household_id,
            'plan_id'        => $plan->id,
            'gateway'        => config('payment.gateway'),
            'merchant_ref'   => 'ROM-' . Str::uuid()->toString(),
            'amount'         => $totalHarga,
            'billing_period' => $period,
            'payment_method' => 'lainnya',
            'status'         => 'pending',
            'period_start'   => Carbon::now()->toDateString(),
            'period_end'     => Carbon::now()->copy()->addMonths($months)->toDateString(),
            'expired_at'     => Carbon::now()->addDay(),
        ]);

        $buyer  = new Buyer($user->name, $user->email, '08000000000'); // phone placeholder
        $charge = $this->gateway->createCharge($payment, $buyer);

        $payment->update([
            'payment_reference' => $charge->reference,
            'checkout_url'      => $charge->payUrl,
        ]);

        return $payment;
    }

    /**
     * Terapkan pembayaran (idempoten). Dipanggil webhook & syncStatus.
     * Return true bila baru saja diaktivasi, false bila sudah diproses / ditolak.
     */
    public function applyPayment(PaymentHistory $payment, int $rawAmount, array $payload): bool
    {
        return DB::transaction(function () use ($payment, $rawAmount, $payload) {
            /** @var PaymentHistory $p */
            $p = PaymentHistory::whereKey($payment->id)->lockForUpdate()->first();

            if ($p->status === 'paid') {
                return false; // webhook & cron bisa datang dua kali
            }

            if ($rawAmount !== (int) $p->amount) {
                $p->update(['status' => 'failed', 'raw_payload' => $payload]);
                Log::warning('Pembayaran nominal tidak cocok', [
                    'merchant_ref' => $p->merchant_ref, 'raw' => $rawAmount, 'expected' => (int) $p->amount,
                ]);
                return false; // cegah manipulasi nominal
            }

            $this->subscriptions->activate($p->household, $p->plan, $p->billing_period);

            $p->update([
                'status'       => 'paid',
                'payment_date' => Carbon::now()->toDateString(),
                'raw_payload'  => $payload,
            ]);

            return true;
        });
    }

    /**
     * Tanya status ke gateway; aktivasi jika sudah paid.
     * Untuk status-polling halaman return & cron rekonsiliasi.
     */
    public function syncStatus(PaymentHistory $payment): string
    {
        if ($payment->status === 'paid') {
            return 'paid';
        }

        $status = $this->gateway->fetchStatus($payment);

        if ($status === 'paid') {
            $this->applyPayment($payment, (int) $payment->amount, ['via' => 'sync']);
        } elseif ($status === 'failed') {
            $payment->update(['status' => 'failed']);
        }

        return $status;
    }
}
