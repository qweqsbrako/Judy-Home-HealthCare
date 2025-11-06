<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\API\Mobile\ProfileController;
use App\Http\Controllers\API\Mobile\NurseController;
use App\Http\Controllers\API\Mobile\PatientController;
use App\Http\Controllers\API\Mobile\ScheduleController;
use App\Http\Controllers\API\Mobile\CarePlanController;
use App\Http\Controllers\API\Mobile\ProgressNoteController;
use App\Http\Controllers\API\Mobile\TimeTrackingController;
use App\Http\Controllers\API\Mobile\IncidentController;
use App\Http\Controllers\API\Mobile\TransportController;
use App\Http\Controllers\API\Mobile\MedicalAssessmentController;
use App\Http\Controllers\API\Mobile\FeedbackController;
use App\Http\Controllers\API\Mobile\CareRequestController;
use App\Http\Controllers\API\Mobile\MobileMoneyOtpController;
use App\Http\Controllers\Api\Mobile\NotificationController;


/*
|--------------------------------------------------------------------------
| API Routes - Mobile Application
|--------------------------------------------------------------------------
|
| Clean, organized routes following RESTful conventions and role-based
| access control. Routes are grouped by resource and user role for clarity.
|
*/

// ============================================================================
// PUBLIC ROUTES (No Authentication Required)
// ============================================================================

// Health Check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'service' => 'Judy Home Care API',
        'version' => '2.0'
    ]);
});

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/verify-reset-otp', [AuthController::class, 'verifyResetOtp']);
    Route::post('/resend-reset-otp', [AuthController::class, 'resendResetOtp']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    
    // 2FA for login
    Route::post('/verify-login-2fa', [AuthController::class, 'verifyLoginTwoFactor']);
    Route::post('/resend-login-2fa', [AuthController::class, 'resendLoginTwoFactor']);
});

// ============================================================================
// PROTECTED ROUTES (Authentication Required)
// ============================================================================

Route::middleware('auth:sanctum')->prefix('mobile')->group(function () {
    
    // ------------------------------------------------------------------------
    // PROFILE MANAGEMENT (All Authenticated Users)
    // ------------------------------------------------------------------------
    Route::prefix('profile')->group(function () {
        // Basic profile operations
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/update', [ProfileController::class, 'update']);
        Route::post('/avatar', [ProfileController::class, 'uploadAvatar']);
        Route::post('/change-password', [ProfileController::class, 'changePassword']);
        
        // Two-Factor Authentication
        Route::post('/enable-2fa', [ProfileController::class, 'enableTwoFactor']);
        Route::post('/verify-2fa', [ProfileController::class, 'verifyTwoFactorOtp']);
        Route::post('/resend-2fa-code', [ProfileController::class, 'resendTwoFactorCode']);
        Route::post('/disable-2fa', [ProfileController::class, 'disableTwoFactor']);
        
        // Notification Settings
        Route::post('/notification-settings', [ProfileController::class, 'updateNotificationSettings']);
        Route::post('/notification-preferences', [ProfileController::class, 'updateNotificationPreferences']);
    });

    // Mobile Money OTP endpoints
    Route::post('/payments/send-otp', [MobileMoneyOtpController::class, 'sendOtp']);
    Route::post('/payments/verify-otp', [MobileMoneyOtpController::class, 'verifyOtp']);
    Route::post('/payments/resend-otp', [MobileMoneyOtpController::class, 'resendOtp']);
    Route::get('/payments/check-verification', [MobileMoneyOtpController::class, 'checkVerification']);


        // Register FCM token
    Route::post('/notifications/register-token', [NotificationController::class, 'registerToken']);
    
    // Get user notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    
    // Mark notification as read
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    
    // Mark all as read
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    
    // Delete notification
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    
    // Get unread count
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    
    // ------------------------------------------------------------------------
    // NURSE-SPECIFIC ROUTES
    // ------------------------------------------------------------------------
    Route::prefix('nurse')->middleware('role:nurse')->group(function () {
        //dashboard details
        Route::get('/dashboard', [NurseController::class, 'nurseMobileDashboard']);

        // Patient Management
        Route::get('/patients', [NurseController::class, 'getPatients']);
        Route::get('/patients/{patientId}', [NurseController::class, 'getPatientDetail']);
        
        // Medical Assessments (Nurse only)
        Route::get('/medical-assessments', [MedicalAssessmentController::class, 'index']);
        Route::post('/medical-assessments', [MedicalAssessmentController::class, 'store']);
        Route::get('/medical-assessments/{id}', [MedicalAssessmentController::class, 'show']);
        
        // Time Tracking (Nurse only)
        Route::prefix('time-tracking')->group(function () {
            Route::get('/active', [TimeTrackingController::class, 'getActive']);
            Route::post('/clock-in', [TimeTrackingController::class, 'clockIn']);
            Route::post('/clock-out', [TimeTrackingController::class, 'clockOut']);
            Route::post('/pause', [TimeTrackingController::class, 'pause']);
            Route::post('/resume', [TimeTrackingController::class, 'resume']);
            Route::get('/logs', [TimeTrackingController::class, 'getLogs']);
        });
        
        // Incident Reports (Nurse only)
        Route::prefix('incidents')->group(function () {
            Route::get('/', [IncidentController::class, 'index']);
            Route::post('/', [IncidentController::class, 'store']);
            Route::get('/{id}', [IncidentController::class, 'show']);
            Route::get('/patients/list', [IncidentController::class, 'getPatients']);
        });
    });
    
    // ------------------------------------------------------------------------
    // PATIENT-SPECIFIC ROUTES
    // ------------------------------------------------------------------------
    Route::prefix('patient')->middleware('role:patient')->group(function () {
        Route::get('/dashboard', [PatientController::class, 'patientMobileDashboard']);
        Route::get('/care-plans', [PatientController::class, 'carePlans']);
        Route::get('/care-plans/{id}', [PatientController::class, 'carePlanDetail']);
        Route::get('/schedules', [PatientController::class, 'schedules']);
        Route::get('/progress-notes', [PatientController::class, 'progressNotes']);

        // Feedback routes
        Route::prefix('feedback')->group(function () {
            Route::get('/', [FeedbackController::class, 'index']);
            Route::post('/', [FeedbackController::class, 'store']);
            Route::get('/nurses', [FeedbackController::class, 'getNursesForFeedback']);
            Route::get('/statistics', [FeedbackController::class, 'getStatistics']);
        });
    });


        // Care Request Routes
    Route::prefix('care-requests')->group(function () {
        // Get process info and assessment fee
        Route::get('/info', [CareRequestController::class, 'getRequestInfo']);
        Route::get('/payment-config', [CareRequestController::class, 'getPaymentConfig']);
        // CRUD operations
        Route::get('/', [CareRequestController::class, 'index']);
        Route::get('/{id}', [CareRequestController::class, 'show']);
        Route::post('/', [CareRequestController::class, 'store']);
        
        // Actions
        Route::post('/{id}/cancel', [CareRequestController::class, 'cancel']);
        
        // Payment operations
        Route::post('/{id}/payment/initiate', [CareRequestController::class, 'initiatePayment']);
        Route::post('/payment/verify', [CareRequestController::class, 'verifyPayment']);

        
    });

    // Transport Requests (accessible by both Nurse and Patient)
    Route::prefix('transport-requests')->middleware(['auth:sanctum'])->group(function () {
        Route::get('/', [TransportController::class, 'index']);
        Route::post('/', [TransportController::class, 'store']);
        Route::get('/{id}', [TransportController::class, 'show']);
        Route::get('/drivers/available', [TransportController::class, 'getAvailableDrivers']);
    });
    
    // ------------------------------------------------------------------------
    // SHARED RESOURCES (Both Nurse and Patient)
    // ------------------------------------------------------------------------
    
    // Schedules - READ operations (both roles)
    Route::get('/schedules', [ScheduleController::class, 'index']);
    Route::get('/schedules/{id}', [ScheduleController::class, 'show']);
    
    // Care Plans
    Route::prefix('care-plans')->group(function () {
        Route::get('/', [CarePlanController::class, 'index']);
        Route::get('/{id}', [CarePlanController::class, 'show']);
    });

    // WRITE operations (nurse only, with /nurse/care-plans prefix)
    Route::prefix('nurse/care-plans')->middleware('role:nurse')->group(function () {
        Route::post('/', [CarePlanController::class, 'store']);
        Route::put('/{id}', [CarePlanController::class, 'update']);
        Route::post('/{id}/tasks/toggle', [CarePlanController::class, 'toggleTask']);
        Route::get('/doctors', [CarePlanController::class, 'getDoctors']);
        Route::get('/patients', [CarePlanController::class, 'getPatients']);
    });
        
    // Progress Notes
    Route::prefix('progress-notes')->group(function () {
        // READ operations (both roles)
        Route::get('/', [ProgressNoteController::class, 'index']);
        Route::get('/{id}', [ProgressNoteController::class, 'show']);
        
        // WRITE operations (nurse only)
        Route::middleware('role:nurse')->group(function () {
            Route::post('/', [ProgressNoteController::class, 'store']);
        });
    });
});

// ============================================================================
// FALLBACK ROUTES
// ============================================================================

// API 404 Handler
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found. Please check the URL and try again.',
        'available_versions' => ['v1' => '/api/mobile/*']
    ], 404);
});

Route::post('/webhooks/paystack', [CareRequestController::class, 'handleWebhook']);


/*
|--------------------------------------------------------------------------
| Route Registration Notes
|--------------------------------------------------------------------------
|
| MIDDLEWARE REQUIRED:
| 1. Register 'role' middleware in app/Http/Kernel.php:
|    'role' => \App\Http\Middleware\CheckUserRole::class,
|
| ROUTE PATTERNS:
| - Public routes: /api/auth/*
| - Protected routes: /api/mobile/*
| - Nurse routes: /api/mobile/nurse/*
| - Patient routes: /api/mobile/patient/*
| - Shared routes: /api/mobile/{resource}
|
| HTTP METHODS:
| - GET: Retrieve resources
| - POST: Create new resources
| - PUT/PATCH: Update existing resources
| - DELETE: Remove resources
|
| AUTHENTICATION:
| All /api/mobile/* routes require Bearer token authentication via Sanctum
|
*/