<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'broadcasting/auth'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        env('APP_FRONTEND_URL'),
        env('APP_URL'),
        env('VITE_APP_FRONTEND_URL'),
        in_array(env('APP_ENV', 'production'), ['local', 'testing']) ? 'http://localhost:5173' : null,
        in_array(env('APP_ENV', 'production'), ['local', 'testing']) ? 'http://127.0.0.1:5173' : null,
        in_array(env('APP_ENV', 'production'), ['local', 'testing']) ? 'http://localhost:8000' : null,
        in_array(env('APP_ENV', 'production'), ['local', 'testing']) ? 'http://127.0.0.1:8000' : null,
    ]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];