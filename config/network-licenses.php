<?php

return [
    'private_key_path' => env('NETWORK_LICENSE_PRIVATE_KEY_PATH', storage_path('app/private/netwatch/license-private.pem')),
    'public_key_path' => env('NETWORK_LICENSE_PUBLIC_KEY_PATH', storage_path('app/private/netwatch/license-public.pem')),
    'key_id' => env('NETWORK_LICENSE_KEY_ID', 'netwatch-1'),
    'verification_days' => 2,
    'trial_sensor_limit' => 2147483647,
    'trial_installation_limit' => 1,
];
