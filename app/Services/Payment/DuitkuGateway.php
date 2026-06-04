<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Models\PaymentHistory;
use App\Services\Payment\DTO\Buyer;
use App\Services\Payment\DTO\CallbackResult;
use App\Services\Payment\DTO\ChargeResult;
use Illuminate\Http\Request;

class DuitkuGateway implements PaymentGateway
{
    private \Duitku\Config $config;

    public function __construct()
    {
        $this->config = new \Duitku\Config(
            (string) config('payment.duitku.merchant_key'),
            (string) config('payment.duitku.merchant_code'),
        );
        $this->config->setSandboxMode(config('payment.duitku.mode') !== 'production');
    }

    public function createCharge(PaymentHistory $payment, Buyer $buyer): ChargeResult
    {
        $params = [
            'paymentAmount'   => (int) $payment->amount,         // dari DB plan, bukan input user
            'merchantOrderId' => $payment->merchant_ref,         // kunci idempotensi kita
            'productDetails'  => "Romoly {$payment->plan->nama} ({$payment->billing_period})",
            'email'           => $buyer->email,
            'customerVaName'  => $buyer->name,
            'phoneNumber'     => $buyer->phone,
            'callbackUrl'     => route('billing.webhook'),
            'returnUrl'       => route('billing.return', ['ref' => $payment->merchant_ref]),
            'expiryPeriod'    => 60 * 24, // menit → 24 jam
        ];

        $response = \Duitku\Pop::createInvoice($params, $this->config);
        $data = json_decode($response, true) ?: [];

        return new ChargeResult(
            payUrl: (string) ($data['paymentUrl'] ?? ''),
            reference: (string) ($data['reference'] ?? ''),
        );
    }

    public function verifyCallback(Request $request): CallbackResult
    {
        // Pop::callback() memvalidasi signature MD5(merchantCode+amount+merchantOrderId+apiKey).
        // Jika invalid, SDK akan throw — jangan proses payload tanpa lewat sini.
        $notif = \Duitku\Pop::callback($this->config);

        return new CallbackResult(
            merchantRef: (string) ($notif->merchantOrderId ?? ''),
            paid: (($notif->resultCode ?? '') === '00'),
            rawAmount: (int) ($notif->amount ?? 0),
            payload: (array) $notif,
        );
    }

    public function fetchStatus(PaymentHistory $payment): string
    {
        // KUNCI = merchant_ref kita sendiri → cron/status-polling tak butuh ID pihak ketiga
        $response = \Duitku\Pop::transactionStatus($payment->merchant_ref, $this->config);
        $data = json_decode($response, true) ?: [];

        return match ($data['statusCode'] ?? '01') {
            '00'    => 'paid',
            '02'    => 'failed',
            default => 'pending',
        };
    }
}
