<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogoProductosSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [1, 'Shampoo Pravana Moisture Marvel', 'Hidratación para cabello seco o teñido', 19900, 3, 'fotos/shampoo-moisture-marvel.jpg'],
            [2, 'Shampoo Pravana Perfect Blonde', 'Matizador violeta para rubios y decolorados', 19900, 3, 'fotos/shampoo-perfect-blonde.jpg'],
            [3, 'Shampoo Pravana Repair Radiance', 'Reparación y brillo para cabello dañado', 19900, 3, 'fotos/shampoo-repair-radiance.jpg'],
            [4, 'Shampoo Pravana Volume Vibrance', 'Volumen ligero para cabello fino', 19900, 3, 'fotos/shampoo-volume-vibrance.jpg'],
            [5, 'Shampoo Pravana Clear Cleanse', 'Clarificante: limpia residuos y minerales', 19900, 3, 'fotos/shampoo-clear-cleanse.jpg'],
            [6, 'Acondicionador Pravana Moisture Marvel', 'Hidratación y suavidad Pravana', 19900, 3, 'fotos/acond-moisture-marvel.jpg'],
            [7, 'Acondicionador Pravana Perfect Blonde', 'Acondicionador matizador para rubios', 19900, 3, 'fotos/acond-perfect-blonde.jpg'],
            [8, 'Acondicionador Pravana Repair Radiance', 'Reparación y anti-frizz Pravana', 19900, 3, 'fotos/acond-repair-radiance.jpg'],
            [9, 'Acondicionador Pravana Volume Vibrance', 'Volumen y brillo sin pesar', 19900, 3, 'fotos/acond-volume-vibrance.jpg'],
            [10, 'Aceite Pravana', 'Aceite capilar Pravana para brillo y nutrición', 21900, 3, 'fotos/aceite-pravana.png'],
            [11, 'Leave-in protector térmico Pravana', 'Leave-in Pravana con protección térmica', 19900, 3, 'fotos/leavein-pravana.jpg'],
            [12, 'Cera capilar', 'Fijación y definición para peinados', 7900, 1, 'fotos/cera-capilar.png'],
            [13, 'Polvo texturizante', 'Volumen y textura sin apelmazar', 7900, 1, 'fotos/polvo-texturizante.jpg'],
            [14, 'Aceite hidratante Bohus', 'Elixir hidratante Bohus para nutrir y brillar', 24900, 3, 'fotos/aceite-bohus.jpg'],
            [15, 'Crema de tratamiento Bohus', 'Crema de tratamiento Bohus para hidratar en profundidad', 24900, 3, 'fotos/crema-bohus.jpg'],
        ];

        foreach ($products as [$id, $name, $description, $price, $categoryId, $image]) {
            DB::table('PRODUCTO')->updateOrInsert(
                ['id_producto' => $id],
                [
                    'producto' => $name,
                    'descripcion' => $description,
                    'precio' => $price,
                    'imagen' => $image,
                    'disponibilidad' => true,
                    'id_categoria' => $categoryId,
                ],
            );
        }

        DB::table('PRODUCTO')->where('id_producto', '>', 15)->delete();
    }
}
