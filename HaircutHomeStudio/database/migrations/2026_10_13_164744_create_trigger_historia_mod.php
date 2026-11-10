<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS TRG_TIPO_SERVICIO_BEFORE_INSERT');

        DB::unprepared("
            CREATE TRIGGER TRG_TIPO_SERVICIO_BEFORE_INSERT
            BEFORE INSERT ON TIPO_SERVICIO
            FOR EACH ROW
            BEGIN
                DECLARE var_slug_nombre VARCHAR(80) DEFAULT '';
                DECLARE var_contador INT DEFAULT 1;
                DECLARE var_longitud INT;
                DECLARE var_letra CHAR(1) DEFAULT '';

                SET var_longitud = CHAR_LENGTH(NEW.tipo_servicio);

                WHILE var_contador <= var_longitud DO
                    SET var_letra = SUBSTRING(NEW.tipo_servicio, var_contador, 1);
                    IF var_letra = ' ' OR var_letra = '' THEN
                        SET var_letra = '_';
                    END IF;
                    SET var_slug_nombre = CONCAT(var_slug_nombre, LOWER(var_letra));
                    SET var_contador = var_contador + 1;
                END WHILE;

                SET NEW.slug = var_slug_nombre;
            END
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS TRG_TIPO_SERVICIO_BEFORE_INSERT;');
    }
};