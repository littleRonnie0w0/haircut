<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Support\Format;
use App\Support\SimplePdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function export(Request $request): Response|StreamedResponse
    {
        $datos = $request->validate([
            'anio' => ['required', 'integer', 'min:2020', 'max:2100'],
            'mes' => ['required', 'integer', 'between:1,12'],
            'formato' => ['required', 'in:csv,pdf'],
        ]);
        $reservas = Reserva::query()
            ->with(['usuario', 'servicio.categoria'])
            ->whereYear('fecha', $datos['anio'])
            ->whereMonth('fecha', $datos['mes'])
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get();
        $nombreBase = 'reporte-haircut-'.$datos['anio'].'-'.str_pad((string) $datos['mes'], 2, '0', STR_PAD_LEFT);

        if ($datos['formato'] === 'csv') {
            return response()->streamDownload(function () use ($reservas): void {
                $salida = fopen('php://output', 'w');
                fwrite($salida, "\xEF\xBB\xBF");
                fputcsv($salida, ['Fecha', 'Hora', 'Cliente', 'Email', 'Categoría', 'Servicio', 'Lugar', 'Estado'], ';');
                foreach ($reservas as $reserva) {
                    fputcsv($salida, [
                        $reserva->fecha->format('Y-m-d'),
                        $this->rango($reserva),
                        $reserva->usuario->nombre,
                        $reserva->usuario->email,
                        $reserva->servicio->categoria->nombre,
                        $reserva->servicio->nombre,
                        Format::lugar($reserva->lugar),
                        Format::estado($reserva->estado),
                    ], ';');
                }
                fclose($salida);
            }, $nombreBase.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $contenido = $this->crearPdf($reservas, $datos['anio'], $datos['mes']);

        return response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$nombreBase.'.pdf"',
            'Content-Length' => strlen($contenido),
        ]);
    }

    private function crearPdf($reservas, int $anio, int $mes): string
    {
        $conteo = collect(['pendiente', 'confirmada', 'realizada', 'cancelada', 'no_asistio', 'rechazada'])
            ->mapWithKeys(fn (string $estado) => [$estado => $reservas->where('estado', $estado)->count()]);
        $validas = $reservas->whereIn('estado', ['confirmada', 'realizada']);
        $ingresos = $validas->sum(fn (Reserva $reserva) => (float) $reserva->servicio->precio);
        $top = $validas->groupBy('servicio_id')
            ->mapWithKeys(fn ($grupo) => [$grupo->first()->servicio->nombre => $grupo->count()])
            ->sortDesc()->take(4);

        $burdeo = [92, 41, 60];
        $burdeoOscuro = [74, 32, 48];
        $tinta = [58, 37, 48];
        $suave = [140, 107, 120];
        $durazno = [235, 224, 228];
        $arena = [243, 231, 236];
        $blanco = [255, 255, 255];
        $crema = [250, 247, 245];
        $verde = [61, 154, 100];
        $pdf = new SimplePdf;
        $pdf->addPage();
        $ancho = $pdf->width();
        $margen = 36;
        $logo = public_path('img/logo-pdf.jpg');
        if (! is_file($logo)) {
            $logo = public_path('img/logo.png');
        }

        $pdf->rect(0, 0, $ancho, 112, $burdeo);
        $pdf->text($margen, 28, 22, 'Reporte mensual', true, $blanco);
        $pdf->text($margen, 56, 11, Format::MESES[$mes].' '.$anio, false, $durazno);
        $pdf->image($ancho - $margen - 64, 10, 64, 64, $logo, null, true);
        $pdf->text($margen, 82, 9, 'Generado el '.now()->format('d/m/Y H:i'), false, $durazno);

        $y = 130;
        $espacio = 10;
        $anchoTarjeta = ($ancho - $margen * 2 - $espacio * 3) / 4;
        $indicadores = [
            ['Reservas', (string) $reservas->count(), $burdeo],
            ['Confirmadas', (string) $validas->count(), $verde],
            ['Pendientes', (string) $conteo['pendiente'], [224, 138, 60]],
            ['Ingresos', Format::precio($ingresos), $burdeoOscuro],
        ];
        foreach ($indicadores as $indice => $indicador) {
            $x = $margen + $indice * ($anchoTarjeta + $espacio);
            $pdf->rect($x, $y, $anchoTarjeta, 58, $blanco);
            $pdf->rect($x, $y, 5, 58, $indicador[2]);
            $pdf->text($x + 14, $y + 12, 8, strtoupper($indicador[0]), true, $suave);
            $pdf->text($x + 14, $y + 28, 14, $indicador[1], true, $burdeo);
        }

        $y = 204;
        $pdf->text($margen, $y, 11, 'Resumen del mes', true, $burdeo);
        $y += 16;
        $estados = [
            ['Pendiente', $conteo['pendiente']], ['Confirmada', $conteo['confirmada']],
            ['Realizada', $conteo['realizada']], ['Cancelada', $conteo['cancelada']],
            ['No llegó', $conteo['no_asistio']], ['Rechazada', $conteo['rechazada']],
        ];
        $maximoEstado = max(1, ...array_column($estados, 1));
        foreach ($estados as [$etiqueta, $cantidad]) {
            $pdf->text($margen, $y, 8, $etiqueta, false, $tinta);
            $pdf->rect($margen + 72, $y + 1, 250, 8, $arena);
            if ($cantidad > 0) {
                $pdf->rect($margen + 72, $y + 1, max(4, 250 * ($cantidad / $maximoEstado)), 8, $burdeo);
            }
            $pdf->text($margen + 330, $y, 8, (string) $cantidad, true, $burdeo);
            $y += 14;
        }

        $servicioX = 410;
        $servicioY = 204;
        $pdf->text($servicioX, $servicioY, 11, 'Servicios más pedidos', true, $burdeo);
        $servicioY += 18;
        if ($top->isEmpty()) {
            $pdf->text($servicioX, $servicioY, 9, 'Sin datos este mes.', false, $suave);
        } else {
            $maximo = max(1, (int) $top->max());
            foreach ($top as $nombre => $cantidad) {
                $pdf->text($servicioX, $servicioY, 8, $pdf->clip($nombre, 8, 120), false, $tinta);
                $servicioY += 11;
                $pdf->rect($servicioX, $servicioY, 105, 7, $arena);
                $pdf->rect($servicioX, $servicioY, max(4, 105 * ($cantidad / $maximo)), 7, $burdeo);
                $pdf->text(522, $servicioY - 1, 8, (string) $cantidad, true, $burdeo);
                $servicioY += 14;
            }
        }

        $y = max($y, $servicioY) + 18;
        $pdf->text($margen, $y, 11, 'Detalle de reservas', true, $burdeo);
        $y += 14;
        $columnas = [['Fecha', 58], ['Hora', 78], ['Cliente', 92], ['Servicio', 210], ['Estado', 75]];
        $this->cabeceraTabla($pdf, $margen, $y, $columnas, $burdeo, $blanco);

        if ($reservas->isEmpty()) {
            $pdf->rect($margen, $y, 513, 22, $crema);
            $pdf->text($margen + 8, $y + 7, 9, 'Sin reservas en este mes.', false, $suave);
        } else {
            foreach ($reservas as $indice => $reserva) {
                if ($y > 800) {
                    $pdf->addPage();
                    $pdf->rect(0, 0, $ancho, 36, $burdeo);
                    $pdf->text($margen, 12, 11, 'Reporte mensual · '.Format::MESES[$mes].' '.$anio, true, $blanco);
                    $y = 44;
                    $this->cabeceraTabla($pdf, $margen, $y, $columnas, $burdeo, $blanco);
                }
                $pdf->rect($margen, $y, 513, 16, $indice % 2 === 0 ? $crema : $blanco);
                $valores = [
                    $reserva->fecha->format('d/m/Y'),
                    $this->rango($reserva),
                    $reserva->usuario->nombre,
                    $reserva->servicio->categoria->nombre.' — '.$reserva->servicio->nombre,
                    Format::estado($reserva->estado),
                ];
                $x = $margen + 4;
                foreach ($columnas as $indiceColumna => $columna) {
                    $pdf->text($x, $y + 4, 8, $pdf->clip($valores[$indiceColumna], 8, $columna[1] - 8), false, $tinta);
                    $x += $columna[1];
                }
                $y += 16;
            }
        }

        $pdf->text($margen, 822, 8, 'Haircut Home Studio · Prof. Ricardo Luengo Mardones · Melipilla', false, $suave);

        return $pdf->render();
    }

    private function cabeceraTabla(SimplePdf $pdf, float $x, float &$y, array $columnas, array $fondo, array $texto): void
    {
        $pdf->rect($x, $y, array_sum(array_column($columnas, 1)), 16, $fondo);
        $columnaX = $x + 4;
        foreach ($columnas as $columna) {
            $pdf->text($columnaX, $y + 4, 8, $columna[0], true, $texto);
            $columnaX += $columna[1];
        }
        $y += 16;
    }

    private function rango(Reserva $reserva): string
    {
        $inicio = substr($reserva->hora, 0, 5);
        $fin = $reserva->fecha->setTimeFromTimeString($inicio)
            ->addMinutes(max(60, $reserva->servicio->duracion_min))
            ->format('H:i');

        return $inicio.' – '.$fin;
    }
}
