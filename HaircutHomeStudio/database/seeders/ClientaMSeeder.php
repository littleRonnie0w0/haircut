<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ClientaMSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'clienta.m@haircut.cl';
        $hash = Hash::make('ClientaM123');
        $id = DB::table('USUARIO')->where('email', $email)->value('id_usuario');

        if (! $id) {
            $id = ((int) DB::table('USUARIO')->max('id_usuario')) + 1;
            DB::table('USUARIO')->insert([
                'id_usuario' => $id,
                'email' => $email,
                'contrasenia' => $hash,
                'nombre_usuario' => 'Clienta M.',
                'id_rol' => 3,
            ]);

            return;
        }

        DB::table('USUARIO')->where('id_usuario', $id)->update([
            'nombre_usuario' => 'Clienta M.',
            'contrasenia' => $hash,
            'id_rol' => 3,
        ]);
    }
}
