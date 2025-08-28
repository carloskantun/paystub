<?php
return [
    'stripe' => [
        'public_key' => getenv('STRIPE_PK'),
        'secret_key' => getenv('STRIPE_SK'),
    ],
    'mercadopago' => [
        'access_token' => getenv('MP_ACCESS_TOKEN'),
    ],
];
