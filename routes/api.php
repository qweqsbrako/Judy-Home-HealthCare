<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Mobile\PatientController as MobilePatientController;

/*
|--------------------------------------------------------------------------
| API Routes - Mobile Application Only
|--------------------------------------------------------------------------
|
| These routes are for the mobile application and use Sanctum token
| authentication. All web-based routes have been moved to web.php
|
*/

// Health Check Route
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'service' => 'Judy Home Care API'
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot-password');
    Route::post('/verify-reset-otp', [AuthController::class, 'verifyResetOtp'])->name('auth.verify-reset-otp');
    Route::post('/resend-reset-otp', [AuthController::class, 'resendResetOtp'])->name('auth.resend-reset-otp');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password');
});

// Mobile API Routes (Token-based with Sanctum)
Route::middleware('auth:sanctum')->prefix('mobile')->group(function () {
    
    // Nurse Dashboard
    Route::get('/nurse/dashboard', [MobilePatientController::class, 'nurseMobileDashboard']);
    
    // Patient Management
    Route::prefix('nurse/patients')->group(function () {
        Route::get('/', [MobilePatientController::class, 'nursePatients']);
        Route::get('/{patientId}', [MobilePatientController::class, 'nursePatientDetail']);
        Route::post('/{patientId}/progress-notes', [MobilePatientController::class, 'createProgressNote']);
    });
    
    // Schedule Management
    Route::get('/nurse/schedules', [MobilePatientController::class, 'nurseSchedules']);
    
    // Care Plan Management
    Route::prefix('nurse/care-plans')->group(function () {
        Route::get('/', [MobilePatientController::class, 'nurseCarePlans']);
        Route::post('/create', [MobilePatientController::class, 'createCarePlan']);
        Route::put('/{id}', [MobilePatientController::class, 'updateCarePlan']);
    });
    
    // Medical Assessments
    Route::post('/nurse/medical-assessments', [MobilePatientController::class, 'createMedicalAssessment']);
    
    // Transport Requests
    Route::prefix('nurse/transport-requests')->group(function () {
        Route::get('/', [MobilePatientController::class, 'nurseTransportRequests']);
        Route::post('/', [MobilePatientController::class, 'createTransportRequest']);
        Route::get('/available-drivers', [MobilePatientController::class, 'getAvailableDrivers']);
        Route::get('/{id}', [MobilePatientController::class, 'getTransportRequest']);
    });
    
    // Incident Management
    Route::prefix('nurse/incidents')->group(function () {
        Route::get('/', [MobilePatientController::class, 'getIncidents']);
        Route::post('/', [MobilePatientController::class, 'createIncident']);
        Route::get('/patients', [MobilePatientController::class, 'getPatientsForIncident']);
    });
    
    // Time Tracking
    Route::prefix('nurse/time-tracking')->group(function () {
        Route::get('/active', [MobilePatientController::class, 'getActiveSession']);
        Route::post('/clock-out', [MobilePatientController::class, 'clockOut']);
        Route::post('/pause', [MobilePatientController::class, 'pauseSession']);
        Route::post('/resume', [MobilePatientController::class, 'resumeSession']);
        Route::post('/schedules/{scheduleId}/clock-in', [MobilePatientController::class, 'clockInSchedule']);
    });
});