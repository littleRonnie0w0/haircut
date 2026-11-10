<?php

namespace App\Services;

use App\Models\Reserva;

class ReservationWorkflowService
{
    public function actualizarRealizadas(): int
    {
        $actualizadas = 0;
        $idRealizada = Reserva::idEstadoUi('realizada');

        if ($idRealizada === null) {
            return 0;
        }

        Reserva::query()
            ->conEstado('confirmada')
            ->get()
            ->each(function (Reserva $reserva) use (&$actualizadas, $idRealizada): void {
                if ($reserva->fecha === null || $reserva->hora === null) {
                    return;
                }

                if ($reserva->fecha->copy()->setTimeFromTimeString($reserva->hora)->isPast()) {
                    $reserva->update(['id_estado_reserva' => $idRealizada]);
                    $actualizadas++;
                }
            });

        return $actualizadas;
    }
}
