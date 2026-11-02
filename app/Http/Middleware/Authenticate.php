<?php

namespace App\Http\Middleware;

use App\Support\ErrorHandler;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Protège les routes utilisant le guard de session Laravel configuré. */
class Authenticate
{
    /** Continue si le client est connecté ; sinon renvoie AUTH_REQUIRED. */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            // Seules les pages GET peuvent être reprises après la connexion.
            if (! $request->expectsJson() && $request->isMethod('GET')) {
                $request->session()->put('url.intended', $request->fullUrl());
            }

            return ErrorHandler::respond($request, ErrorHandler::AUTH_REQUIRED, 401);
        }

        return $next($request);
    }
}
