<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

/**
 * Envío de correos (Resend / mail / log) con plantilla de marca.
 */
class MessagingService
{
    private ?string $lastError = null;

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /** @param  array<string, mixed>  $meta */
    public function send(string $to, string $subject, string $body, array $meta = []): bool
    {
        $html = $this->wrapPlainBody($subject, $body, $meta);

        return $this->dispatch($to, $subject, $html, $body, $meta);
    }

    /**
     * @param  list<string>  $lines
     * @param  array<string, mixed>  $meta
     */
    public function sendBrand(
        string $to,
        string $subject,
        string $greeting,
        array $lines,
        ?string $ctaUrl = null,
        ?string $ctaLabel = null,
        ?string $infoHtml = null,
        array $meta = [],
    ): bool {
        $html = View::make('emails.brand', [
            'title' => 'HairCut Home Studio',
            'greeting' => $greeting,
            'lines' => $lines,
            'ctaUrl' => $ctaUrl,
            'ctaLabel' => $ctaLabel,
            'infoHtml' => $infoHtml,
        ])->render();

        $text = $greeting."\n\n".implode("\n\n", $lines);
        if ($ctaUrl) {
            $text .= "\n\n{$ctaLabel}: {$ctaUrl}";
        }

        return $this->dispatch($to, $subject, $html, $text, $meta);
    }

    /** @param  array<string, mixed>  $meta */
    private function dispatch(string $to, string $subject, string $html, string $text, array $meta = []): bool
    {
        $this->lastError = null;
        $driver = (string) config('messaging.driver', 'resend');

        try {
            return match ($driver) {
                'http' => $this->sendHttp($to, $subject, $html, $text, $meta),
                'log' => $this->sendLog($to, $subject, $text, $meta),
                default => $this->sendMail($to, $subject, $html, $text),
            };
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::warning('MessagingService falló', [
                'to' => $to,
                'driver' => $driver,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** @param  array<string, mixed>  $meta */
    private function wrapPlainBody(string $subject, string $body, array $meta): string
    {
        $nombre = (string) ($meta['nombre'] ?? '');
        $greeting = $nombre !== '' ? "Hola {$nombre}," : 'Hola,';
        $lines = array_values(array_filter(array_map('trim', preg_split("/\n+/", $body) ?: [])));

        return View::make('emails.brand', [
            'title' => 'HairCut Home Studio',
            'greeting' => $greeting,
            'lines' => $lines !== [] ? $lines : [$body],
            'ctaUrl' => $meta['cta_url'] ?? null,
            'ctaLabel' => $meta['cta_label'] ?? null,
            'infoHtml' => $meta['info_html'] ?? null,
        ])->render();
    }

    /** @param  array<string, mixed>  $meta */
    private function sendLog(string $to, string $subject, string $body, array $meta): bool
    {
        Log::info('Mensaje (driver=log)', [
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'meta' => $meta,
        ]);

        return true;
    }

    private function sendMail(string $to, string $subject, string $html, string $text): bool
    {
        Mail::html($html, function ($message) use ($to, $subject, $text): void {
            $message->to($to)
                ->from(
                    (string) config('messaging.from'),
                    (string) config('messaging.from_name'),
                )
                ->subject($subject)
                ->text($text);
        });

        return true;
    }

    /** @param  array<string, mixed>  $meta */
    private function sendHttp(string $to, string $subject, string $html, string $text, array $meta): bool
    {
        $apiKey = trim((string) config('messaging.api_key'));
        $apiUrl = trim((string) config('messaging.api_url', 'https://api.resend.com/emails'));

        if ($apiKey === '') {
            $this->lastError = 'Falta la API key de mensajería.';
            Log::warning('MESSAGING_API_KEY vacío; se usa log.');

            return $this->sendLog($to, $subject, $text, $meta);
        }

        $fromName = (string) config('messaging.from_name');
        $fromEmail = (string) config('messaging.from');
        $overrideTo = strtolower(trim((string) config('messaging.override_to', '')));
        $destination = strtolower(trim($to));
        $finalSubject = $subject;

        // Sin dominio verificado Resend solo acepta el correo de la cuenta.
        if ($overrideTo !== '' && $overrideTo !== $destination) {
            $finalSubject = "[para: {$destination}] {$subject}";
            $destination = $overrideTo;
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(20)
            ->post($apiUrl !== '' ? $apiUrl : 'https://api.resend.com/emails', [
                'from' => "{$fromName} <{$fromEmail}>",
                'to' => [$destination],
                'subject' => $finalSubject,
                'html' => $html,
                'text' => $text,
            ]);

        if (! $response->successful()) {
            $payload = $response->json();
            $message = is_array($payload)
                ? (string) ($payload['message'] ?? $response->body())
                : $response->body();
            $this->lastError = $message !== '' ? $message : 'No se pudo enviar el correo.';

            Log::warning('Resend respondió error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'to' => $to,
                'delivered_to' => $destination,
            ]);

            return false;
        }

        return true;
    }
}
