<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Obtiene el usuario autenticado de la solicitud.
        $user = $request->user();

        // Verifica si no hay un usuario autenticado o si el rol del usuario
        // no está incluido en la lista de roles permitidos.
        if (!$user || !in_array($user->role, $roles)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return $next($request);
    }
}