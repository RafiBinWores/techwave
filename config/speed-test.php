<?php

return [
    'geo' => [
        // Browser-side fallbacks for public IP and approximate city.
        'providers' => [
            'https://ipwho.is/',
            'https://ipapi.co/json/',
        ],
        'timeout_ms' => 4000,
    ],
];
