<?php

return [
    // Driver aktif. Ganti via env saat pindah gateway (mis. 'midtrans').
    'gateway' => env('PAYMENT_GATEWAY', 'duitku'),

    'duitku' => [
        'mode'          => env('DUITKU_MODE', 'sandbox'), // sandbox | production
        'merchant_code' => env('DUITKU_MERCHANT_CODE'),
        'merchant_key'  => env('DUITKU_MERCHANT_KEY'),
    ],

    // Jumlah bulan yang ditambahkan ke subscription_end per periode.
    'periods' => [
        'monthly' => 1,
        'yearly'  => 12,
    ],
];
