<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Canal de mensajes (Resend)
    |--------------------------------------------------------------------------
    |
    | driver=resend|mail → Laravel Mail (MAIL_MAILER=resend)
    | driver=http        → API directa de Resend (MESSAGING_API_KEY)
    | driver=log         → solo logs locales
    |
    */

    'driver' => env('MESSAGING_DRIVER', 'http'),

    'api_key' => env('MESSAGING_API_KEY', env('RESEND_API_KEY')),

    'api_url' => env('MESSAGING_API_URL', 'https://api.resend.com/emails'),

    'from' => env('MESSAGING_FROM', env('MAIL_FROM_ADDRESS', 'onboarding@resend.dev')),

    'from_name' => env('MESSAGING_FROM_NAME', env('MAIL_FROM_NAME', 'HairCut Home Studio')),

    'site_url' => env('APP_URL', 'http://localhost:8000'),

    'soft_fail' => (bool) env('MESSAGING_SOFT_FAIL', true),

    /*
    | Con from=onboarding@resend.dev, Resend solo entrega al correo de la cuenta.
    | Mientras no haya dominio verificado, redirige todos los envíos a este correo.
    */
    'override_to' => env('MESSAGING_OVERRIDE_TO'),

];
