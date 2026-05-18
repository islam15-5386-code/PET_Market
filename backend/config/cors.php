<?php

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', env('FRONTEND_URL', '')))))),

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
