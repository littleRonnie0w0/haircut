<?php

namespace Tests\Feature;

use App\Models\AiGeneration;
use App\Models\Categoria;
use App\Models\Reserva;
use App\Models\Servicio;
use App\Models\User;
use App\Services\AiPromptService;
use Database\Seeders\CatalogoServiciosSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_matches_the_complete_professional_tariff(): void
    {
        $this->seed(CatalogoServiciosSeeder::class);
        $expected = [
            'Corte de hombre' => [8000, false, 'corte'],
            'Corte de mujer' => [8000, false, 'corte'],
            'Lavado' => [4000, false, 'corte'],
            'Lavado y secado' => [8000, false, 'corte'],
            'Tintura de retoque' => [30000, false, 'color'],
            'Retoque de tintura + baño de color' => [35000, false, 'color'],
            'Balayage' => [80000, true, 'color'],
            'Babylights' => [80000, true, 'color'],
            'Mechas' => [45000, true, 'color'],
            'Visos' => [45000, true, 'color'],
            'Reflejos' => [40000, true, 'color'],
            'Colores fantasía' => [30000, true, 'color'],
            'Liso permanente' => [50000, false, 'tratamientos'],
            'Alisado Brasil Cacau' => [85000, true, 'tratamientos'],
            'Botox capilar' => [30000, false, 'tratamientos'],
            'Masaje de queratina' => [20000, false, 'tratamientos'],
            'Tratamiento Olaplex' => [35000, false, 'tratamientos'],
        ];
        $services = Servicio::with('categoria')->where('activo', true)->get()->keyBy('nombre');
        $this->assertCount(17, $services);
        foreach ($expected as $name => [$price, $from, $category]) {
            $service = $services->get($name);
            $this->assertNotNull($service, $name);
            $this->assertSame($price, (int) $service->precio, $name);
            $this->assertSame($from, $service->precio_desde, $name);
            $this->assertSame($category, $service->categoria->slug, $name);
        }
        $this->seed(CatalogoServiciosSeeder::class);
        $this->assertSame(17, Servicio::count());
    }

    public function test_migration_preserves_existing_reservations_and_deactivates_obsolete_services(): void
    {
        $category = Categoria::create(['nombre' => 'Cortes', 'slug' => 'corte']);
        $oldCut = Servicio::create(['categoria_id' => $category->id, 'nombre' => 'Corte de damas', 'duracion_min' => 90, 'precio' => 15000]);
        $obsolete = Servicio::create(['categoria_id' => $category->id, 'nombre' => 'Corte infantil', 'duracion_min' => 60, 'precio' => 12000]);
        $reservation = Reserva::create([
            'usuario_id' => User::factory()->create()->id,
            'servicio_id' => $obsolete->id,
            'fecha' => now()->addDay()->toDateString(),
            'hora' => '10:30',
            'estado' => 'confirmada',
            'foto' => 'fotos/existing.jpg',
        ]);
        Schema::table('servicios', fn (Blueprint $table) => $table->dropColumn('precio_desde'));
        $migration = require database_path('migrations/2026_10_11_000000_update_service_tariff.php');
        $migration->up();

        $this->assertSame('Corte de mujer', $oldCut->fresh()->nombre);
        $this->assertSame(90, $oldCut->fresh()->duracion_min);
        $this->assertFalse($obsolete->fresh()->activo);
        $this->assertSame($obsolete->id, $reservation->fresh()->servicio_id);
        $this->assertSame('fotos/existing.jpg', $reservation->fresh()->foto);
        $this->assertSame('Corte infantil', $reservation->fresh()->servicio->nombre);
        $this->assertSame(17, Servicio::where('activo', true)->count());
    }

    public function test_variable_prices_are_visible_in_booking_summary_and_admin(): void
    {
        $this->seed();
        $service = Servicio::where('nombre', 'Balayage')->firstOrFail();
        $admin = User::factory()->create(['rol' => 'admin']);
        $this->actingAs($admin)->get(route('admin.servicios.index'))
            ->assertOk()->assertSee('Desde $80.000');
        $client = User::factory()->create();
        $this->actingAs($client)->get(route('reservas.create', ['serv' => $service->id, 'paso' => 'resumen']))
            ->assertOk()->assertSee('Desde $80.000')
            ->assertSee('la cantidad de cabello y la técnica requerida');
    }

    public function test_admin_can_enable_and_disable_variable_pricing(): void
    {
        $this->seed(CatalogoServiciosSeeder::class);
        $service = Servicio::where('nombre', 'Balayage')->firstOrFail();
        $this->actingAs(User::factory()->create(['rol' => 'admin']));
        $payload = [
            'categoria_id' => $service->categoria_id,
            'nombre' => $service->nombre,
            'duracion_min' => $service->duracion_min,
            'precio' => 80000,
            'activo' => '1',
        ];
        $this->put(route('admin.servicios.update', $service), $payload)
            ->assertSessionHas('success');
        $this->assertFalse($service->fresh()->precio_desde);
        $this->put(route('admin.servicios.update', $service), $payload + ['precio_desde' => '1'])
            ->assertSessionHas('success');
        $this->assertTrue($service->fresh()->precio_desde);
    }

    public function test_every_visual_service_has_a_specific_prompt_and_generates_in_demo_mode(): void
    {
        Storage::fake('local');
        config()->set(['ai.enabled' => true, 'ai.provider' => 'fake', 'ai.process_sync' => true]);
        $this->seed(CatalogoServiciosSeeder::class);
        $this->actingAs(User::factory()->create(['rol' => 'admin', 'ai_sin_limite' => true]));
        $services = Servicio::simulables()->get();
        $this->assertCount(11, $services);
        foreach ($services as $service) {
            $prompt = app(AiPromptService::class)->forService($service);
            $this->assertStringContainsString('Edit only', $prompt);
            $this->assertStringNotContainsString('corresponding to the catalog service', $prompt, $service->nombre);
            $this->post(route('ai.store'), [
                'photo' => new UploadedFile(public_path('img/favicon.png'), 'portrait.png', 'image/png', null, true),
                'servicio_id' => $service->id,
                'consent' => '1',
            ])->assertSessionHasNoErrors()->assertRedirect();
            $generation = AiGeneration::latest('id')->firstOrFail();
            $this->assertSame($service->nombre, $generation->style_label);
            $this->assertSame('completed', $generation->status);
            Storage::disk('local')->assertExists($generation->output_path);
        }
        $this->assertSame(11, AiGeneration::count());
        $fantasy = Servicio::where('nombre', 'Colores fantasía')->firstOrFail();
        $this->assertStringContainsString('violet and magenta', app(AiPromptService::class)->forService($fantasy));
    }

    public function test_wash_only_and_treatments_cannot_be_simulated(): void
    {
        $this->seed(CatalogoServiciosSeeder::class);
        config()->set(['ai.enabled' => true, 'ai.provider' => 'fake']);
        $this->actingAs(User::factory()->create(['ai_sin_limite' => true]));
        $this->get(route('ai.index'))->assertOk()->assertSee('Colores fantasía')
            ->assertDontSee('Alisado Brasil Cacau');
        foreach (['Lavado', 'Botox capilar'] as $name) {
            $this->post(route('ai.store'), [
                'photo' => new UploadedFile(public_path('img/favicon.png'), 'portrait.png', 'image/png', null, true),
                'servicio_id' => Servicio::where('nombre', $name)->value('id'),
                'consent' => '1',
            ])->assertSessionHasErrors('servicio_id');
        }
        $this->assertSame(0, AiGeneration::count());
    }
}
