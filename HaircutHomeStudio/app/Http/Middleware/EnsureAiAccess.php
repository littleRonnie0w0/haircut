<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAiAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return redirect()->route('login');
        }

        if (! $usuario->puedeUsarSimulador()) {
            abort(403, 'No tienes acceso al simulador.');
        }

        return $next($request);
    }
}
