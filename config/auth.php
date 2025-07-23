<?php

return [
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        // Guard untuk User (Blade)
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        
        // Guard untuk Admin Panel (Filament)
        'admin' => [
            'driver' => 'session',
            'provider' => 'admin_users',
        ],
        
        // Guard untuk Dokter Panel (Filament)
        'dokter' => [
            'driver' => 'session',
            'provider' => 'dokter_users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        // Provider khusus Admin (hanya role admin)
        'admin_users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        // Provider khusus Dokter (hanya role dokter)
        'dokter_users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];