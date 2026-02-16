<?php

return [
    'base_currency' => env('BILLING_BASE_CURRENCY', 'USD'),

    'supported_currencies' => [
        'USD',
        'EUR',
        'GBP',
        'PHP',
        'JPY',
        'AUD',
        'CAD',
    ],

    'max_rate_age_hours' => env('BILLING_MAX_RATE_AGE_HOURS', 24),
];

