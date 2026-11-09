<?php

namespace App\Http\Middleware;

use App\Support\ErrorHandler;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Réserve les endpoints de traitement aux administrateurs, jamais aux organisateurs. */
class EnsureAdmin
{
    /** active/auth ont déjà vérifié la session et l’état du compte. */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            return ErrorHandler::respond($request, ErrorHandler::ADMIN_REQUIRED, 403, 'home');
        }

        return $next($request);
    }
}
