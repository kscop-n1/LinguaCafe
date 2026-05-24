<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    | Supported: "pusher", "ably", "redis", "log", "null"
    |
    */

    'default' => env('BROADCAST_DRIVER', 'pusher'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over websockets. Samples of
    | each available type of connection are provided inside this array.
    |
    */

    'connections' => [

        'pusher' => [
            'driver' => 'reverb',
            'key' => env('PUSHER_APP_KEY', 'wjp2pou6ebgibtwccqsj'),
            'secret' => env('PUSHER_APP_SECRET', 'linguacafe'),
            'app_id' => env('PUSHER_APP_ID', 'linguacafe'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
                // 'useTLS' => true,
                'host' => '0.0.0.0',
                'port' => 6001,
                'scheme' => 'http',
            ],
        ],

        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
