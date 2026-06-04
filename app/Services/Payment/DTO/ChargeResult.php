<?php

namespace App\Services\Payment\DTO;

final class ChargeResult
{
    public function __construct(
        public readonly string $payUrl,     // URL halaman bayar (redirect user ke sini)
        public readonly string $reference,  // referensi transaksi dari gateway
    ) {}
}
