<?php

namespace App\Http\Middleware;

use App\Support\ErrorHandler;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Autorisation partagée par l’affichage et l’enregistrement des tournois. */
class EnsureOrganizer
{
    /** Le groupe active/auth a déjà contrôlé la session et l’état du compte. */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isOrganizer()) {
            return ErrorHandler::respond($request, ErrorHandler::TOURNAMENT_FORBIDDEN, 403, 'home');
        }

        return $next($request);
    }
}
