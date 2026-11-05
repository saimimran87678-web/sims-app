<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RfidAttendanceController;

Route::prefix('v1/rfid')->group(function () {
    Route::post('/scan', [RfidAttendanceController::class, 'scan']);
    Route::post('/card/write-confirm', [RfidAttendanceController::class, 'writeConfirm']);
    Route::post('/card/inspect', [RfidAttendanceController::class, 'inspect']);
    Route::post('/sync', [RfidAttendanceController::class, 'sync']);
    Route::get('/status', [RfidAttendanceController::class, 'status']);
});
