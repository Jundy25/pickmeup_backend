<?php
// config/broadcasting.php

return [
    'default' => env('BROADCAST_DRIVER', 'reverb'),

    'connections' => [
        'reverb' => [
            'driver' => 'pusher',
            'app_id' => env('REVERB_APP_ID', 'local'),
            'key' => env('REVERB_APP_KEY', 'local'),
            'secret' => env('REVERB_APP_SECRET', 'local'),
            'app_host' => env('REVERB_HOST', '127.0.0.1'),
            'app_port' => env('REVERB_PORT', 8080),
            'use_tls' => env('REVERB_USE_TLS', false),
        ],

        // Keep your existing websockets configuration if needed
        'websockets' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY', 'local'),
            'secret' => env('PUSHER_APP_SECRET', 'local'),
            'app_id' => env('PUSHER_APP_ID', 'local'),
            'options' => [
                'host' => env('PUSHER_HOST', '127.0.0.1'),
                'port' => env('PUSHER_PORT', 6001),
                'scheme' => env('PUSHER_SCHEME', 'http'),
                'encrypted' => false,
                'useTLS' => false,
            ],
        ],
    ],
];