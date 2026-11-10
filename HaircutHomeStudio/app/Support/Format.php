<?php

namespace App\Support;

use Carbon\CarbonInterface;

class Format
{
    public const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public const DIAS = [
        1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
        5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo',
    ];

    public static function precio(float|int|string|null $valor): string
    {
        return '$'.number_format((float) $valor, 0, ',', '.');
    }

    public static function lugar(?string $lugar): string
    {
        return $lugar === 'domicilio' ? 'A domicilio' : 'En el salón';
    }

    public static function duracion(int $minutos): string
    {
        $horas = max(1, (int) ceil($minutos / 60));

        return $horas === 1 ? '1 h' : $horas.' h';
    }

    public static function estado(string $estado): string
    {
        return [
            'pendiente' => 'Pendiente',
            'confirmada' => 'Confirmada',
            'realizada' => 'Realizada',
            'realizando' => 'Realizando',
            'finalizado' => 'Finalizado',
            'cancelada' => 'Cancelada',
            'no_asistio' => 'No llegó',
            'rechazada' => 'Rechazada',
        ][$estado] ?? $estado;
    }

    public static function estadoClase(string $estado): string
    {
        return [
            'pendiente' => 'status-pendiente',
            'confirmada' => 'status-confirmada',
            'realizada' => 'status-realizada',
            'cancelada' => 'status-cancelada',
            'no_asistio' => 'status-cancelada',
            'rechazada' => 'status-rechazada',
        ][$estado] ?? 'status-pendiente';
    }

    public static function tituloDia(CarbonInterface $fecha): string
    {
        $base = self::DIAS[$fecha->dayOfWeekIso].' '.$fecha->day.' de '.self::MESES[$fecha->month];
        if ($fecha->year !== now()->year) {
            $base .= ' '.$fecha->year;
        }

        return match ($fecha->toDateString()) {
            now()->toDateString() => 'Hoy · '.$base,
            now()->addDay()->toDateString() => 'Mañana · '.$base,
            default => $base,
        };
    }
}
