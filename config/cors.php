<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Put your frontend URL here
    'allowed_origins' => [env('FRONT_URI')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Must be true when using cookies or Authorization headers
    'supports_credentials' => true,
];
