<?php

namespace App\Contracts;

use App\Models\PaymentHistory;
use App\Services\Payment\DTO\Buyer;
use App\Services\Payment\DTO\CallbackResult;
use App\Services\Payment\DTO\ChargeResult;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /** Buat transaksi di gateway; balikkan URL bayar + referensi. */
    public function createCharge(PaymentHistory $payment, Buyer $buyer): ChargeResult;

    /** Validasi & parse webhook. Signature WAJIB tervalidasi di sini (boleh throw bila invalid). */
    public function verifyCallback(Request $request): CallbackResult;

    /** Poll status transaksi (untuk status-polling & cron). Balikkan 'paid' | 'pending' | 'failed'. */
    public function fetchStatus(PaymentHistory $payment): string;
}
