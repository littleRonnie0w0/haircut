<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();
        if (! $usuario || $usuario->estaActiva()) {
            return $next($request);
        }

        if ($request->routeIs('home', 'productos.*', 'logout')) {
            return $next($request);
        }

        return redirect()
            ->route('home')
            ->with('error', 'Tu cuenta está desactivada. Solo puedes ver Inicio y Productos.');
    }
}
