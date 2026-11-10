<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::table('ROL')->upsert([
            ['id_rol' => 1, 'rol' => 'Admin'],
            ['id_rol' => 2, 'rol' => 'Peluquero'],
            ['id_rol' => 3, 'rol' => 'Cliente'],
            ['id_rol' => 4, 'rol' => 'CliAdmin'],
        ], ['id_rol'], ['rol']);

        $users = [
            [1, 'Administrador', 'admin@admin.cl', '$2y$10$VoCY9sMcCNHglwe.P/2DfumxW29mcf5OtcWHN/WVSuccjEPCmJmXa', 1],
            [2, 'Ricardo', 'ricardo@me.com', '$2y$10$EbBQ8mLf7lekhA.01lr6zeRX/..gby5ug6zb51oi5uiMQ9/DaP9Sq', 2],
            [3, 'Cliente Demo', 'cliente@haircut.cl', '$2y$10$3tAusVgmF5O2dgde4RgTjukQ1LDM.7TKjpE.OtECw0CATUo5DDAli', 3],
            [4, 'Ana', 'ana@haircut.cl', '$2y$10$fj.pjC1kYGdi5vAOuKcts.XmZqJPaZrYrdZ4Gtxn8mUzlwKO4LbPK', 3],
            [5, 'Camila Rojas', 'camila@correo.cl', '$2y$10$3tAusVgmF5O2dgde4RgTjukQ1LDM.7TKjpE.OtECw0CATUo5DDAli', 3],
            [6, 'Diego Pérez', 'diego@correo.cl', '$2y$10$3tAusVgmF5O2dgde4RgTjukQ1LDM.7TKjpE.OtECw0CATUo5DDAli', 3],
            [7, 'Fernanda López', 'fernanda@correo.cl', '$2y$10$3tAusVgmF5O2dgde4RgTjukQ1LDM.7TKjpE.OtECw0CATUo5DDAli', 3],
            [8, 'Admin Test Imagen', 'admin.imagen@haircut.cl', Hash::make('ImagenTest2026!'), 1],
        ];

        foreach ($users as [$id, $name, $email, $password, $roleId]) {
            DB::table('USUARIO')->updateOrInsert(
                ['id_usuario' => $id],
                [
                    'email' => $email,
                    'contrasenia' => $password,
                    'nombre_usuario' => $name,
                    'id_rol' => $roleId,
                ],
            );
        }

        DB::table('CATEGORIA')->upsert([
            ['id_categoria' => 1, 'categoria' => 'Cortes'],
            ['id_categoria' => 2, 'categoria' => 'Color'],
            ['id_categoria' => 3, 'categoria' => 'Tratamientos'],
        ], ['id_categoria'], ['categoria']);

        $this->call(CatalogoServiciosSeeder::class);

        $this->call(CatalogoProductosSeeder::class);

        DB::table('ESTADO_RESERVA')->upsert([
            ['id_estado_reserva' => 1, 'estado_reserva' => 'En aprovación'],
            ['id_estado_reserva' => 2, 'estado_reserva' => 'Agendada'],
            ['id_estado_reserva' => 3, 'estado_reserva' => 'Rechazada'],
            ['id_estado_reserva' => 4, 'estado_reserva' => 'Cancelada'],
            ['id_estado_reserva' => 5, 'estado_reserva' => 'Realizada'],
            ['id_estado_reserva' => 6, 'estado_reserva' => 'No asistió'],
        ], ['id_estado_reserva'], ['estado_reserva']);

        DB::table('TIPO_RESERVA')->upsert([
            ['id_tipo_reserva' => 1, 'tipo_reserva' => 'Salón'],
            ['id_tipo_reserva' => 2, 'tipo_reserva' => 'Domicilio'],
        ], ['id_tipo_reserva'], ['tipo_reserva']);

        $reservations = [
            [1, 4, 'Corte de hombre', now()->addDays(2)->toDateString(), '10:30:00', 1, 1, 8000],
            [2, 5, 'Tintura de retoque', now()->addDays(3)->toDateString(), '11:30:00', 1, 2, 30000],
            [3, 6, 'Colores fantasía', now()->addDays(4)->toDateString(), '10:30:00', 2, 1, 30000],
            [4, 4, 'Corte de hombre', now()->subDays(30)->toDateString(), '11:30:00', 5, 1, 8000],
            [5, 7, 'Corte de mujer', now()->subDays(34)->toDateString(), '15:30:00', 4, 1, 8000],
            [6, 5, 'Visos', now()->subDays(36)->toDateString(), '10:30:00', 6, 1, 45000],
        ];

        foreach ($reservations as [$id, $userId, $serviceName, $date, $time, $statusId, $typeId, $amount]) {
            $serviceId = DB::table('SERVICIO')
                ->where('nombre_servicio', $serviceName)
                ->value('id_servicio');

            if ($serviceId === null) {
                throw new \RuntimeException("No existe el servicio '{$serviceName}' para la reserva de prueba.");
            }

            DB::table('RESERVA')->updateOrInsert(
                ['id_reserva' => $id],
                [
                    'hora' => $time,
                    'fecha' => $date,
                    'id_usuario' => $userId,
                    'id_estado_reserva' => $statusId,
                    'id_tipo_reserva' => $typeId,
                ],
            );

            DB::table('DETALLE_RESERVA')->updateOrInsert(
                ['id_detalle_reserva' => $id],
                [
                    'monto_total' => $amount,
                    'id_servicio' => $serviceId,
                    'id_reserva' => $id,
                ],
            );
        }
    }
}
