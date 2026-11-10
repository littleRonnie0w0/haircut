<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\Reserva;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ReservationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_a_reserved_block_cannot_be_booked_twice(): void
    {
        Carbon::setTestNow('2026-09-10 09:00:00');
        Configuracion::create(['hora_inicio' => '10:30:00', 'hora_fin' => '19:30:00', 'dias_atencion' => '2,3,4,5,6']);
        $categoria = Categoria::create(['nombre' => 'Cortes', 'slug' => 'corte']);
        $servicio = Servicio::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Corte',
            'duracion_min' => 60,
            'precio' => 15000,
            'activo' => true,
        ]);
        $primero = User::factory()->create();
        $segundo = User::factory()->create();
        $datos = [
            'servicio_id' => $servicio->id,
            'fecha' => '2026-09-11',
            'hora' => '10:30',
            'lugar' => 'salon',
        ];

        $this->actingAs($primero)->post(route('reservas.store'), $datos)
            ->assertRedirect(route('reservas.index'));
        $this->actingAs($segundo)->from(route('reservas.create'))->post(route('reservas.store'), $datos)
            ->assertRedirect(route('reservas.create'))
            ->assertSessionHas('error');

        $this->assertSame(1, Reserva::query()->count());
    }

    public function test_hairdresser_can_confirm_a_pending_request(): void
    {
        Mail::fake();
        $peluquero = User::factory()->create(['rol' => 'peluquero']);
        $cliente = User::factory()->create();
        $categoria = Categoria::create(['nombre' => 'Cortes', 'slug' => 'corte']);
        $servicio = Servicio::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Corte',
            'duracion_min' => 60,
            'precio' => 15000,
        ]);
        $reserva = Reserva::create([
            'usuario_id' => $cliente->id,
            'servicio_id' => $servicio->id,
            'fecha' => now()->addDay()->toDateString(),
            'hora' => '10:30',
            'estado' => 'pendiente',
        ]);

        $this->actingAs($peluquero)
            ->patch(route('peluquero.solicitudes.update', $reserva), ['accion' => 'confirmar'])
            ->assertSessionHas('success');

        $this->assertSame('confirmada', $reserva->fresh()->estado);
    }

    public function test_reference_photo_is_saved_and_available_to_the_hairdresser(): void
    {
        Storage::fake('public');
        $payload = $this->photoReservationPayload();
        $photo = UploadedFile::fake()->createWithContent('reference.png', file_get_contents(public_path('img/favicon.png')))->size(3072);
        $this->actingAs(User::factory()->create())->post(route('reservas.store'), $payload + ['fotos' => [$photo]])
            ->assertSessionHasNoErrors()->assertRedirect(route('reservas.index'));
        $reservation = Reserva::firstOrFail();
        $this->assertNotNull($reservation->foto);
        $ruta = $reservation->rutasFoto()[0] ?? null;
        $this->assertNotNull($ruta);
        Storage::disk('public')->assertExists($ruta);
        $this->assertSame(file_get_contents($photo->getRealPath()), Storage::disk('public')->get($ruta));
        $this->actingAs(User::factory()->create(['rol' => 'peluquero']))
            ->get(route('peluquero.solicitudes.index'))->assertOk()->assertSee($ruta);
    }

    public function test_invalid_and_oversized_reference_photos_are_rejected_without_a_reservation(): void
    {
        Storage::fake('public');
        $payload = $this->photoReservationPayload();
        $this->actingAs(User::factory()->create());
        foreach ([UploadedFile::fake()->create('reference.pdf', 10, 'application/pdf'), UploadedFile::fake()->createWithContent('large.png', file_get_contents(public_path('img/favicon.png')))->size(3073)] as $photo) {
            $this->post(route('reservas.store'), $payload + ['fotos' => [$photo]])->assertSessionHasErrors('fotos.0');
        }
        $this->assertSame(0, Reserva::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failed_photo_storage_is_reported_without_creating_a_reservation(): void
    {
        $payload = $this->photoReservationPayload();
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFile')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);
        $photo = UploadedFile::fake()->createWithContent('reference.png', file_get_contents(public_path('img/favicon.png')));
        $this->actingAs(User::factory()->create())->post(route('reservas.store'), $payload + ['fotos' => [$photo]])
            ->assertSessionHasErrors('fotos');
        $this->assertSame(0, Reserva::count());
    }

    private function photoReservationPayload(): array
    {
        Carbon::setTestNow('2026-09-10 09:00:00');
        Configuracion::create(['hora_inicio' => '10:30:00', 'hora_fin' => '19:30:00', 'dias_atencion' => '2,3,4,5,6']);
        $category = Categoria::create(['nombre' => 'Cortes y lavado', 'slug' => 'corte']);
        $service = Servicio::create(['categoria_id' => $category->id, 'nombre' => 'Corte de hombre', 'duracion_min' => 60, 'precio' => 8000]);

        return ['servicio_id' => $service->id, 'fecha' => '2026-09-11', 'hora' => '10:30', 'lugar' => 'salon'];
    }
}
