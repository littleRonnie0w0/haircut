<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Tokens de restablecimiento en JSON (sin alterar el esquema de BD).
 */
class PasswordResetStore
{
    private string $path;

    public function __construct()
    {
        $this->path = storage_path('app/password_resets.json');
    }

    public function put(string $email, string $tokenPlain, int $minutes = 60): void
    {
        $email = strtolower(trim($email));
        $data = $this->all();
        $data[$email] = [
            'token_hash' => hash('sha256', $tokenPlain),
            'expires_at' => now()->addMinutes($minutes)->toIso8601String(),
        ];
        $this->write($data);
    }

    public function consume(string $email, string $tokenPlain): bool
    {
        $email = strtolower(trim($email));
        $data = $this->all();
        $row = $data[$email] ?? null;

        if (! is_array($row)) {
            return false;
        }

        $expires = isset($row['expires_at']) ? strtotime((string) $row['expires_at']) : false;
        if ($expires === false || $expires < time()) {
            unset($data[$email]);
            $this->write($data);

            return false;
        }

        $ok = hash_equals((string) ($row['token_hash'] ?? ''), hash('sha256', $tokenPlain));
        if ($ok) {
            unset($data[$email]);
            $this->write($data);
        }

        return $ok;
    }

    /** @return array<string, array{token_hash: string, expires_at: string}> */
    private function all(): array
    {
        if (! File::exists($this->path)) {
            return [];
        }

        $decoded = json_decode((string) File::get($this->path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @param  array<string, array{token_hash: string, expires_at: string}>  $data */
    private function write(array $data): void
    {
        File::ensureDirectoryExists(dirname($this->path));
        File::put($this->path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
