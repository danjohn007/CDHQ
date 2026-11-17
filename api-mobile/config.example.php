<?php
declare(strict_types=1);

return [
    'app' => [
        // URL HTTPS absoluta usada por la app móvil para fotos y portadas.
        'public_base_url' => 'https://cdhq.digital/api-mobile',
    ],
    'database' => [
        'host' => 'localhost',
        'port' => '3306',
        'name' => 'COLOCA_AQUI_LA_BASE_MYSQL',
        'user' => 'COLOCA_AQUI_EL_USUARIO_MYSQL',
        'password' => 'COLOCA_AQUI_LA_PASSWORD_MYSQL',
    ],
    'mail' => [
        // Buzón del dominio usado para enviar los códigos de recuperación de
        // contraseña. Sin esto, el código queda en el log de errores del servidor.
        'from_address' => 'no-reply@cdhq.digital',
        'from_name' => 'CDHQ',
    ],
    'stripe' => [
        // La versión actual sólo acepta llaves de prueba sk_test_.
        'secret_key' => 'COLOCA_AQUI_SK_TEST',
        'webhook_secret' => 'COLOCA_AQUI_WHSEC',
        'success_url' => 'https://cdhq.digital/api-mobile/payment-success.html',
        'cancel_url' => 'https://cdhq.digital/api-mobile/payment-cancelled.html',
    ],
];
