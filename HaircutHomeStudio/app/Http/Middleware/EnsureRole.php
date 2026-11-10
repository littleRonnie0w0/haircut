<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return redirect()->route('login');
        }

        if ($usuario->puedeAccederComo(...$roles)) {
            return $next($request);
        }

        return redirect()->to($usuario->rutaInicio());
    }
}
