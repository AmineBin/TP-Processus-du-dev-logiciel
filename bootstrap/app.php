<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureOrganizer;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Support\ErrorHandler;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Remplace les alias standards pour partager les codes d’erreur du projet.
        $middleware->alias([
            'auth' => Authenticate::class,
            'guest' => RedirectIfAuthenticated::class,
            'active' => EnsureActiveAccount::class,
            'organizer' => EnsureOrganizer::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            if ($request->routeIs('login.store')) {
                return ErrorHandler::respond($request, ErrorHandler::AUTH_RATE_LIMITED, 429)
                    ->withHeaders($exception->getHeaders());
            }
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
