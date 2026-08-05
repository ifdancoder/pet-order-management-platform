<?php

declare(strict_types=1);

return [
    'authentication' => [
        'jwt' => [
            'issuer' => env('JWT_ISSUER', env('APP_URL', 'http://localhost')),
            'audience' => env('JWT_AUDIENCE', 'orderflow-api'),
            'access_ttl_seconds' => (int) env('JWT_ACCESS_TTL_SECONDS', 900),
            'private_key_path' => env(
                'JWT_PRIVATE_KEY_PATH',
                storage_path('app/keys/jwt-private.pem'),
            ),
            'public_key_path' => env(
                'JWT_PUBLIC_KEY_PATH',
                storage_path('app/keys/jwt-public.pem'),
            ),
            'private_key_passphrase' => env('JWT_PRIVATE_KEY_PASSPHRASE', ''),
        ],
    ],
];
