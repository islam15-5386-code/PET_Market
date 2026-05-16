<?php

$frontendUrl = env('APP_FRONTEND_URL', env('FRONTEND_URL', 'http://localhost:3000'));

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Development
        'http://localhost:3000',
        'http://127.0.0.1:3000',

        // Production frontend (from env)
        rtrim($frontendUrl, '/'),
    ],

    'allowed_origins_patterns' => [
        '/^http:\\/\\/(localhost|127\\.0\\.0\\.1|0\\.0\\.0\\.0)(:\\d+)?$/',
        '/^http:\\/\\/192\\.168\\.\\d+\\.\\d+(\\:\\d+)?$/',
        '/^http:\\/\\/10\\.\\d+\\.\\d+\\.\\d+(\\:\\d+)?$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,    // 24 hours — reduces preflight requests in prod

    'supports_credentials' => true,

];
