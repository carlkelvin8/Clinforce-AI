<?php

return [
    'enabled' => env('RATE_LIMIT_ENABLED', true),

    // Default window and limits
    'window_seconds' => env('RATE_LIMIT_WINDOW', 60),

    'roles' => [
        // Authenticated users get higher limits
        'authenticated' => [
            'limit' => env('RATE_LIMIT_AUTH_LIMIT', 100),
            'burst' => env('RATE_LIMIT_AUTH_BURST', 50),
        ],
        // Anonymous (no auth) get stricter limits
        'anonymous' => [
            'limit' => env('RATE_LIMIT_ANON_LIMIT', 20),
            'burst' => env('RATE_LIMIT_ANON_BURST', 10),
        ],
    ],

    // Add headers to responses
    'headers' => [
        'enabled' => true,
    ],

    // Fallback to cache driver if Redis unavailable
    'graceful_degradation' => true,
];
