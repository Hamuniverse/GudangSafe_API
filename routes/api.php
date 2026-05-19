<?php

use App\Http\Controllers\Api\ActuatorController;
use App\Http\Controllers\Api\AnomalyController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SensorController;
use App\Http\Controllers\Api\ThresholdController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| GudangSafe API Routes
|--------------------------------------------------------------------------
|
| Semua route di sini otomatis diawali prefix /api (dari bootstrap/app.php)
| Auth menggunakan Laravel Sanctum (token-based).
|
*/

// Public (tanpa auth)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [App\Http\Controllers\Api\ForgotPasswordController::class, 'sendResetCode']);
Route::post('/reset-password',  [App\Http\Controllers\Api\ForgotPasswordController::class, 'resetPassword']);

// Protected (wajib login)
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout',         [AuthController::class, 'logout']);
    Route::get('/profile',         [AuthController::class, 'profile']);
    Route::put('/profile',         [AuthController::class, 'updateProfile']);

    // Sensor
    Route::get('/sensor/realtime', [SensorController::class, 'realtime']);
    Route::get('/sensor/history',  [SensorController::class, 'history']);

    // Aktuator
    Route::get('/actuator/status', [ActuatorController::class, 'status']);
    Route::post('/actuator/control', [ActuatorController::class, 'control']);
    Route::get('/actuator/log',    [ActuatorController::class, 'log']);

    // Threshold
    Route::get('/threshold',       [ThresholdController::class, 'show']);
    Route::put('/threshold',       [ThresholdController::class, 'update'])
        ->middleware('role:admin');

    // Notifikasi
    Route::get('/notification',    [NotificationController::class, 'index']);
    Route::delete('/notification', [NotificationController::class, 'destroy']);

    // Anomali
    Route::get('/anomaly',         [AnomalyController::class, 'index']);

    // Manajemen Pegawai (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/employee',          [EmployeeController::class, 'index']);
        Route::post('/employee',         [EmployeeController::class, 'store']);
        Route::put('/employee/{id}',     [EmployeeController::class, 'update']);
        Route::delete('/employee/{id}',  [EmployeeController::class, 'destroy']);
    });
});
