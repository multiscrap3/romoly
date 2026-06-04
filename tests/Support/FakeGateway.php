<?php

namespace Tests\Support;

use App\Contracts\PaymentGateway;
use App\Models\PaymentHistory;
use App\Services\Payment\DTO\Buyer;
use App\Services\Payment\DTO\CallbackResult;
use App\Services\Payment\DTO\ChargeResult;
use Illuminate\Http\Request;

class FakeGateway implements PaymentGateway
{
    public string $nextStatus = 'pending';       // diatur test untuk fetchStatus()
    public ?CallbackResult $nextCallback = null; // diatur test untuk verifyCallback()

    public function createCharge(PaymentHistory $payment, Buyer $buyer): ChargeResult
    {
        return new ChargeResult(
            payUrl: 'https://sandbox.test/pay/' . $payment->merchant_ref,
            reference: 'REF-' . $payment->merchant_ref,
        );
    }

    public function verifyCallback(Request $request): CallbackResult
    {
        return $this->nextCallback ?? new CallbackResult(
            merchantRef: (string) $request->input('merchantOrderId'),
            paid: $request->input('resultCode') === '00',
            rawAmount: (int) $request->input('amount'),
            payload: $request->all(),
        );
    }

    public function fetchStatus(PaymentHistory $payment): string
    {
        return $this->nextStatus;
    }
}
