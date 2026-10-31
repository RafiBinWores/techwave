<?php

use App\Http\Controllers\NetworkLicenseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/netwatch')->middleware('throttle:60,1')->group(function (): void {
    Route::post('/activate', [NetworkLicenseController::class, 'activate']);
    Route::post('/refresh', [NetworkLicenseController::class, 'refresh']);
    Route::post('/deactivate', [NetworkLicenseController::class, 'deactivate']);
});
