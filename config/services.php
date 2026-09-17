<?php

return [
    'mercadopago' => [
        'public_key' => env('MERCADO_PAGO_PUBLIC_KEY'),
        'access_token' => env('MERCADO_PAGO_ACCESS_TOKEN'),
    ],
    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'private_key' => env('FIREBASE_PRIVATE_KEY'),
    ],
];
