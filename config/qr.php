<?php

return [
    'free_daily_scan_limit' => (int) env('QR_FREE_DAILY_SCAN_LIMIT', 10),
    'scan_timezone' => env('QR_SCAN_TIMEZONE', 'Asia/Dhaka'),
];
