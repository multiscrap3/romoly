<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\App\Contracts\PaymentGateway::class, function () {
            return match (config('payment.gateway')) {
                'duitku' => new \App\Services\Payment\DuitkuGateway(),
                default  => throw new \RuntimeException(
                    'Payment gateway tidak dikenal: ' . config('payment.gateway')
                ),
            };
        });
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // PDP D7 & D8: Enkripsi session + paksa HTTPS di production
        if (app()->environment('production')) {
            URL::forceScheme('https');
            config(['session.secure' => true]);
            config(['session.encrypt' => true]);
        }
    }
}
