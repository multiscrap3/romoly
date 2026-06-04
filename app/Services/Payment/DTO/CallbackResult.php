<?php

namespace App\Services\Payment\DTO;

final class CallbackResult
{
    public function __construct(
        public readonly string $merchantRef, // = payment_history.merchant_ref
        public readonly bool   $paid,
        public readonly int    $rawAmount,   // untuk validasi-ulang nominal
        public readonly array  $payload,     // disimpan ke raw_payload
    ) {}
}
