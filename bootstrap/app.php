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
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'perm' => \App\Http\Middleware\RequirePermission::class,
        ]);
        // Razorpay webhooks are server-to-server and verified by HMAC signature instead of CSRF.
        $middleware->validateCsrfTokens(except: ['webhooks/razorpay']);
        $middleware->redirectGuestsTo(fn ($request) => $request->is('admin*') ? route('admin.login') : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
