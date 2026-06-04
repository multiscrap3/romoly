<?php

namespace App\Services\Payment\DTO;

final class Buyer
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $phone,
    ) {}
}
