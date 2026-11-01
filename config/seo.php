<?php

return [
    'free_daily_limit' => (int) env('SEO_FREE_DAILY_LIMIT', 3),
    'premium_daily_limit' => (int) env('SEO_PREMIUM_DAILY_LIMIT', 50),
    'retention_days' => 90,
    'history_limit' => 50,
    'max_redirects' => 5,
    'html_bytes' => 2097152,
    'auxiliary_bytes' => 262144,
    'connect_timeout' => 5,
    'audit_seconds' => 20,
    'probe_seconds' => 2,
    'integration_seconds' => 4,
    'integrations' => [
        'backlinks' => ['endpoint' => env('SEO_BACKLINKS_ENDPOINT'), 'token' => env('SEO_BACKLINKS_TOKEN')],
        'screenshot' => ['provider' => env('SEO_SCREENSHOT_PROVIDER', 'thumio'), 'endpoint' => env('SEO_SCREENSHOT_ENDPOINT'), 'token' => env('SEO_SCREENSHOT_TOKEN')],
    ],
];
