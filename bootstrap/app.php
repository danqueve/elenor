<?php

use App\Http\Middleware\EnsureUserHasPhone;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Signed-in visitors that open a guest-only page (e.g. the login) go to their account.
        $middleware->redirectUsersTo(fn () => route('account'));

        // Routes that need a contact phone (the checkout) use the "phone" alias.
        $middleware->alias(['phone' => EnsureUserHasPhone::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
