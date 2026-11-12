<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureAiAccess;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\NoSessionCache;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->appendToGroup('web', EnsureAccountActive::class);
        $middleware->redirectUsersTo(function (Request $request) {
            $usuario = $request->user();

            return $usuario ? $usuario->rutaInicio() : '/';
        });
        $middleware->alias([
            'role' => EnsureRole::class,
            'ai.access' => EnsureAiAccess::class,
            'session.cache' => NoSessionCache::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'La sesión expiró. Recarga la página e inténtalo de nuevo.',
                ], 419);
            }

            return redirect()
                ->route('login')
                ->with('error', 'La sesión expiró. Vuelve a iniciar sesión.');
        });

        // Laravel a veces arma la página 419 antes del render(); este respond() la convierte a redirect.
        $exceptions->respond(function (Response $response, $e, Request $request) {
            if ($response->getStatusCode() !== 419) {
                return $response;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'La sesión expiró. Recarga la página e inténtalo de nuevo.',
                ], 419);
            }

            return redirect()
                ->route('login')
                ->with('error', 'La sesión expiró. Vuelve a iniciar sesión.');
        });
    })->create();
