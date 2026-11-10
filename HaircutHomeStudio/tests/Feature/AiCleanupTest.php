<?php

namespace Tests\Feature;

use App\Models\AiGeneration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_private_images_are_deleted_without_removing_usage_record(): void
    {
        Storage::fake('local');
        $usuario = User::factory()->create();
        Storage::disk('local')->put('ai/input/expired.jpg', 'input');
        Storage::disk('local')->put('ai/output/expired.jpg', 'output');
        $generation = AiGeneration::create([
            'usuario_id' => $usuario->id,
            'preset' => 'bob',
            'input_path' => 'ai/input/expired.jpg',
            'output_path' => 'ai/output/expired.jpg',
            'input_hash' => hash('sha256', 'input'),
            'status' => 'completed',
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'completed_at' => now()->subDays(4),
            'expires_at' => now()->subMinute(),
        ]);

        $this->artisan('ai:purge')->assertSuccessful();

        $generation->refresh();
        $this->assertSame('expired', $generation->status);
        $this->assertNull($generation->input_path);
        $this->assertNull($generation->output_path);
        Storage::disk('local')->assertMissing('ai/input/expired.jpg');
        Storage::disk('local')->assertMissing('ai/output/expired.jpg');
        $this->assertDatabaseHas('ai_generations', ['id' => $generation->id]);
    }
}
