<?php

namespace Tests\Feature;

use App\Models\AiGeneration;
use App\Models\User;
use App\Services\AiQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiQuotaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_limited_to_three_generations_in_24_hours(): void
    {
        $usuario = User::factory()->create();
        $this->createGenerations($usuario, 3, now()->subHour());

        $status = app(AiQuotaService::class)->status($usuario);

        $this->assertFalse($status['allowed']);
        $this->assertSame(0, $status['remaining_24h']);
        $this->assertSame(2, $status['remaining_30d']);
    }

    public function test_user_is_limited_to_five_generations_in_30_days(): void
    {
        $usuario = User::factory()->create();
        $this->createGenerations($usuario, 5, now()->subDays(2));

        $status = app(AiQuotaService::class)->status($usuario);

        $this->assertFalse($status['allowed']);
        $this->assertSame(0, $status['remaining_30d']);
        $this->assertStringContainsString('30 días', $status['reason']);
    }

    public function test_failed_generations_still_consume_quota(): void
    {
        $usuario = User::factory()->create();
        $this->createGenerations($usuario, 5, now()->subHour(), 'failed');

        $status = app(AiQuotaService::class)->status($usuario);

        $this->assertFalse($status['allowed']);
        $this->assertSame(0, $status['remaining_24h']);
        $this->assertSame(0, $status['remaining_30d']);
    }

    public function test_unlimited_admin_bypasses_user_and_global_quotas(): void
    {
        config()->set('ai.limits.global_per_day', 1);
        config()->set('ai.limits.global_per_month', 1);
        $admin = User::factory()->create([
            'rol' => 'admin',
            'ai_sin_limite' => true,
        ]);
        $this->createGenerations($admin, 8, now()->subHour());

        $status = app(AiQuotaService::class)->status($admin);

        $this->assertTrue($status['allowed']);
        $this->assertTrue($status['unlimited']);
        $this->assertNull($status['remaining_24h']);
        $this->assertNull($status['remaining_30d']);
        $this->assertNull($status['reason']);
    }

    private function createGenerations(User $usuario, int $count, $createdAt, string $status = 'completed'): void
    {
        foreach (range(1, $count) as $index) {
            $generation = AiGeneration::create([
                'usuario_id' => $usuario->id,
                'preset' => 'balayage_miel',
                'input_path' => "ai/input/{$index}.jpg",
                'output_path' => $status === 'completed' ? "ai/output/{$index}.jpg" : null,
                'input_hash' => hash('sha256', "input-{$index}"),
                'status' => $status,
                'ip_hash' => hash('sha256', '127.0.0.1'),
                'completed_at' => $status === 'completed' ? $createdAt : null,
                'expires_at' => now()->addHours(72),
            ]);
            $generation->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
        }
    }
}
