<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Séparation stricte des rôles : un rôle n'accède qu'à ses propres fonctionnalités. */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            if ($request->expectsJson()) {
                abort(403, 'Accès refusé pour votre rôle.');
            }
            return redirect('/tableau-de-bord')->with('error', "Cette section n'est pas accessible avec votre rôle.");
        }

        return $next($request);
    }
}
