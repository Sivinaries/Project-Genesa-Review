<?php

use App\Http\Controllers\Api\EssApiController;
use App\Http\Controllers\FingerspotController;
use Illuminate\Support\Facades\Route;

Route::post('/fingerspot/webhook', [FingerspotController::class, 'handleWebhook']);

Route::prefix('v1')->group(function () {

    Route::post('/login', [EssApiController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/logout', [EssApiController::class, 'logout']);

        // Data diri & home
        Route::get('/home',    [EssApiController::class, 'home']);
        Route::get('/profile', [EssApiController::class, 'profil']);

        // Jadwal
        Route::get('/schedule', [EssApiController::class, 'schedule']);

        // Absensi (read-only dari ESS)
        Route::get('/attendance', [EssApiController::class, 'attendance']);

        // Cuti & Izin
        Route::get('/leave',  [EssApiController::class, 'leave']);
        Route::post('/leave', [EssApiController::class, 'reqLeave']);

        // Lembur
        Route::get('/overtime',  [EssApiController::class, 'overtime']);
        Route::post('/overtime', [EssApiController::class, 'reqOvertime']);

        // Slip Gaji
        Route::get('/payroll',      [EssApiController::class, 'payroll']);
        Route::get('/payroll/{id}', [EssApiController::class, 'payrollDetail']);

        // Note
        Route::get('/note', [EssApiController::class, 'note']);

        // GPS Attendance
        Route::get('/gps-attendance',            [EssApiController::class, 'gpsAttendance']);
        Route::post('/gps-attendance/checkin',   [EssApiController::class, 'gpsCheckIn']);
        Route::post('/gps-attendance/checkout',  [EssApiController::class, 'gpsCheckOut']);

        // Jadwal koordinator
        Route::get('/coordinator/schedule',           [EssApiController::class, 'coordinatorSchedule']);
        Route::post('/coordinator/schedule',          [EssApiController::class, 'coordinatorStoreSchedule']);
        Route::put('/coordinator/schedule/{id}',      [EssApiController::class, 'coordinatorUpdateSchedule']);
        Route::delete('/coordinator/schedule/{id}',   [EssApiController::class, 'coordinatorDestroySchedule']);

        // Cuti koordinator
        Route::get('/coordinator/leave',              [EssApiController::class, 'coordinatorLeave']);
        Route::post('/coordinator/leave',             [EssApiController::class, 'storeCoordinatorLeave']);
        Route::put('/coordinator/leave/{id}',         [EssApiController::class, 'coordinatorUpdateLeave']);

        // Lembur koordinator
        Route::get('/coordinator/overtime',           [EssApiController::class, 'coordinatorOvertime']);
        Route::post('/coordinator/overtime',          [EssApiController::class, 'storeCoordinatorOvertime']);
        Route::put('/coordinator/overtime',           [EssApiController::class, 'coordinatorBatchUpdate']);
        Route::delete('/coordinator/overtime',        [EssApiController::class, 'coordinatorBatchDelete']);
    });
});