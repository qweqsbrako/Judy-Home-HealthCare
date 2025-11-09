<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserController; 
use App\Http\Controllers\NurseController; 
use App\Http\Controllers\PatientController; 
use App\Http\Controllers\DoctorController; 
use App\Http\Controllers\PendingVerificationController; 
use App\Http\Controllers\CarePlanController;
use App\Http\Controllers\CareAssignmentController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\TimeTrackingController;
use App\Http\Controllers\ProgressNoteController;
use App\Http\Controllers\MedicalAssessmentController;
use App\Http\Controllers\TransportRequestController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\QualityReportingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminCareRequestController;
use App\Http\Controllers\PaymentController;


/*
|--------------------------------------------------------------------------
| Web Routes - Management Portal
|--------------------------------------------------------------------------
|
| These routes are for the web-based management portal and use session-based
| authentication. All mobile app routes are in api.php
|
*/

// IMPORTANT: Sanctum CSRF route MUST come before catch-all route
// This is handled automatically by Sanctum, but we can be explicit
Route::get('/sanctum/csrf-cookie', function (Request $request) {
    return response()->json(['message' => 'CSRF cookie set']);
});

// Authentication Routes (Public) - MUST come before catch-all
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot-password');
    Route::post('/verify-reset-otp', [AuthController::class, 'verifyResetOtp'])->name('auth.verify-reset-otp');
    Route::post('/resend-reset-otp', [AuthController::class, 'resendResetOtp'])->name('auth.resend-reset-otp');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password');
});

// Protected Routes (Require Authentication)
Route::middleware('auth:web')->group(function () {
    
    // User Authentication Routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    });

    // Dashboard
    Route::get('/dashboard-data', [DashboardController::class, 'index']);
    
    // User Management Routes
    Route::prefix('users')->group(function () {
        
        // ============================================
        // IMPORTANT: Static routes MUST come before parameterized routes!
        // ============================================
        
        // User statistics (accessible to all authenticated users)
        Route::get('statistics', [UserController::class, 'statistics']);
        
        // Export route (static route - must come before {user})
        Route::get('export', [UserController::class, 'export']);
        
        // List route for dropdowns (NO pagination) - MUST come before {user}
        Route::get('list', [UserController::class, 'list']);
        
        // Self-management routes (users can manage their own profile)
        Route::get('profile', function (Request $request) {
            return response()->json([
                'success' => true,
                'data' => new App\Http\Resources\UserResource($request->user())
            ]);
        });
        Route::put('profile', [UserController::class, 'updateProfile']);
        Route::post('upload-avatar', [UserController::class, 'uploadAvatar']);
        Route::delete('delete-avatar', [UserController::class, 'deleteAvatar']);
        
        // Basic CRUD operations
        Route::get('/', [UserController::class, 'index']); // Paginated list
        Route::post('/', [UserController::class, 'store']);
        
        // ============================================
        // Parameterized routes MUST come LAST
        // ============================================
        
        Route::get('{user}', [UserController::class, 'show']);
        Route::put('{user}', [UserController::class, 'update']);
        Route::delete('{user}', [UserController::class, 'destroy']);
        Route::post('/{user}/change-password', [UserController::class, 'changePassword']);
        Route::post('/{user}/send-password-reset', [UserController::class, 'sendPasswordResetEmail']);
        
        // User verification routes
        Route::post('{user}/verify', [UserController::class, 'verify']);
        Route::post('{user}/reject', [UserController::class, 'reject']);
        
        // User status management
        Route::post('{user}/suspend', [UserController::class, 'suspend']);
        Route::post('{user}/activate', [UserController::class, 'activate']);
        
        // Invitation management
        Route::post('{user}/resend-invitation', [UserController::class, 'resendInvitation']);
    });

    // Nurse management routes
    Route::prefix('nurses')->group(function () {
        Route::get('/', [NurseController::class, 'index']);
        Route::post('/', [NurseController::class, 'store']);
        Route::get('export', [NurseController::class, 'export']);
        Route::get('{nurse}', [NurseController::class, 'show']);
        Route::put('{nurse}', [NurseController::class, 'update']);
        Route::delete('{nurse}', [NurseController::class, 'destroy']);
        Route::post('{nurse}/verify', [NurseController::class, 'verify']);
        Route::post('{nurse}/suspend', [NurseController::class, 'suspend']);
        Route::post('{nurse}/activate', [NurseController::class, 'activate']);
        Route::post('/{nurse}/change-password', [NurseController::class, 'changePassword']); 
        Route::post('/{nurse}/send-password-reset', [NurseController::class, 'sendPasswordResetEmail']); 
    });

    // Patient management routes
    Route::get('patients/export', [PatientController::class, 'export']);
    Route::post('patients/{patient}/verify', [PatientController::class, 'verify']);
    Route::post('patients/{patient}/suspend', [PatientController::class, 'suspend']);
    Route::post('patients/{patient}/activate', [PatientController::class, 'activate']);
    Route::post('patients/{patient}/change-password', [PatientController::class, 'changePassword']);
    Route::post('patients/{patient}/send-password-reset', [PatientController::class, 'sendPasswordResetEmail']);
    Route::delete('patients/{patient}/photo', [PatientController::class, 'deletePhotoEndpoint']);
    Route::apiResource('patients', PatientController::class);

    // Doctor management routes
    Route::get('doctors/export', [DoctorController::class, 'export']);
    Route::post('doctors/{doctor}/verify', [DoctorController::class, 'verify']);
    Route::post('doctors/{doctor}/suspend', [DoctorController::class, 'suspend']);
    Route::post('doctors/{doctor}/activate', [DoctorController::class, 'activate']);
    Route::post('doctors/{doctor}/change-password', [DoctorController::class, 'changePassword']);
    Route::post('doctors/{doctor}/send-password-reset', [DoctorController::class, 'sendPasswordResetEmail']);
    Route::apiResource('doctors', DoctorController::class);



    // Payment routes
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payments/statistics', [PaymentController::class, 'getStatistics']);
    Route::get('/payments/export', [PaymentController::class, 'export']);
    Route::get('/payments/{id}', [PaymentController::class, 'show']);


    // Pending Verifications
    Route::prefix('pending-verifications')->group(function () {
        Route::get('/', [PendingVerificationController::class, 'index']);
        Route::get('/export', [PendingVerificationController::class, 'export']);
        Route::get('/stats/overview', [PendingVerificationController::class, 'statistics']);
        Route::post('/bulk-verify', [PendingVerificationController::class, 'bulkVerify']);
        Route::post('/bulk-reject', [PendingVerificationController::class, 'bulkReject']);
        Route::get('/{user}', [PendingVerificationController::class, 'show']);
        Route::post('/{user}/verify', [PendingVerificationController::class, 'verify']);
        Route::post('/{user}/reject', [PendingVerificationController::class, 'reject']);
    });

    /*
    |--------------------------------------------------------------------------
    | Care Plans Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('care-plans')->group(function () {

        Route::get('/export', [CarePlanController::class, 'export']);

        // Main CRUD operations
        Route::get('/', [CarePlanController::class, 'index']);
        Route::post('/', [CarePlanController::class, 'store']);
        Route::get('/{carePlan}', [CarePlanController::class, 'show']);
        Route::put('/{carePlan}', [CarePlanController::class, 'update']);
        Route::delete('/{carePlan}', [CarePlanController::class, 'delete']);

        // Workflow actions
        Route::post('/{carePlan}/submit-for-approval', [CarePlanController::class, 'submitForApproval']);
        Route::post('/{carePlan}/approve', [CarePlanController::class, 'approve']);
        Route::post('/{carePlan}/complete', [CarePlanController::class, 'complete']);
        Route::post('/{carePlan}/update-progress', [CarePlanController::class, 'updateProgress']);

        // Nurse management
        Route::post('/{carePlan}/assign-nurse', [CarePlanController::class, 'assignNurse']);
        Route::post('/{carePlan}/reassign-nurse', [CarePlanController::class, 'reassignNurse']);
        Route::post('/{carePlan}/accept', [CarePlanController::class, 'acceptByNurse']);
        Route::get('/find-suitable-nurses', [CarePlanController::class, 'findSuitableNurses']);

        // Data endpoints
        Route::get('/data/statistics', [CarePlanController::class, 'getStatistics']);
        Route::get('/data/doctors', [CarePlanController::class, 'getDoctors']);
        Route::get('/data/patients', [CarePlanController::class, 'getPatients']);
        Route::get('/data/nurses', [CarePlanController::class, 'getNurses']);
    });

    /*
    |--------------------------------------------------------------------------
    | Schedules Routes
    |--------------------------------------------------------------------------
    */
    // Helper routes MUST come BEFORE the resource routes to avoid conflicts
    Route::get('schedules/statistics', [ScheduleController::class, 'getStatistics']);
    Route::get('schedules/calendar', [ScheduleController::class, 'getCalendarView']);
    Route::get('schedules/nurses', [ScheduleController::class, 'getNurses']);
    Route::get('schedules/export', [ScheduleController::class, 'export']);
    
    // Schedule-specific actions
    Route::post('schedules/{schedule}/confirm', [ScheduleController::class, 'confirmByNurse']);
    Route::post('schedules/{schedule}/start', [ScheduleController::class, 'startShift']);
    Route::post('schedules/{schedule}/end', [ScheduleController::class, 'endShift']);
    Route::post('schedules/{schedule}/cancel', [ScheduleController::class, 'cancel']);
    Route::post('schedules/{schedule}/reminder', [ScheduleController::class, 'sendReminder']);
    Route::get('/schedules/care-plans', [ScheduleController::class, 'getCarePlansForNurse']);

    Route::apiResource('schedules', ScheduleController::class);

    /*
    |--------------------------------------------------------------------------
    | Time Tracking Routes
    |--------------------------------------------------------------------------
    */

        
    // Export
    Route::get('/time-tracking/export', [TimeTrackingController::class, 'export']);

    Route::get('/time-tracking/current', [TimeTrackingController::class, 'getCurrentSession']);
    Route::get('/time-tracking/summary/today', [TimeTrackingController::class, 'getTodaysSummary']);
    Route::get('/time-tracking/summary/weekly', [TimeTrackingController::class, 'getWeeklySummary']);
    Route::get('/time-tracking/statistics', [TimeTrackingController::class, 'getStatistics']);

    // Core CRUD operations
    Route::get('/time-tracking', [TimeTrackingController::class, 'index']);
    Route::post('/time-tracking', [TimeTrackingController::class, 'store']);
    Route::get('/time-tracking/{timeTracking}', [TimeTrackingController::class, 'show']);
    Route::delete('/time-tracking/{timeTracking}', [TimeTrackingController::class, 'destroy']);

    // Clock in/out operations
    Route::post('/time-tracking/{timeTracking}/clock-in', [TimeTrackingController::class, 'clockIn']);
    Route::post('/time-tracking/{timeTracking}/clock-out', [TimeTrackingController::class, 'clockOut']);
    
    // Session management
    Route::post('/time-tracking/{timeTracking}/pause', [TimeTrackingController::class, 'pause']);
    Route::post('/time-tracking/{timeTracking}/resume', [TimeTrackingController::class, 'resume']);
    Route::post('/time-tracking/{timeTracking}/cancel', [TimeTrackingController::class, 'cancel']);
    
    // Break management
    Route::post('/time-tracking/{timeTracking}/add-break', [TimeTrackingController::class, 'addBreak']);


    /*
    |--------------------------------------------------------------------------
    | Progress Notes Routes
    |--------------------------------------------------------------------------
    */
    // Standard CRUD routes
    
    Route::prefix('progress-notes')->group(function () {
        Route::get('export', [ProgressNoteController::class, 'export'])
            ->name('progress-notes.export');
        
        Route::get('statistics', [ProgressNoteController::class, 'statistics'])
            ->name('progress-notes.statistics');
        
        Route::post('{progressNote}/duplicate', [ProgressNoteController::class, 'duplicate'])
            ->name('progress-notes.duplicate');
    });
    
    // Patient-specific routes
    Route::prefix('patients/{patient}')->group(function () {
        Route::get('progress-notes', [ProgressNoteController::class, 'getPatientNotes'])
            ->name('patients.progress-notes');
    });
    
    // Nurse-specific routes  
    Route::prefix('nurses/{nurse}')->group(function () {
        Route::get('progress-notes', [ProgressNoteController::class, 'getNurseNotes'])
            ->name('nurses.progress-notes');
    });

    Route::apiResource('progress-notes', ProgressNoteController::class);

    /*
    |--------------------------------------------------------------------------
    | Medical Assessment Routes
    |--------------------------------------------------------------------------
    */
    Route::apiResource('medical-assessments', MedicalAssessmentController::class);
    
    // Additional custom routes
    Route::prefix('medical-assessments')->group(function () {
        // Export medical assessments to CSV
        Route::get('export', [MedicalAssessmentController::class, 'export'])
            ->name('medical-assessments.export');
        
        // Get statistics for dashboard/reporting
        Route::get('statistics', [MedicalAssessmentController::class, 'statistics'])
            ->name('medical-assessments.statistics');
        
        // Mark assessment as reviewed
        Route::post('{medicalAssessment}/mark-reviewed', [MedicalAssessmentController::class, 'markReviewed'])
            ->name('medical-assessments.mark-reviewed');
    });
    
    // Patient-specific routes
    Route::prefix('patients/{patient}')->group(function () {
        Route::get('medical-assessments', [MedicalAssessmentController::class, 'getPatientAssessments'])
            ->name('patients.medical-assessments');
    });

    /*
    |--------------------------------------------------------------------------
    | Transport Request Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('transports')->group(function () {
        // IMPORTANT: Specific routes MUST come BEFORE dynamic routes with parameters
        
        // Dashboard and export routes FIRST (before {transportRequest})
        Route::get('/dashboard', [TransportRequestController::class, 'dashboard']);
        Route::get('/export', [TransportRequestController::class, 'export']);
        Route::get('/my-requests', [TransportRequestController::class, 'myRequests']);
        
        // Basic CRUD operations
        Route::get('/', [TransportRequestController::class, 'index']);
        Route::post('/', [TransportRequestController::class, 'store']);
        Route::get('/{transportRequest}', [TransportRequestController::class, 'show']);
        Route::put('/{transportRequest}', [TransportRequestController::class, 'update']);
        Route::delete('/{transportRequest}', [TransportRequestController::class, 'destroy']);
        
        // Transport request actions (these can come after {transportRequest} because they have additional segments)
        Route::post('/{transportRequest}/assign-driver', [TransportRequestController::class, 'assignDriver']);
        Route::post('/{transportRequest}/start', [TransportRequestController::class, 'start']);
        Route::post('/{transportRequest}/complete', [TransportRequestController::class, 'complete']);
        Route::post('/{transportRequest}/cancel', [TransportRequestController::class, 'cancel']);
    });
    
    // User's own transport requests
    Route::get('my-transports', [TransportRequestController::class, 'myRequests']);
    
    /*
    |--------------------------------------------------------------------------
    | Driver Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('drivers')->group(function () {
        Route::get('available', [DriverController::class, 'available']);          
        Route::get('dashboard', [DriverController::class, 'dashboard']);          
        Route::get('export', [DriverController::class, 'export']);               
        
        Route::get('/', [DriverController::class, 'index']);                   
        Route::post('/', [DriverController::class, 'store']);                  
        Route::get('{driver}', [DriverController::class, 'show']);             
        Route::put('{driver}', [DriverController::class, 'update']);    
        Route::delete('/{driver}', [DriverController::class, 'destroy'])
        ->name('drivers.destroy');         
        
        Route::post('{driver}/suspend', [DriverController::class, 'suspend']);
        Route::post('{driver}/reactivate', [DriverController::class, 'reactivate']);
        Route::post('{driver}/assign-vehicle', [DriverController::class, 'assignVehicle']);
        Route::post('{driver}/unassign-vehicle', [DriverController::class, 'unassignVehicle']);
    });



    /*
    |--------------------------------------------------------------------------
    | Vehicle Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('vehicles')->group(function () {
        // Basic CRUD Operations
        Route::get('/', [VehicleController::class, 'index']);                   
        Route::post('/', [VehicleController::class, 'store']);                 
        Route::get('/{vehicle}', [VehicleController::class, 'show']);          
        Route::put('/{vehicle}', [VehicleController::class, 'update']);    
        
        // Vehicle Status Management
        Route::patch('/{vehicle}/status', [VehicleController::class, 'updateStatus']); 
        Route::delete('/{vehicle}', [VehicleController::class, 'destroy'])
        ->name('vehicles.destroy');
        
        // Driver Assignment Routes
        Route::post('/{vehicle}/assign-driver', [VehicleController::class, 'assignDriver']);     
        Route::post('/{vehicle}/unassign-driver', [VehicleController::class, 'unassignDriver']);
        
        // Vehicle History & Analytics
        Route::get('/{vehicle}/transport-history', [VehicleController::class, 'transportHistory']);
        
        // Special Queries
        Route::get('/available/list', [VehicleController::class, 'available']);          
        Route::get('/expiring/insurance', [VehicleController::class, 'expiringInsurance']);
        Route::get('/expiring/registration', [VehicleController::class, 'expiringRegistration']); 
        
        // Reports & Export
        Route::get('/dashboard/stats', [VehicleController::class, 'dashboard']);        
        Route::get('/export/csv', [VehicleController::class, 'export']);  
        
    });

    /*
    |--------------------------------------------------------------------------
    | Quality Reports Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('quality-reports')->group(function () {
        Route::get('/', [QualityReportingController::class, 'index']);
        Route::get('/patient-feedback', [QualityReportingController::class, 'getPatientFeedback']);
        Route::get('/nurse-performance', [QualityReportingController::class, 'getNursePerformance']);
        Route::get('/incident-reports', [QualityReportingController::class, 'getIncidentReports']);
        Route::get('/incident-reports/{incidentId}', [QualityReportingController::class, 'getIncidentReport']);
        Route::get('/quality-metrics', [QualityReportingController::class, 'getQualityMetrics']);
        Route::get('/export/{type}', [QualityReportingController::class, 'exportReport']);
        Route::post('/feedback/{feedbackId}/respond', [QualityReportingController::class, 'respondToFeedback']);
        Route::post('/incidents/{incidentId}/update-status', [QualityReportingController::class, 'updateIncidentStatus']);
        Route::post('/add-incident-report', [QualityReportingController::class, 'addIncidentReport']);
        Route::put('/incident-reports/{incidentId}', [QualityReportingController::class, 'updateIncidentReport']);
        Route::delete('/incident-reports/{incidentId}', [QualityReportingController::class, 'deleteIncidentReport']);
    });

    /*
    |--------------------------------------------------------------------------
    | Reports Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('reports')->group(function () {
        
        // USER MANAGEMENT REPORTS
        Route::get('/user-activity', [ReportController::class, 'userActivityReport'])
            ->name('reports.user-activity');
        Route::get('/role-distribution', [ReportController::class, 'roleDistributionReport'])
            ->name('reports.role-distribution');
        Route::get('/verification-status', [ReportController::class, 'verificationStatusReport'])
            ->name('reports.verification-status');
        
        // CARE MANAGEMENT REPORTS
        Route::get('/care-plan-analytics', [ReportController::class, 'carePlanAnalytics'])
            ->name('reports.care-plan-analytics');
        Route::get('/patient-care-summary', [ReportController::class, 'patientCareSummary'])
            ->name('reports.patient-care-summary');
        Route::get('/care-plan-performance', [ReportController::class, 'carePlanPerformance'])
            ->name('reports.care-plan-performance');

        // TRANSPORT REPORTS
        Route::get('/vehicle-management', [ReportController::class, 'vehicleManagementReport']);
        Route::get('/transport-efficiency', [ReportController::class, 'transportEfficiencyReport']);
        Route::get('/export-transport-reports', [ReportController::class, 'exportTransportReports']);
        
        // NURSE PERFORMANCE REPORTS
        Route::get('/nurse-productivity', [ReportController::class, 'nurseProductivityReport'])
            ->name('reports.nurse-productivity');
        Route::get('/schedule-compliance', [ReportController::class, 'scheduleComplianceReport'])
            ->name('reports.schedule-compliance');
        Route::get('/time-tracking-analytics', [ReportController::class, 'timeTrackingAnalytics'])
            ->name('reports.time-tracking-analytics');
        
        // PATIENT HEALTH REPORTS
        Route::get('/patient-health-trends', [ReportController::class, 'patientHealthTrends'])
            ->name('reports.patient-health-trends');
        Route::get('/progress-notes-analytics', [ReportController::class, 'progressNotesAnalytics'])
            ->name('reports.progress-notes-analytics');
        Route::get('/patient-outcomes', [ReportController::class, 'patientOutcomesReport'])
            ->name('reports.patient-outcomes');
        Route::get('/medical-conditions', [ReportController::class, 'medicalConditionReports'])
            ->name('reports.medical-conditions');
        
        // TRANSPORTATION REPORTS
        Route::get('/transport-utilization-report', [ReportController::class, 'transportUtilizationReport'])
            ->name('reports.transport-utilization');
        Route::get('/driver-performance-report', [ReportController::class, 'driverPerformanceReport'])
            ->name('reports.driver-performance');
        
        // FINANCIAL REPORTS
        Route::get('/cost-analysis', [ReportController::class, 'costAnalysisReport'])
            ->name('reports.cost-analysis');
        Route::get('/service-utilization', [ReportController::class, 'serviceUtilizationReport'])
            ->name('reports.service-utilization');
        Route::get('/revenue-analytics', [ReportController::class, 'revenueAnalytics'])
            ->name('reports.revenue-analytics');
        
        // EXPORT FUNCTIONALITY
        Route::get('/export', [ReportController::class, 'exportReport'])
            ->name('reports.export');
    });

    /*
    |--------------------------------------------------------------------------
    | Profile Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/update', [ProfileController::class, 'update']);
        Route::put('/update-password', [ProfileController::class, 'updatePassword']);
        Route::put('/update-professional', [ProfileController::class, 'updateProfessional']);
        Route::post('/update-avatar', [ProfileController::class, 'updateAvatar']);
        Route::post('/enable-2fa', [ProfileController::class, 'enableTwoFactor']);
        Route::post('/disable-2fa', [ProfileController::class, 'disableTwoFactor']);
        Route::post('/verify-2fa', [ProfileController::class, 'verifyTwoFactor']);
        Route::get('/statistics', [ProfileController::class, 'statistics']);
        Route::put('/update-preferences', [ProfileController::class, 'updatePreferences']);
        Route::delete('/delete-account', [ProfileController::class, 'deleteAccount']);
    });
});

/*
|--------------------------------------------------------------------------
| Role and Permission Routes (Admin/SuperAdmin only)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'role:admin,superadmin'])->group(function () {
    
    // Role Management Routes
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/{role}', [RoleController::class, 'show'])->name('roles.show');
        Route::put('/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
        
        // Permission assignment routes
        Route::post('/{role}/permissions', [RoleController::class, 'assignPermissions'])->name('roles.assign-permissions');
        Route::get('/{role}/permissions', [RoleController::class, 'getPermissions'])->name('roles.get-permissions');
        
        // User assignment routes
        Route::post('/{role}/users', [RoleController::class, 'assignUsers'])->name('roles.assign-users');
        Route::get('/{role}/users', [RoleController::class, 'getUsers'])->name('roles.get-users');
        
        // Role duplication
        Route::post('/{role}/duplicate', [RoleController::class, 'duplicate'])->name('roles.duplicate');
    });

    // Permission Management Routes
    Route::prefix('permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('/flat', [PermissionController::class, 'flat'])->name('permissions.flat');
        Route::get('/categories', [PermissionController::class, 'getCategories'])->name('permissions.categories');
        Route::get('/search', [PermissionController::class, 'search'])->name('permissions.search');
        
        Route::get('/category/{category}', [PermissionController::class, 'getByCategory'])->name('permissions.by-category');
        Route::get('/category/{category}/subcategories', [PermissionController::class, 'getSubcategories'])->name('permissions.subcategories');
        
        Route::get('/{permission}', [PermissionController::class, 'show'])->name('permissions.show');
        Route::get('/{permission}/roles', [PermissionController::class, 'getRoles'])->name('permissions.get-roles');
    });
});


Route::middleware(['auth:web', 'role:admin,superadmin'])->prefix('admin')->group(function () {
    
    // Care Request Management
    Route::prefix('care-requests')->group(function () {
        // List and stats
        Route::get('/', [AdminCareRequestController::class, 'index']);
        Route::get('/statistics', [AdminCareRequestController::class, 'getStatistics']);
        Route::get('/nurses', [AdminCareRequestController::class, 'getAvailableNurses']);
        Route::get('/export', [AdminCareRequestController::class, 'export']); 

        
        // Actions
        Route::post('/{id}/assign-nurse', [AdminCareRequestController::class, 'assignNurse']);
        Route::post('/{id}/schedule-assessment', [AdminCareRequestController::class, 'scheduleAssessment']);
        Route::post('/{id}/complete-assessment', [AdminCareRequestController::class, 'completeAssessment']);
        Route::post('/{id}/issue-care-cost', [AdminCareRequestController::class, 'issueCareCost']);
        Route::post('/{id}/start-care', [AdminCareRequestController::class, 'startCare']);
        Route::post('/{id}/reject', [AdminCareRequestController::class, 'reject']);
        Route::post('/{id}/update-status', [AdminCareRequestController::class, 'updateStatus']);
        Route::get('/patients', [AdminCareRequestController::class, 'getPatients']);
        Route::post('/create', [AdminCareRequestController::class, 'createRequest']);

    });
    
});

/*
|--------------------------------------------------------------------------
| Vue SPA Catch-All Route
|--------------------------------------------------------------------------
| CRITICAL: This MUST be the LAST route in the file!
| It catches all undefined routes and serves the Vue app.
*/
Route::get('/{any}', function () {
    return view('welcome'); // Blade that mounts your Vue app
})->where('any', '.*');