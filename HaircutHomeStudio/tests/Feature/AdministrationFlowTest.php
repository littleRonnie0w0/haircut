<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\DiaOff;
use App\Models\MesVisible;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdministrationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_create_and_update_catalog_records(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);

        $this->actingAs($admin)->post(route('admin.categorias.store'), [
            'nombre' => 'Spa capilar',
        ])->assertSessionHas('success');

        $categoria = Categoria::query()->where('slug', 'spa-capilar')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.servicios.store'), [
            'categoria_id' => $categoria->id,
            'nombre' => 'Hidratación profunda',
            'descripcion' => 'Tratamiento nutritivo',
            'duracion_min' => 90,
            'precio' => 25000,
        ])->assertSessionHas('success');

        $servicio = Servicio::query()->where('nombre', 'Hidratación profunda')->firstOrFail();
        $this->assertTrue($servicio->activo);
        $this->assertSame(90, $servicio->duracion_min);

        $this->actingAs($admin)->post(route('admin.catalogo.store'), [
            'categoria_id' => $categoria->id,
            'nombre' => 'Mascarilla nutritiva',
            'descripcion' => 'Venta presencial',
            'precio' => 15990,
        ])->assertSessionHas('success');

        $producto = Producto::query()->where('nombre', 'Mascarilla nutritiva')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.catalogo.update', $producto), [
            'categoria_id' => $categoria->id,
            'nombre' => 'Mascarilla nutritiva',
            'descripcion' => 'Venta presencial',
            'precio' => 16990,
            'orden' => 2,
        ])->assertSessionHas('success');

        $this->assertFalse($producto->fresh()->activo);
        $this->assertSame('16990.00', $producto->fresh()->precio);
    }

    public function test_hairdresser_can_update_schedule_days_off_and_visible_months(): void
    {
        Carbon::setTestNow('2026-09-10 09:00:00');
        $peluquero = User::factory()->create(['rol' => 'peluquero']);

        $this->actingAs($peluquero)->put(route('peluquero.disponibilidad.update'), [
            'anio' => '2026',
            'mes' => '10',
            'hora_inicio' => '09:30',
            'hora_fin' => '18:30',
            'dias' => ['2', '3', '4', '5', '6'],
            'off' => ['14'],
            'meses_visibles' => ['2026-10', '2026-11'],
        ])->assertRedirect(route('peluquero.disponibilidad.edit', ['anio' => 2026, 'mes' => 10]));

        $configuracion = Configuracion::query()->firstOrFail();
        $this->assertSame('09:30:00', $configuracion->hora_inicio);
        $this->assertSame('18:30:00', $configuracion->hora_fin);
        $this->assertTrue(DiaOff::query()->whereDate('fecha', '2026-10-14')->exists());
        $this->assertTrue(MesVisible::query()->where('anio', 2026)->where('mes', 9)->exists());
        $this->assertTrue(MesVisible::query()->where('anio', 2026)->where('mes', 11)->exists());
    }

    public function test_saving_days_off_only_replaces_the_selected_month_and_blocks_booking(): void
    {
        Carbon::setTestNow('2027-02-01 09:00:00');
        $configuration = Configuracion::create(['hora_inicio' => '10:30:00', 'hora_fin' => '19:30:00', 'dias_atencion' => '2,3,4,5,6']);
        DiaOff::create(['configuracion_id' => $configuration->id, 'fecha' => '2027-01-20']);
        DiaOff::create(['configuracion_id' => $configuration->id, 'fecha' => '2027-02-03']);
        $category = Categoria::create(['nombre' => 'Cortes', 'slug' => 'corte']);
        $service = Servicio::create(['categoria_id' => $category->id, 'nombre' => 'Corte', 'duracion_min' => 60, 'precio' => 8000, 'activo' => true]);

        $this->actingAs(User::factory()->create(['rol' => 'peluquero']))
            ->put(route('peluquero.disponibilidad.update'), [
                'anio' => '2027',
                'mes' => '2',
                'hora_inicio' => '10:30',
                'hora_fin' => '19:30',
                'dias' => ['2', '3', '4', '5', '6'],
                'off' => ['2', '2', '7', '28', '30'],
                'meses_visibles' => ['2027-02'],
            ])->assertSessionHasNoErrors()->assertSessionHas('success')
            ->assertRedirect(route('peluquero.disponibilidad.edit', ['anio' => 2027, 'mes' => 2]));

        $this->assertSame(['2027-01-20', '2027-02-02'], DiaOff::orderBy('fecha')->get()->map(fn (DiaOff $day) => $day->fecha->toDateString())->all());
        $availability = app(AvailabilityService::class);
        $this->assertSame([], $availability->slotsDisponibles('2027-02-02', $service));
        $this->assertNotEmpty($availability->slotsDisponibles('2027-02-03', $service));
        $this->get(route('peluquero.disponibilidad.edit', ['anio' => 2027, 'mes' => 2]))
            ->assertOk()->assertViewHas('offs', [2]);
    }
}
