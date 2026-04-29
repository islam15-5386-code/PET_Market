<?php

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Development
        'http://localhost:3000',

        // Production — replace with your actual frontend domain
        'https://petmarketplace.com',
        'https://www.petmarketplace.com',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,    // 24 hours — reduces preflight requests in prod

    'supports_credentials' => true,

];
