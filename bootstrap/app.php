<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use App\Http\Middleware\CheckPlanLimitMiddleware;
use App\Http\Middleware\CronSecretMiddleware;
use App\Http\Middleware\HouseholdMiddleware;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SuperadminGlobalMiddleware;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            LocaleMiddleware::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'billing/webhook',
        ]);
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'household' => HouseholdMiddleware::class,
            'log.activity' => LogActivityMiddleware::class,
            'check.plan' => CheckPlanLimitMiddleware::class,
            'cron.secret' => CronSecretMiddleware::class,
            'superadmin.global' => SuperadminGlobalMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
