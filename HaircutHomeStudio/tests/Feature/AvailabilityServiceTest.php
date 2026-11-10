<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\DiaOff;
use App\Models\MesVisible;
use App\Models\Reserva;
use App\Models\Servicio;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_saturday_closes_at_1430(): void
    {
        Carbon::setTestNow('2026-09-10 09:00:00');
        $configuracion = Configuracion::create([
            'hora_inicio' => '10:30:00',
            'hora_fin' => '19:30:00',
            'dias_atencion' => '2,3,4,5,6',
        ]);
        MesVisible::create(['configuracion_id' => $configuracion->id, 'anio' => 2026, 'mes' => 9]);
        $servicio = $this->servicio('Cortes', 'corte', 60);

        $slots = app(AvailabilityService::class)->slotsDisponibles('2026-09-12', $servicio);

        $this->assertSame(['10:30', '11:30', '12:30', '13:30'], $slots);
    }

    public function test_reserved_hours_block_any_other_person(): void
    {
        Carbon::setTestNow('2026-09-10 09:00:00');
        $configuracion = Configuracion::create([
            'hora_inicio' => '10:30:00',
            'hora_fin' => '19:30:00',
            'dias_atencion' => '2,3,4,5,6',
        ]);
        MesVisible::create(['configuracion_id' => $configuracion->id, 'anio' => 2026, 'mes' => 9]);
        $color = $this->servicio('Color', 'color', 180);
        $corte = $this->servicio('Cortes', 'corte', 60);
        $usuario = User::factory()->create();
        Reserva::create([
            'usuario_id' => $usuario->id,
            'servicio_id' => $color->id,
            'fecha' => '2026-09-11',
            'hora' => '10:30',
            'estado' => 'confirmada',
        ]);

        $slots = app(AvailabilityService::class)->slotsDisponibles('2026-09-11', $corte);

        $this->assertNotContains('10:30', $slots);
        $this->assertNotContains('11:30', $slots);
        $this->assertNotContains('12:30', $slots);
    }

    public function test_days_off_have_no_available_slots(): void
    {
        Carbon::setTestNow('2026-09-10 09:00:00');
        $configuracion = Configuracion::create([
            'hora_inicio' => '10:30:00',
            'hora_fin' => '19:30:00',
            'dias_atencion' => '2,3,4,5,6',
        ]);
        DiaOff::create(['configuracion_id' => $configuracion->id, 'fecha' => '2026-09-11']);
        $servicio = $this->servicio('Cortes', 'corte', 60);

        $this->assertSame([], app(AvailabilityService::class)->slotsDisponibles('2026-09-11', $servicio));
    }

    private function servicio(string $categoria, string $slug, int $duracion): Servicio
    {
        $categoriaModel = Categoria::firstOrCreate(['slug' => $slug], ['nombre' => $categoria]);

        return Servicio::create([
            'categoria_id' => $categoriaModel->id,
            'nombre' => "Servicio {$categoria}",
            'duracion_min' => $duracion,
            'precio' => 10000,
            'activo' => true,
        ]);
    }
}
