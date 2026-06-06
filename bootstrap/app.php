<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'superadmin' => \App\Http\Middleware\SuperadminMiddleware::class,
            'tenant.context' => \App\Http\Middleware\SetTenantFromAuthenticatedUser::class,
            'redirect.superadmin.from.admin' => \App\Http\Middleware\RedirectSuperadminFromAdmin::class,
            'agent' => \App\Http\Middleware\AgentMiddleware::class,
            'teamleader' => \App\Http\Middleware\TeamLeaderMiddleware::class,
            'regionalmanager' => \App\Http\Middleware\RegionalManagerMiddleware::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'subadmin.ability' => \App\Http\Middleware\SubadminAbilityMiddleware::class,
            'customer.dealer' => \App\Http\Middleware\CustomerDealerMiddleware::class,
            'shop.buyer' => \App\Http\Middleware\ShopBuyerMiddleware::class,
        ]);
        // SetTenantFromAuthenticatedUser must run before SubstituteBindings so that
        // route model binding (e.g. {purchase}) can apply the correct tenant scope.
        $middleware->removeFromGroup('web', \Illuminate\Routing\Middleware\SubstituteBindings::class);
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\SetTenantFromAuthenticatedUser::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
        $middleware->removeFromGroup('api', \Illuminate\Routing\Middleware\SubstituteBindings::class);
        $middleware->appendToGroup('api', [
            \App\Http\Middleware\SetTenantFromAuthenticatedUser::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'selcom/checkout-callback',
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
