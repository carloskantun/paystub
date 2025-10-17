<?php
return [
    'stripe' => [
    // Prefer modern names with legacy fallbacks for compatibility
    'public_key' => getenv('STRIPE_PK') ?: getenv('STRIPE_PUBLIC'),
    'secret_key' => getenv('STRIPE_SECRET') ?: getenv('STRIPE_SK'),
    ],
    'mercadopago' => [
        'access_token' => getenv('MP_ACCESS_TOKEN'),
    ],
];
