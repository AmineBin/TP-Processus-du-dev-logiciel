<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Les nouveaux endpoints backend répondent en JSON, même sans header Accept du client. */
class ForceJsonResponse
{
    /** Ne change ni le type du corps entrant ni les protections de session/CSRF. */
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
