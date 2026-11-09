<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureOrganizer;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Support\ErrorHandler;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
            'admin' => EnsureAdmin::class,
            'json' => ForceJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            if ($request->routeIs('login.store', 'organizer-requests.store')) {
                $code = $request->routeIs('login.store') ? ErrorHandler::AUTH_RATE_LIMITED : ErrorHandler::ORGANIZER_REQUEST_RATE_LIMITED;

                return ErrorHandler::respond($request, $code, 429)
                    ->withHeaders($exception->getHeaders());
            }
        });

        // Couvre aussi les requêtes SQL de validation, exécutées avant les contrôleurs.
        $exceptions->render(function (QueryException $exception, Request $request) {
            if ($request->routeIs('organizer-requests.*', 'admin.organizer-requests.*')) {
                $request->headers->set('Accept', 'application/json');

                return ErrorHandler::respond($request, ErrorHandler::ORGANIZER_REQUEST_FAILED, 503);
            }
        });

        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($request->routeIs('organizer-requests.*', 'admin.organizer-requests.*')) {
                // Le CSRF peut échouer avant le middleware json du module.
                $request->headers->set('Accept', 'application/json');
                $code = match ($exception->getStatusCode()) {
                    419 => ErrorHandler::CSRF_TOKEN_MISMATCH,
                    404 => ErrorHandler::ORGANIZER_REQUEST_NOT_FOUND,
                    default => null,
                };
                if ($code !== null) {
                    return ErrorHandler::respond($request, $code, $exception->getStatusCode());
                }
            }
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson()
                || $request->routeIs('organizer-requests.*', 'admin.organizer-requests.*'),
        );
    })->create();
