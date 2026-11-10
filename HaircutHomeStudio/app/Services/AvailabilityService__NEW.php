<?php

use App\Models\Reserva;
use App\Models\EstadoReserva;
use App\Models\Servicio;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AvailabilityService
{
    public function getConfig(): object{
        return (object) config('reglasnegocio');
        // contiene hora_inicio, hora_fin, dias_atencion
    }

    // función para obtener las reservas con un estado de inhabilitado
    public function getReservasInhabilitadas(): Collection
    {
        return Reserva::query()
            ->where('id_estado_reserva', 6)
            ->get();
    }
    
    // funcion para obtener las reservas con un estado de habilitado
    public function getReservasHabilitadas(): Collection
    {
        return Reserva::query()
            ->where('id_estado_reserva', 7)
            ->get();
    }

}