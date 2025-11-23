<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour vérifier les rôles des utilisateurs
 */
class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Vérifier que l'utilisateur est authentifié
        if (!auth('api')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentification requise'
            ], 401);
        }

        $user = auth('api')->user();

        // Vérifier que l'utilisateur a un des rôles requis
        if (!in_array($user->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Rôle insuffisant.'
            ], 403);
        }

        return $next($request);
    }
}
