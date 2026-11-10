<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Services\MessagingService;
use App\Services\ReservationWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SolicitudController extends Controller
{
    public function __construct(
        private readonly ReservationWorkflowService $workflow,
        private readonly MessagingService $messaging,
    ) {}

    public function index(): View
    {
        $this->workflow->actualizarRealizadas();

        return view('admin.solicitudes', [
            'solicitudes' => Reserva::query()
                ->with(['usuario', 'servicio', 'estadoReserva', 'detalleReserva'])
                ->conEstado('pendiente')
                ->orderBy('fecha')
                ->orderBy('hora')
                ->get(),
        ]);
    }

    public function update(Request $request, Reserva $reserva): RedirectResponse
    {
        $datos = $request->validate([
            'accion' => ['required', Rule::in(['confirmar', 'rechazar'])],
        ]);
        $reserva->load(['usuario', 'servicio', 'estadoReserva']);

        if ($reserva->estado !== 'pendiente') {
            return back()->with('error', 'La solicitud ya no está pendiente.');
        }

        $confirmar = $datos['accion'] === 'confirmar';
        $reserva->asignarEstadoUi($confirmar ? 'confirmada' : 'rechazada');

        $nombre = $reserva->usuario?->nombre_usuario ?? 'clienta';
        $servicio = $reserva->servicio?->nombre ?? 'servicio';
        $fecha = $reserva->fecha?->format('d/m/Y') ?? '';
        $hora = substr((string) $reserva->hora, 0, 5);
        $email = (string) ($reserva->usuario?->email ?? '');

        $mailOk = true;
        if ($email !== '') {
            if ($confirmar) {
                $mailOk = $this->messaging->sendBrand(
                    $email,
                    'Hora confirmada — HairCut Home Studio',
                    "Hola {$nombre},",
                    [
                        "Tu hora de {$servicio} quedó adjudicada para el {$fecha} a las {$hora}.",
                        'Te esperamos en HairCut Home Studio. El pago es presencial.',
                    ],
                    route('reservas.index'),
                    'Ver mis reservas',
                    'Para más información visita nuestra página <a href="'.e(config('messaging.site_url')).'" style="color:#5C293C;">HairCut Home Studio</a>.',
                    ['tipo' => 'reserva_confirmada', 'reserva_id' => $reserva->id_reserva],
                );
            } else {
                $mailOk = $this->messaging->sendBrand(
                    $email,
                    'Solicitud no confirmada — HairCut Home Studio',
                    "Hola {$nombre},",
                    [
                        "Tu solicitud de {$servicio} para el {$fecha} no pudo confirmarse.",
                        'El cupo quedó libre. Puedes intentar reservar otra hora cuando quieras.',
                    ],
                    route('reservas.create'),
                    'Reservar otra hora',
                    'Para más información visita nuestra página <a href="'.e(config('messaging.site_url')).'" style="color:#5C293C;">HairCut Home Studio</a>.',
                    ['tipo' => 'reserva_rechazada', 'reserva_id' => $reserva->id_reserva],
                );
            }
        }

        if ($confirmar) {
            return back()->with(
                $mailOk ? 'success' : 'error',
                $mailOk
                    ? 'Solicitud confirmada. Se notificó al cliente por correo.'
                    : 'Solicitud confirmada, pero el correo no se pudo enviar.',
            );
        }

        return back()->with(
            $mailOk ? 'success' : 'error',
            $mailOk
                ? 'Solicitud rechazada. El cupo quedó libre.'
                : 'Solicitud rechazada, pero el correo no se pudo enviar.',
        );
    }
}
