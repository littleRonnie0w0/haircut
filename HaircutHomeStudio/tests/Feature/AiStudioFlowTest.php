<?php

namespace Tests\Feature;

use App\Models\AiGeneration;
use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\Reserva;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiStudioFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config()->set('ai.enabled', true);
        config()->set('ai.provider', 'fake');
        config()->set('ai.process_sync', true);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_client_sees_active_cut_and_color_services_but_not_treatments(): void
    {
        $cliente = User::factory()->create();
        $corte = Categoria::create(['nombre' => 'Cortes', 'slug' => 'corte']);
        $color = Categoria::create(['nombre' => 'Color', 'slug' => 'color']);
        $tratamiento = Categoria::create(['nombre' => 'Tratamientos', 'slug' => 'tratamientos']);
        $this->service($corte, 'Corte de damas');
        $this->service($color, 'Balayage');
        $this->service($tratamiento, 'Botox capilar');

        $this->actingAs($cliente)
            ->get(route('ai.index'))
            ->assertOk()
            ->assertSee('Simulador de estilo')
            ->assertSee('Corte de damas')
            ->assertSee('Balayage')
            ->assertDontSee('Botox capilar')
            ->assertSee('<strong>5</strong>', false)
            ->assertSee('disponibles');
    }

    public function test_unlimited_admin_can_open_studio_and_bypasses_request_throttle(): void
    {
        $admin = User::factory()->create([
            'nombre' => 'Admin Test Imagen',
            'rol' => 'admin',
            'ai_sin_limite' => true,
        ]);
        $categoria = Categoria::create(['nombre' => 'Cortes', 'slug' => 'corte']);
        $this->service($categoria, 'Corte de damas');

        $this->actingAs($admin)
            ->get(route('ai.index'))
            ->assertOk()
            ->assertSee('Cuenta de prueba sin límite de imágenes');

        foreach (range(1, 5) as $attempt) {
            $this->actingAs($admin)
                ->post(route('ai.store'), [])
                ->assertRedirect()
                ->assertSessionHasErrors('photo');
        }
    }

    public function test_client_can_generate_a_private_preview_and_other_users_cannot_see_it(): void
    {
        $cliente = User::factory()->create();
        $otroCliente = User::factory()->create();
        $categoria = Categoria::create(['nombre' => 'Color', 'slug' => 'color']);
        $servicio = $this->service($categoria, 'Balayage');

        $response = $this->actingAs($cliente)->post(route('ai.store'), [
            'photo' => $this->portrait(),
            'servicio_id' => $servicio->id,
            'consent' => '1',
        ]);

        $generation = AiGeneration::query()->firstOrFail();
        $response->assertRedirect(route('ai.show', $generation));
        $this->assertSame('completed', $generation->status);
        $this->assertSame($servicio->nombre, $generation->style_label);
        $this->assertSame('color', $generation->style_category);
        Storage::disk('local')->assertExists($generation->input_path);
        Storage::disk('local')->assertExists($generation->output_path);

        $this->actingAs($cliente)
            ->get(route('ai.image', [$generation, 'output']))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
        $this->actingAs($otroCliente)
            ->get(route('ai.image', [$generation, 'output']))
            ->assertNotFound();
    }

    public function test_a_photo_smaller_than_512_pixels_is_accepted_with_only_a_quality_recommendation(): void
    {
        $cliente = User::factory()->create();
        $categoria = Categoria::create(['nombre' => 'Cortes', 'slug' => 'corte']);
        $servicio = $this->service($categoria, 'Corte de damas');

        $this->actingAs($cliente)
            ->get(route('ai.index'))
            ->assertOk()
            ->assertSee('También puedes usar una más pequeña.');

        $response = $this->actingAs($cliente)->post(route('ai.store'), [
            'photo' => new UploadedFile(public_path('img/favicon.png'), 'small.png', 'image/png', null, true),
            'servicio_id' => $servicio->id,
            'consent' => '1',
        ]);

        $generation = AiGeneration::query()->firstOrFail();
        $response->assertRedirect(route('ai.show', $generation));
        $this->assertSame('completed', $generation->status);
        Storage::disk('local')->assertExists($generation->output_path);
    }

    public function test_identical_photo_and_service_reuse_the_result_without_consuming_quota(): void
    {
        $cliente = User::factory()->create();
        $categoria = Categoria::create(['nombre' => 'Cortes', 'slug' => 'corte']);
        $servicio = $this->service($categoria, 'Corte de damas');
        $payload = [
            'photo' => $this->portrait(),
            'servicio_id' => $servicio->id,
            'consent' => '1',
        ];

        $this->actingAs($cliente)->post(route('ai.store'), $payload)->assertRedirect();
        $first = AiGeneration::query()->firstOrFail();

        $this->actingAs($cliente)->post(route('ai.store'), [
            'photo' => $this->portrait(),
            'servicio_id' => $servicio->id,
            'consent' => '1',
        ])->assertRedirect(route('ai.show', $first));

        $this->assertSame(1, AiGeneration::query()->count());
    }

    public function test_changed_prompt_does_not_reuse_an_old_result_but_keeps_its_history(): void
    {
        $client = User::factory()->create(['ai_sin_limite' => true]);
        $category = Categoria::create(['nombre' => 'Color', 'slug' => 'color']);
        $service = $this->service($category, 'Colores fantasía');
        $this->actingAs($client);
        $this->post(route('ai.store'), [
            'photo' => $this->portrait(),
            'servicio_id' => $service->id,
            'consent' => '1',
        ])->assertSessionHasNoErrors();
        $old = AiGeneration::firstOrFail();
        $old->update(['preset' => 'style:'.substr(hash('sha256', 'color|Colores fantasía'), 0, 32)]);
        $this->post(route('ai.store'), [
            'photo' => $this->portrait(),
            'servicio_id' => $service->id,
            'consent' => '1',
        ])->assertSessionHasNoErrors();
        $current = AiGeneration::latest('id')->firstOrFail();
        $this->assertNotSame($old->id, $current->id);
        $this->assertSame('completed', $current->status);
        Storage::disk('local')->assertExists($old->output_path);

        config()->set('ai.service_prompts.color.colores fantasia', 'Preview blue fantasy color on existing hair.');
        $this->post(route('ai.store'), [
            'photo' => $this->portrait(),
            'servicio_id' => $service->id,
            'consent' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame(3, AiGeneration::count());
        $this->assertNotSame($current->preset, AiGeneration::latest('id')->firstOrFail()->preset);
    }

    public function test_completed_preview_is_copied_into_a_private_reservation_attachment(): void
    {
        Carbon::setTestNow('2026-09-10 09:00:00');
        Configuracion::create(['hora_inicio' => '10:30:00', 'hora_fin' => '19:30:00', 'dias_atencion' => '2,3,4,5,6']);
        $categoria = Categoria::create(['nombre' => 'Cortes', 'slug' => 'corte']);
        $servicio = Servicio::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Corte de damas',
            'duracion_min' => 60,
            'precio' => 15000,
            'activo' => true,
        ]);
        $cliente = User::factory()->create();
        $peluquero = User::factory()->create(['rol' => 'peluquero']);
        $otroCliente = User::factory()->create();
        $image = file_get_contents(public_path('img/collage/03.jpg'));
        Storage::disk('local')->put('ai/input/attached.jpg', $image);
        Storage::disk('local')->put('ai/output/attached.jpg', $image);
        $generation = AiGeneration::create([
            'usuario_id' => $cliente->id,
            'style_label' => $servicio->nombre,
            'style_category' => 'corte',
            'preset' => 'style:test',
            'input_path' => 'ai/input/attached.jpg',
            'output_path' => 'ai/output/attached.jpg',
            'input_hash' => hash('sha256', $image),
            'status' => 'completed',
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'completed_at' => now(),
            'expires_at' => now()->addHours(72),
        ]);

        $this->actingAs($cliente)->post(route('reservas.store'), [
            'servicio_id' => $servicio->id,
            'fecha' => '2026-09-11',
            'hora' => '10:30',
            'lugar' => 'salon',
            'ai_generation_id' => $generation->id,
        ])->assertRedirect(route('reservas.index'));

        $reserva = Reserva::query()->firstOrFail();
        $this->assertNotNull($reserva->imagen_simulada);
        $this->assertNotSame($generation->output_path, $reserva->imagen_simulada);
        Storage::disk('local')->assertExists($reserva->imagen_simulada);
        $this->assertSame($image, Storage::disk('local')->get($reserva->imagen_simulada));

        $this->actingAs($peluquero)
            ->get(route('reservas.simulated-image', $reserva))
            ->assertOk();
        $this->actingAs($otroCliente)
            ->get(route('reservas.simulated-image', $reserva))
            ->assertNotFound();
        $this->actingAs($peluquero)
            ->get(route('ai.image', [$generation, 'output']))
            ->assertNotFound();

        $generation->update(['expires_at' => now()->subMinute()]);
        $this->artisan('ai:purge')->assertSuccessful();
        Storage::disk('local')->assertMissing($generation->output_path ?? 'ai/output/attached.jpg');
        Storage::disk('local')->assertExists($reserva->imagen_simulada);
        $this->actingAs($cliente)
            ->get(route('reservas.simulated-image', $reserva))
            ->assertOk();
    }

    public function test_preview_can_be_used_as_reference_for_another_service(): void
    {
        Carbon::setTestNow('2026-09-10 09:00:00');
        Configuracion::create(['hora_inicio' => '10:30:00', 'hora_fin' => '19:30:00', 'dias_atencion' => '2,3,4,5,6']);
        $categoria = Categoria::create(['nombre' => 'Cortes', 'slug' => 'corte']);
        $simulatedService = $this->service($categoria, 'Corte de damas');
        $differentService = $this->service($categoria, 'Peinado');
        $cliente = User::factory()->create();
        $image = file_get_contents(public_path('img/collage/03.jpg'));
        Storage::disk('local')->put('ai/input/mismatch.jpg', $image);
        Storage::disk('local')->put('ai/output/mismatch.jpg', $image);
        $generation = AiGeneration::create([
            'usuario_id' => $cliente->id,
            'style_label' => $simulatedService->nombre,
            'style_category' => 'corte',
            'preset' => 'style:test',
            'input_path' => 'ai/input/mismatch.jpg',
            'output_path' => 'ai/output/mismatch.jpg',
            'input_hash' => hash('sha256', $image),
            'status' => 'completed',
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'completed_at' => now(),
            'expires_at' => now()->addHours(72),
        ]);

        $this->actingAs($cliente)->post(route('reservas.store'), [
            'servicio_id' => $differentService->id,
            'fecha' => '2026-09-11',
            'hora' => '10:30',
            'lugar' => 'salon',
            'ai_generation_id' => $generation->id,
        ])->assertRedirect(route('reservas.index'));

        $reserva = Reserva::query()->firstOrFail();
        $this->assertSame($differentService->id, $reserva->servicio_id);
        Storage::disk('local')->assertExists($reserva->imagen_simulada);
    }

    public function test_client_cannot_attach_another_users_preview(): void
    {
        Carbon::setTestNow('2026-09-10 09:00:00');
        Configuracion::create(['hora_inicio' => '10:30:00', 'hora_fin' => '19:30:00', 'dias_atencion' => '2,3,4,5,6']);
        $categoria = Categoria::create(['nombre' => 'Cortes', 'slug' => 'corte']);
        $servicio = $this->service($categoria, 'Corte de damas');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $image = file_get_contents(public_path('img/collage/03.jpg'));
        Storage::disk('local')->put('ai/output/private.jpg', $image);
        $generation = AiGeneration::create([
            'usuario_id' => $owner->id,
            'style_label' => 'Corte de damas',
            'style_category' => 'corte',
            'preset' => 'style:test',
            'output_path' => 'ai/output/private.jpg',
            'input_hash' => hash('sha256', $image),
            'status' => 'completed',
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'completed_at' => now(),
            'expires_at' => now()->addHours(72),
        ]);

        $this->actingAs($other)->from(route('reservas.create'))->post(route('reservas.store'), [
            'servicio_id' => $servicio->id,
            'fecha' => '2026-09-11',
            'hora' => '10:30',
            'lugar' => 'salon',
            'ai_generation_id' => $generation->id,
        ])->assertRedirect(route('reservas.create'))->assertSessionHasErrors('ai_generation_id');

        $this->assertDatabaseCount('reservas', 0);
    }

    private function portrait(): UploadedFile
    {
        return new UploadedFile(
            public_path('img/collage/03.jpg'),
            'portrait.jpg',
            'image/jpeg',
            null,
            true,
        );
    }

    private function service(Categoria $categoria, string $nombre): Servicio
    {
        return Servicio::create([
            'categoria_id' => $categoria->id,
            'nombre' => $nombre,
            'duracion_min' => 60,
            'precio' => 15000,
            'activo' => true,
        ]);
    }
}
