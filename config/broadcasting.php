<?php

return [

    'default' => env('BROADCAST_CONNECTION', 'null'),

    'connections' => [
        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_BROADCAST_CONNECTION', 'default'),
            'prefix' => env('REDIS_BROADCAST_PREFIX', ''),
        ],

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
                'useTLS' => env('PUSHER_SCHEME', 'http') === 'https',
                'host' => env('PUSHER_HOST', '127.0.0.1'),
                'port' => (int) env('PUSHER_PORT', 6001),
                'scheme' => env('PUSHER_SCHEME', 'http'),
                'encrypted' => env('PUSHER_SCHEME', 'http') === 'https',
            ],
        ],
    ],

];
