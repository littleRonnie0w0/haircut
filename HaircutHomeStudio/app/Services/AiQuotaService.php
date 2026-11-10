<?php

namespace App\Services;

use App\Models\AiGeneration;
use App\Models\User;

class AiQuotaService
{
    /** @return array{allowed: bool, unlimited: bool, remaining_24h: ?int, remaining_30d: ?int, reason: ?string} */
    public function status(User $usuario): array
    {
        $processing = AiGeneration::query()
            ->delUsuario($usuario)
            ->whereIn('status', ['pending', 'processing'])
            ->where('created_at', '>=', now()->subMinutes(10))
            ->exists();

        if ($usuario->ai_sin_limite) {
            return [
                'allowed' => ! $processing,
                'unlimited' => true,
                'remaining_24h' => null,
                'remaining_30d' => null,
                'reason' => $processing ? 'Ya tienes una simulación en proceso.' : null,
            ];
        }

        $dailyLimit = max(0, (int) config('ai.limits.per_24_hours'));
        $monthlyLimit = max(0, (int) config('ai.limits.per_30_days'));
        $globalDailyLimit = max(0, (int) config('ai.limits.global_per_day'));
        $globalMonthlyLimit = max(0, (int) config('ai.limits.global_per_month'));

        $dailyUsed = AiGeneration::query()
            ->delUsuario($usuario)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
        $monthlyUsed = AiGeneration::query()
            ->delUsuario($usuario)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        $globalDailyUsed = AiGeneration::query()
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
        $globalMonthlyUsed = AiGeneration::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        $reason = match (true) {
            $processing => 'Ya tienes una simulación en proceso.',
            $dailyLimit === 0 || $dailyUsed >= $dailyLimit => 'Alcanzaste el límite de simulaciones de las últimas 24 horas.',
            $monthlyLimit === 0 || $monthlyUsed >= $monthlyLimit => 'Alcanzaste el límite de simulaciones de los últimos 30 días.',
            $globalDailyLimit === 0 || $globalDailyUsed >= $globalDailyLimit => 'El cupo diario del simulador se agotó. Intenta mañana.',
            $globalMonthlyLimit === 0 || $globalMonthlyUsed >= $globalMonthlyLimit => 'El cupo mensual del simulador se agotó.',
            default => null,
        };

        return [
            'allowed' => $reason === null,
            'unlimited' => false,
            'remaining_24h' => max(0, $dailyLimit - $dailyUsed),
            'remaining_30d' => max(0, $monthlyLimit - $monthlyUsed),
            'reason' => $reason,
        ];
    }
}
