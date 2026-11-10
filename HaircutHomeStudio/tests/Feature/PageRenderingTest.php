<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PageRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-10 09:00:00');
        $this->seed();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_public_pages_render(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('HairCut Home Studio');
        $this->get(route('productos.index'))->assertOk()->assertSee('Productos del estudio');
        $this->get(route('login'))->assertOk()->assertSee('Entrar');
    }

    public function test_client_pages_render(): void
    {
        $cliente = User::query()->where('rol', 'cliente')->firstOrFail();

        $this->actingAs($cliente)->get(route('reservas.create'))->assertOk()->assertSee('Reserva aquí');
        $this->actingAs($cliente)->get(route('reservas.index'))->assertOk()->assertSee('Mis Reservas');
    }

    public function test_catalog_admin_pages_render(): void
    {
        $admin = User::query()->where('rol', 'admin')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.servicios.index'))->assertOk()->assertSee('Agregar servicio');
        $this->actingAs($admin)->get(route('admin.catalogo.index'))->assertOk()->assertSee('Agregar producto');
    }

    public function test_hairdresser_pages_render(): void
    {
        $peluquero = User::query()->where('rol', 'peluquero')->firstOrFail();

        $this->actingAs($peluquero)->get(route('peluquero.dashboard'))->assertOk()->assertSee('Resumen del studio');
        $this->actingAs($peluquero)->get(route('peluquero.disponibilidad.edit'))->assertOk()->assertSee('Días libres');
        $this->actingAs($peluquero)->get(route('peluquero.solicitudes.index'))->assertOk()->assertSee('Solicitudes Pendientes');
        $this->actingAs($peluquero)->get(route('peluquero.horas.index'))->assertOk()->assertSee('Horas');
    }
}
