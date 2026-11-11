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
        ], ['id_rol'], ['rol']);

        DB::table('ROL')->where('rol', 'CliAdmin')->delete();

        $users = [
            [
                'id_usuario' => 1,
                'nombre_usuario' => 'Administrador',
                'email' => 'admin@admin.cl',
                'contrasenia' => '$2y$10$VoCY9sMcCNHglwe.P/2DfumxW29mcf5OtcWHN/WVSuccjEPCmJmXa',
                'id_rol' => 1,
            ],
            [
                'id_usuario' => 2,
                'nombre_usuario' => 'Ricardo',
                'email' => 'ricardo@me.com',
                'contrasenia' => '$2y$10$EbBQ8mLf7lekhA.01lr6zeRX/..gby5ug6zb51oi5uiMQ9/DaP9Sq',
                'id_rol' => 2,
            ],
            [
                'id_usuario' => 3,
                'nombre_usuario' => 'Admin Test Imagen',
                'email' => 'admin.imagen@haircut.cl',
                'contrasenia' => Hash::make('ImagenTest2026!'),
                'id_rol' => 1,
            ],
            [
                'id_usuario' => 4,
                'nombre_usuario' => 'Rosa Garrido',
                'email' => 'garrido.rosa2004@gmail.com',
                'contrasenia' => Hash::make('Rosa2004!'),
                'id_rol' => 3,
            ],
        ];

        $keepEmails = array_column($users, 'email');
        $this->purgeUsersOutside($keepEmails);

        foreach ($users as $user) {
            $existingId = DB::table('USUARIO')->where('email', $user['email'])->value('id_usuario');
            if ($existingId) {
                DB::table('USUARIO')->where('id_usuario', $existingId)->update([
                    'nombre_usuario' => $user['nombre_usuario'],
                    'contrasenia' => $user['contrasenia'],
                    'id_rol' => $user['id_rol'],
                ]);
                continue;
            }

            $idTaken = DB::table('USUARIO')->where('id_usuario', $user['id_usuario'])->exists();
            DB::table('USUARIO')->insert([
                'id_usuario' => $idTaken
                    ? ((int) DB::table('USUARIO')->max('id_usuario')) + 1
                    : $user['id_usuario'],
                'nombre_usuario' => $user['nombre_usuario'],
                'email' => $user['email'],
                'contrasenia' => $user['contrasenia'],
                'id_rol' => $user['id_rol'],
            ]);
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

        $rosaId = (int) DB::table('USUARIO')
            ->where('email', 'garrido.rosa2004@gmail.com')
            ->value('id_usuario');

        $corteHombre = DB::table('SERVICIO')
            ->where('nombre_servicio', 'Corte de hombre')
            ->value('id_servicio');

        if ($rosaId && $corteHombre) {
            $reservaId = DB::table('RESERVA')
                ->where('id_usuario', $rosaId)
                ->where('id_estado_reserva', 5)
                ->value('id_reserva');

            if (! $reservaId) {
                $reservaId = ((int) DB::table('RESERVA')->max('id_reserva')) + 1;
                DB::table('RESERVA')->insert([
                    'id_reserva' => $reservaId,
                    'hora' => '11:30:00',
                    'fecha' => now()->subDays(30)->toDateString(),
                    'id_usuario' => $rosaId,
                    'id_estado_reserva' => 5,
                    'id_tipo_reserva' => 1,
                ]);
            } else {
                DB::table('RESERVA')->where('id_reserva', $reservaId)->update([
                    'hora' => '11:30:00',
                    'fecha' => now()->subDays(30)->toDateString(),
                    'id_estado_reserva' => 5,
                    'id_tipo_reserva' => 1,
                ]);
            }

            DB::table('DETALLE_RESERVA')->updateOrInsert(
                ['id_reserva' => $reservaId],
                [
                    'monto_total' => 8000,
                    'id_servicio' => $corteHombre,
                ],
            );
        }
    }

    /**
     * @param  list<string>  $keepEmails
     */
    private function purgeUsersOutside(array $keepEmails): void
    {
        $removeIds = DB::table('USUARIO')
            ->whereNotIn('email', $keepEmails)
            ->pluck('id_usuario');

        if ($removeIds->isEmpty()) {
            return;
        }

        $reservaIds = DB::table('RESERVA')
            ->whereIn('id_usuario', $removeIds)
            ->pluck('id_reserva');

        if ($reservaIds->isNotEmpty()) {
            DB::table('DETALLE_RESERVA')->whereIn('id_reserva', $reservaIds)->delete();
            DB::table('RESERVA')->whereIn('id_reserva', $reservaIds)->delete();
        }

        foreach ($removeIds as $removeId) {
            DB::table('AI_GENERATIONS')
                ->where('ip_hash', hash_hmac('sha256', 'user:'.$removeId, (string) config('app.key')))
                ->delete();
        }

        DB::table('HISTORIAL_MOD')->whereIn('id_usuario', $removeIds)->delete();
        DB::table('sessions')->whereIn('user_id', $removeIds)->delete();
        DB::table('USUARIO')->whereIn('id_usuario', $removeIds)->delete();
    }
}
