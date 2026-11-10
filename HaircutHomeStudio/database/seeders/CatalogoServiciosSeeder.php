<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogoServiciosSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $types = [
                1 => ['Corte', 'corte', 'ico-cat-corte.png'],
                2 => ['Color', 'color', 'ico-color.png'],
                3 => ['Tratamientos', 'tratamientos', 'ico-tratamiento.png'],
            ];

            foreach ($types as $id => [$name, $slug, $image]) {
                DB::table('TIPO_SERVICIO')->updateOrInsert(
                    ['id_tipo_servicio' => $id],
                    ['tipo_servicio' => $name, 'slug' => $slug, 'imagen' => $image],
                );
            }

            $services = [
                [1, 'Corte de hombre', 8000, 60, 'ico-corte.png'],
                [1, 'Corte de mujer', 8000, 60, 'ico-corte.png'],
                [1, 'Lavado', 4000, 60, 'ico-brushing.png'],
                [1, 'Lavado y secado', 8000, 60, 'ico-brushing.png'],
                [2, 'Tintura de retoque', 30000, 120, 'ico-retoque.png'],
                [2, 'Retoque de tintura + baño de color', 35000, 120, 'ico-retoque.png'],
                [2, 'Balayage', 80000, 300, 'ico-balayage.png'],
                [2, 'Babylights', 80000, 300, 'ico-babylight.png'],
                [2, 'Mechas', 45000, 180, 'ico-mechas.png'],
                [2, 'Visos', 45000, 180, 'ico-visos.png'],
                [2, 'Reflejos', 40000, 180, 'ico-visos.png'],
                [2, 'Colores fantasía', 30000, 240, 'ico-fantasia.png'],
                [3, 'Liso permanente', 50000, 240, 'ico-liso.png'],
                [3, 'Alisado Brasil Cacau', 85000, 240, 'ico-liso.png'],
                [3, 'Botox capilar', 30000, 120, 'ico-botox.png'],
                [3, 'Masaje de queratina', 20000, 60, 'ico-masaje.png'],
                [3, 'Tratamiento Olaplex', 35000, 60, 'ico-olaplex.png'],
            ];

            foreach ($services as [$typeId, $name, $price, $duration, $image]) {
                DB::table('SERVICIO')->updateOrInsert(
                    ['nombre_servicio' => $name, 'id_tipo_servicio' => $typeId],
                    [
                        'descripcion' => 'Bloque de agenda referencial; confirma la duración con el profesional.',
                        'imagen' => $image,
                        'duracion_min' => $duration,
                        'precio' => $price,
                        'disponibilidad' => true,
                    ],
                );
            }
        });
    }
}
