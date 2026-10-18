<?php

// SSLCommerz configuration

return [
    // Credentials and mode are resolved from Site Settings at payment time.
    'apiCredentials' => [
        'store_id' => null,
        'store_password' => null,
    ],
    'apiUrl' => [
        'make_payment' => '/gwprocess/v4/api.php',
        'transaction_status' => '/validator/api/merchantTransIDvalidationAPI.php',
        'order_validate' => '/validator/api/validationserverAPI.php',
        'refund_payment' => '/validator/api/merchantTransIDvalidationAPI.php',
        'refund_status' => '/validator/api/merchantTransIDvalidationAPI.php',
    ],
    'apiDomain' => 'https://sandbox.sslcommerz.com',
    'connect_from_localhost' => env('IS_LOCALHOST', false), // For Sandbox, use "true", For Live, use "false"
    'success_url' => '/sslcommerz/success',
    'fail_url' => '/sslcommerz/fail',
    'cancel_url' => '/sslcommerz/cancel',
    'ipn_url' => '/sslcommerz/ipn',
];
