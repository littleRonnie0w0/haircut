<?php

namespace App\Providers;

use App\Contracts\AiImageProvider;
use App\Services\AvailabilityStore;
use App\Services\FakeAiImageProvider;
use App\Services\GeminiImageProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */

    public function register(): void
    {
        $this->app->bind(AiImageProvider::class, function ($app): AiImageProvider {
            return config('ai.provider') === 'fake'
                ? $app->make(FakeAiImageProvider::class)
                : $app->make(GeminiImageProvider::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(static fn () => Password::min(8)
            ->mixedCase()
            ->numbers());

        $appUrl = (string) config('app.url');
        if (str_starts_with($appUrl, 'https://')) {
            $appHost = parse_url($appUrl, PHP_URL_HOST);
            $requestHost = request()->getHost();
            if ($appHost && $requestHost && strcasecmp((string) $appHost, (string) $requestHost) === 0) {
                URL::forceScheme('https');
            }
        }

        RateLimiter::for('ai-generations', function (Request $request) {
            if ($request->user()?->ai_sin_limite) {
                return Limit::none();
            }

            return [
                Limit::perMinute(2)->by('ai-user:'.($request->user()?->id ?? 'guest')),
                Limit::perMinute(4)->by('ai-ip:'.$request->ip()),
            ];
        });

        View::composer('layouts.app', function ($view): void {
            $data = app(AvailabilityStore::class)->all();
            $view->with('layoutConfig', (object) [
                'hora_inicio' => $data['hora_inicio'],
                'hora_fin' => $data['hora_fin'],
                'dias_atencion' => $data['dias_atencion'],
            ]);
        });
    }
}
