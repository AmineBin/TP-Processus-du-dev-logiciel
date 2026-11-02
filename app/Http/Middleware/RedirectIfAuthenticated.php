<?php

namespace App\Http\Middleware;

use App\Support\ErrorHandler;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Réserve les formulaires de connexion et d’inscription aux visiteurs. */
class RedirectIfAuthenticated
{
    /** Redirige un utilisateur connecté vers l’accueil, ou répond en JSON. */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            if ($request->expectsJson()) {
                return ErrorHandler::respond($request, ErrorHandler::AUTH_ALREADY_AUTHENTICATED, 409);
            }

            return redirect()->route('home');
        }

        return $next($request);
    }
}
