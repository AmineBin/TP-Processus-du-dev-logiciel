<?php

namespace App\Http\Middleware;

use App\Support\ErrorHandler;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Révoque aussi l’accès lorsqu’un compte est désactivé après sa connexion. */
class EnsureActiveAccount
{
    /** Contrôle l’état du compte avant les middlewares auth et guest. */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->est_actif) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return ErrorHandler::respond($request, ErrorHandler::AUTH_INACTIVE, 403);
        }

        return $next($request);
    }
}
