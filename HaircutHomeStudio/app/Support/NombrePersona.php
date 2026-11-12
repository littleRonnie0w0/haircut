<?php

namespace App\Support;

final class NombrePersona
{
    /** @var list<string> */
    private const BLOQUEADOS = [
        'clienta',
        'cliente',
        'cliente a',
        'usuario',
        'user',
        'test',
        'prueba',
        'admin',
        'administrador',
        'nombre',
        'sin nombre',
        'n/a',
        'na',
        'asd',
        'qwerty',
        'xxx',
    ];

    /**
     * Reglas Laravel para un nombre de persona real.
     *
     * @return list<string|\Closure>
     */
    public static function rules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'min:2',
            'max:120',
            'regex:/^[\p{L}\p{M}][\p{L}\p{M}\'\.\-]*(?: [\p{L}\p{M}][\p{L}\p{M}\'\.\-]*){0,5}$/u',
            function (string $attribute, mixed $value, \Closure $fail): void {
                $nombre = self::normalizar((string) $value);
                if ($nombre === '') {
                    return;
                }

                if (in_array($nombre, self::BLOQUEADOS, true)) {
                    $fail('Ingresa un nombre válido. No uses textos genéricos como “Clienta”.');

                    return;
                }

                if (preg_match('/(.)\1{2,}/u', $nombre)) {
                    $fail('Revisa el nombre: parece incompleto o mal escrito.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(string $campo = 'nombre'): array
    {
        return [
            "{$campo}.required" => 'Ingresa tu nombre completo.',
            "{$campo}.min" => 'El nombre es demasiado corto. Indica al menos un nombre real.',
            "{$campo}.max" => 'El nombre es demasiado largo.',
            "{$campo}.regex" => 'El nombre solo puede incluir letras, espacios y caracteres como apóstrofe o guion.',
        ];
    }

    public static function normalizar(string $valor): string
    {
        $valor = trim(preg_replace('/\s+/u', ' ', $valor) ?? '');

        return mb_strtolower($valor);
    }
}
