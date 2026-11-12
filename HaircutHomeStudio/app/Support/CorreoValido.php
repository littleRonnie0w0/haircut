<?php

namespace App\Support;

final class CorreoValido
{
    /** Proveedores de correo personales conocidos. */
    private const PROVEEDORES = [
        'gmail.com',
        'googlemail.com',
        'hotmail.com',
        'hotmail.es',
        'outlook.com',
        'outlook.es',
        'live.com',
        'msn.com',
        'yahoo.com',
        'yahoo.es',
        'ymail.com',
        'icloud.com',
        'me.com',
        'mac.com',
    ];

    /**
     * @return list<string|\Closure>
     */
    public static function rules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'email:rfc',
            'max:150',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === null || trim((string) $value) === '') {
                    return;
                }

                if (! self::esAceptable((string) $value)) {
                    $fail(
                        'Ingresa un correo válido: Gmail, Hotmail, Outlook, Yahoo, iCloud '
                        .'o un correo empresarial que termine en .cl o .com.'
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(string $campo = 'email'): array
    {
        return [
            "{$campo}.required" => 'Ingresa tu correo electrónico.',
            "{$campo}.email" => 'El correo electrónico no tiene un formato válido.',
            "{$campo}.max" => 'El correo electrónico es demasiado largo.',
        ];
    }

    public static function esAceptable(string $email): bool
    {
        $email = mb_strtolower(trim($email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $at = strrpos($email, '@');
        if ($at === false) {
            return false;
        }

        $dominio = substr($email, $at + 1);
        if ($dominio === '' || ! str_contains($dominio, '.')) {
            return false;
        }

        // Rechaza etiquetas vacías o basura tipo "a@.com" / "a@b."
        if (preg_match('/(^\\.)|(\\.\\.)|(\\.$)/', $dominio)) {
            return false;
        }

        if (in_array($dominio, self::PROVEEDORES, true)) {
            return true;
        }

        // Empresarial u otro correo legítimo con TLD .cl o .com
        return (bool) preg_match('/\.(cl|com)$/i', $dominio);
    }
}
