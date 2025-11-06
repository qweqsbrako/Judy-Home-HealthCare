<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\CarePlan;
use App\Models\Schedule;
use App\Models\TimeTracking;
use App\Models\ProgressNote;
// use App\Models\MedicalAssessment; // Commented out until table is created
use App\Models\Driver;
use App\Models\TransportRequest;
use App\Models\IncidentReport;

class DashboardController extends Controller
{
    /**
     * Main dashboard endpoint - returns data based on authenticated user's role
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        switch ($user->role) {
            case 'patient':
                return $this->patientDashboard($user);
            case 'nurse':
                return $this->nurseDashboard($user);
            case 'doctor':
                return $this->doctorDashboard($user);
            case 'admin':
            case 'superadmin':
                return $this->adminDashboard($user);
            default:
                return response()->json(['error' => 'Invalid user role'], 403);
        }
    }

    /**
     * ================================
     * PATIENT DASHBOARD
     * ================================
     */
    private function patientDashboard($user)
    {
        // Total care sessions
        $totalSessions = ProgressNote::where('patient_id', $user->id)->count();
        
        // Vitals recorded
        $vitalsRecorded = ProgressNote::where('patient_id', $user->id)
            ->whereNotNull('vitals')
            ->count();
        
        // Assigned nurses (unique nurses who have cared for this patient through schedules)
        $assignedNurses = Schedule::whereHas('carePlan', function($q) use ($user) {
                $q->where('patient_id', $user->id);
            })
            ->distinct('nurse_id')
            ->count('nurse_id');
        
        // Financial summary (mock data - would need actual payment/billing tables)
        $totalSpent = 0; // Replace with actual query
        $pendingBills = 0; // Replace with actual query
        $insuranceCovered = 0; // Replace with actual query
        
        // Next appointment
        $nextAppointment = Schedule::whereHas('carePlan', function($q) use ($user) {
                $q->where('patient_id', $user->id);
            })
            ->where('schedule_date', '>=', Carbon::today())
            ->where('status', '!=', 'cancelled')
            ->with('nurse:id,first_name,last_name')
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->first();
        
        // Current care plan
        $carePlan = CarePlan::where('patient_id', $user->id)
            ->where('status', 'active')
            ->with('doctor:id,first_name,last_name')
            ->first();
        
        // Recent activity
        $recentActivity = $this->getPatientActivity($user->id);
        
        // Health reminders (mock data - would need reminder table)
        $healthReminders = $this->getHealthReminders($user->id);
        
        // Alerts
        $alerts = $this->getPatientAlerts($user->id);

        return response()->json([
            'user' => $user,
            'stats' => [
                'totalSessions' => $totalSessions,
                'vitalsRecorded' => $vitalsRecorded,
                'assignedNurses' => $assignedNurses,
                'totalSpent' => $totalSpent,
                'pendingBills' => $pendingBills,
                'insuranceCovered' => $insuranceCovered,
                'nextAppointment' => $nextAppointment ? [
                    'time' => Carbon::parse($nextAppointment->start_time)->format('g:i A'),
                    'date' => Carbon::parse($nextAppointment->schedule_date)->isToday() 
                        ? 'Today' 
                        : Carbon::parse($nextAppointment->schedule_date)->format('M d, Y'),
                    'nurse' => $nextAppointment->nurse 
                        ? $nextAppointment->nurse->first_name . ' ' . $nextAppointment->nurse->last_name . ', RN'
                        : 'Not assigned',
                    'type' => $nextAppointment->shift_type ?? 'General Care'
                ] : [
                    'time' => 'No upcoming',
                    'date' => 'appointments',
                    'nurse' => 'N/A',
                    'type' => 'N/A'
                ],
                'carePlan' => $carePlan ? [
                    'title' => $carePlan->title ?? 'Active Care Plan',
                    'description' => $carePlan->description ?? 'Comprehensive care program',
                    'progress' => $carePlan->completion_percentage ?? 0,
                    'tasks' => $this->getCarePlanTasks($carePlan->id)
                ] : null
            ],
            'recentActivity' => $recentActivity,
            'healthReminders' => $healthReminders,
            'alerts' => $alerts
        ]);
    }

    /**
     * ================================
     * NURSE DASHBOARD
     * ================================
     */
    private function nurseDashboard($user)
    {
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        
        // Active patients (unique patients with active care plans assigned to this nurse)
        $activePatients = DB::table('schedules')
            ->join('care_plans', 'schedules.care_plan_id', '=', 'care_plans.id')
            ->where('schedules.nurse_id', $user->id)
            ->where('schedules.schedule_date', '>=', $today)
            ->where('care_plans.status', 'active')
            ->whereNull('schedules.deleted_at')
            ->whereNull('care_plans.deleted_at')
            ->distinct('care_plans.patient_id')
            ->count('care_plans.patient_id');
        
        // New patients this week (patients with care plans created this week and assigned to this nurse)
        $newPatients = DB::table('schedules')
            ->join('care_plans', 'schedules.care_plan_id', '=', 'care_plans.id')
            ->where('schedules.nurse_id', $user->id)
            ->where('schedules.schedule_date', '>=', $weekStart)
            ->where('care_plans.created_at', '>=', $weekStart)
            ->whereNull('schedules.deleted_at')
            ->whereNull('care_plans.deleted_at')
            ->distinct('care_plans.patient_id')
            ->count('care_plans.patient_id');
        
        // Critical patients
        $criticalPatients = DB::table('schedules')
            ->join('care_plans', 'schedules.care_plan_id', '=', 'care_plans.id')
            ->where('schedules.nurse_id', $user->id)
            ->where('schedules.schedule_date', '>=', $today)
            ->where('care_plans.priority', 'critical')
            ->where('care_plans.status', 'active')
            ->whereNull('schedules.deleted_at')
            ->whereNull('care_plans.deleted_at')
            ->distinct('care_plans.patient_id')
            ->count('care_plans.patient_id');
        
        // Hours worked this week
        $hoursWorked = TimeTracking::where('nurse_id', $user->id)
            ->where('start_time', '>=', $weekStart)
            ->where('status', 'completed')
            ->sum('total_duration_minutes') / 60;
        
        // Hours worked today
        $hoursToday = TimeTracking::where('nurse_id', $user->id)
            ->whereDate('start_time', $today)
            ->where('status', 'completed')
            ->sum('total_duration_minutes') / 60;
        
        // Overtime hours this week
        $overtimeHours = TimeTracking::where('nurse_id', $user->id)
            ->where('start_time', '>=', $weekStart)
            ->where('status', 'completed')
            ->where('total_duration_minutes', '>', 480) // More than 8 hours
            ->sum(DB::raw('CASE WHEN total_duration_minutes > 480 THEN total_duration_minutes - 480 ELSE 0 END')) / 60;
        
        // Average rating (mock - would need rating table)
        $avgRating = 4.8;
        
        // Vitals recorded this month
        $vitalsRecorded = ProgressNote::where('nurse_id', $user->id)
            ->whereMonth('visit_date', Carbon::now()->month)
            ->whereNotNull('vitals')
            ->count();
        
        // Notes written this month
        $notesWritten = ProgressNote::where('nurse_id', $user->id)
            ->whereMonth('visit_date', Carbon::now()->month)
            ->count();
        
        // Today's schedule
        $todaysSchedule = Schedule::where('nurse_id', $user->id)
            ->whereDate('schedule_date', $today)
            ->with(['carePlan.patient:id,first_name,last_name', 'carePlan:id,care_type,priority,patient_id'])
            ->orderBy('start_time')
            ->get()
            ->map(function($schedule) {
                return [
                    'id' => $schedule->id,
                    'time' => Carbon::parse($schedule->start_time)->format('H:i'),
                    'duration' => $schedule->duration ?? '1h',
                    'patient_name' => $schedule->carePlan && $schedule->carePlan->patient 
                        ? $schedule->carePlan->patient->first_name . ' ' . $schedule->carePlan->patient->last_name
                        : 'Unknown Patient',
                    'patient_id' => $schedule->carePlan->patient_id ?? null,
                    'care_type' => $schedule->carePlan->care_type ?? 'General Care',
                    'location' => $schedule->location ?? 'Not specified',
                    'status' => $schedule->status,
                    'priority' => $schedule->carePlan->priority ?? 'medium'
                ];
            });
        
        // Recent patients
        $recentPatients = $this->getNurseRecentPatients($user->id);
        
        // Recent activity
        $recentActivity = $this->getNurseActivity($user->id);
        
        // Alerts
        $alerts = $this->getNurseAlerts($user->id);

        return response()->json([
            'user' => $user,
            'stats' => [
                'activePatients' => $activePatients,
                'newPatients' => $newPatients,
                'criticalPatients' => $criticalPatients,
                'hoursWorked' => round($hoursWorked, 1),
                'hoursToday' => round($hoursToday, 1),
                'overtimeHours' => round($overtimeHours, 1),
                'avgRating' => $avgRating,
                'vitalsRecorded' => $vitalsRecorded,
                'notesWritten' => $notesWritten
            ],
            'todaysSchedule' => $todaysSchedule,
            'recentPatients' => $recentPatients,
            'recentActivity' => $recentActivity,
            'alerts' => $alerts
        ]);
    }

    /**
     * ================================
     * DOCTOR DASHBOARD
     * ================================
     */
    private function doctorDashboard($user)
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        
        // Active care plans created by this doctor
        $activePlans = CarePlan::where('doctor_id', $user->id)
            ->where('status', 'active')
            ->count();
        
        // Patients under care
        $patientsUnderCare = CarePlan::where('doctor_id', $user->id)
            ->where('status', 'active')
            ->distinct('patient_id')
            ->count('patient_id');
        
        // New patients this month
        $newPatients = CarePlan::where('doctor_id', $user->id)
            ->where('created_at', '>=', $thisMonth)
            ->distinct('patient_id')
            ->count('patient_id');
        
        // Recent assessments - TEMPORARY FIX: Using mock data until MedicalAssessment table is created
        $recentAssessments = 0; // Would be: MedicalAssessment::where('doctor_id', $user->id)->whereMonth('created_at', Carbon::now()->month)->count();
        
        // Pending reviews (care plans needing approval)
        $pendingReviews = CarePlan::where('status', 'pending_approval')
            ->count();
        
        // Care team schedules (schedules for this doctor's care plans)
        $careTeamSchedules = $this->getDoctorCareTeamSchedules($user->id);
        
        // Recent activity
        $recentActivity = $this->getDoctorActivity($user->id);
        
        // Alerts
        $alerts = $this->getDoctorAlerts($user->id);

        return response()->json([
            'user' => $user,
            'stats' => [
                'activePlans' => $activePlans,
                'patientsUnderCare' => $patientsUnderCare,
                'newPatients' => $newPatients,
                'recentAssessments' => $recentAssessments,
                'pendingReviews' => $pendingReviews
            ],
            'careTeamSchedules' => $careTeamSchedules,
            'recentActivity' => $recentActivity,
            'alerts' => $alerts
        ]);
    }

    /**
     * ================================
     * ADMIN DASHBOARD
     * ================================
     */
    private function adminDashboard($user)
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $thisWeek = Carbon::now()->startOfWeek();
        
        // Total users
        $totalUsers = User::count();
        
        // Active users (logged in within last 7 days)
        $activeUsers = User::where('last_login_at', '>=', Carbon::now()->subDays(7))
            ->count();
        
        // Pending verifications
        $pendingVerifications = User::where('verification_status', 'pending')
            ->count();
        
        // Monthly revenue (mock - would need actual billing tables)
        $monthlyRevenue = 0;
        
        // Revenue growth (mock)
        $revenueGrowth = 0;
        
        // Average bill (mock)
        $avgBill = 0;
        
        // Open incidents
        $openIncidents = IncidentReport::whereIn('status', ['open', 'investigating'])
            ->count();
        
        // Resolved incidents today
        $resolvedIncidents = IncidentReport::where('status', 'resolved')
            ->whereDate('updated_at', $today)
            ->count();
        
        $qualityScore = 0;
        
        // Recent activity
        $recentActivity = $this->getAdminActivity();
        
        // Alerts
        $alerts = $this->getAdminAlerts();
        
        // Recent finance records
        $recentFinanceRecords = $this->getRecentFinanceRecords();
        
        // Transportation requests
        $transportationRequests = TransportRequest::whereIn('status', ['pending', 'assigned'])
            ->with(['patient:id,first_name,last_name', 'driver:id,first_name,last_name'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($request) {
                return [
                    'id' => $request->id,
                    'type' => $request->transport_type,
                    'destination' => $request->destination,
                    'patientName' => $request->patient 
                        ? $request->patient->first_name . ' ' . $request->patient->last_name
                        : 'Unknown',
                    'pickupLocation' => $request->pickup_location,
                    'scheduledTime' => $request->scheduled_pickup_time 
                        ? Carbon::parse($request->scheduled_pickup_time)->format('g:i A M d')
                        : 'ASAP',
                    'priority' => $request->priority,
                    'status' => $request->status
                ];
            });
        
        // Care management schedules
        $careManagementSchedules = $this->getCareManagementSchedules();
        
        // Recent nurse applications
        $recentNurseApplications = User::where('role', 'nurse')
            ->whereIn('verification_status', ['pending', 'under-review'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($nurse) {
                return [
                    'id' => $nurse->id,
                    'name' => $nurse->first_name . ' ' . $nurse->last_name,
                    'avatar' => $nurse->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($nurse->first_name . '+' . $nurse->last_name) . '&background=e3f2fd',
                    'credentials' => $nurse->license_number ?? 'RN, BSN',
                    'experience' => $nurse->years_experience ?? 0,
                    'specialization' => $nurse->specialization ?? 'General Care',
                    'status' => $nurse->verification_status,
                    'appliedAt' => $nurse->created_at
                ];
            });
        
        // Application summary
        $pendingApplications = User::where('role', 'nurse')
            ->where('verification_status', 'pending')
            ->count();
        
        $underReviewApplications = User::where('role', 'nurse')
            ->where('verification_status', 'under-review')
            ->count();
        
        $approvedThisWeek = User::where('role', 'nurse')
            ->where('verification_status', 'verified')
            ->where('verified_at', '>=', $thisWeek)
            ->count();

        return response()->json([
            'user' => $user,
            'stats' => [
                'totalUsers' => $totalUsers,
                'activeUsers' => $activeUsers,
                'pendingVerifications' => $pendingVerifications,
                'monthlyRevenue' => $monthlyRevenue,
                'revenueGrowth' => $revenueGrowth,
                'avgBill' => $avgBill,
                'openIncidents' => $openIncidents,
                'resolvedIncidents' => $resolvedIncidents,
                'qualityScore' => $qualityScore
            ],
            'recentActivity' => $recentActivity,
            'alerts' => $alerts,
            'recentFinanceRecords' => $recentFinanceRecords,
            'transportationRequests' => $transportationRequests,
            'careManagementSchedules' => $careManagementSchedules,
            'recentNurseApplications' => $recentNurseApplications,
            'applicationSummary' => [
                'pendingApplications' => $pendingApplications,
                'underReviewApplications' => $underReviewApplications,
                'approvedThisWeek' => $approvedThisWeek
            ],
            'financeSummary' => [
                'dailyRevenue' => 2450,
                'pendingPayments' => 1850
            ]
        ]);
    }

    /**
     * ================================
     * HELPER METHODS
     * ================================
     */

    private function getPatientActivity($patientId)
    {
        $activities = collect();
        
        // Recent vitals
        $vitals = ProgressNote::where('patient_id', $patientId)
            ->whereNotNull('vitals')
            ->orderBy('visit_date', 'desc')
            ->limit(3)
            ->with('nurse:id,first_name,last_name')
            ->get()
            ->map(function($note) {
                return [
                    'id' => $note->id,
                    'type' => 'vitals',
                    'title' => 'Vitals Recorded',
                    'description' => 'Vital signs recorded during care visit',
                    'user' => $note->nurse ? $note->nurse->first_name . ' ' . $note->nurse->last_name . ', RN' : 'Nurse',
                    'status' => 'completed',
                    'created_at' => $note->visit_date
                ];
            });
        
        return $vitals->take(5);
    }

    private function getNurseActivity($nurseId)
    {
        $activities = collect();
        
        // Recent notes
        $notes = ProgressNote::where('nurse_id', $nurseId)
            ->orderBy('visit_date', 'desc')
            ->limit(5)
            ->with('patient:id,first_name,last_name')
            ->get()
            ->map(function($note) {
                return [
                    'id' => $note->id,
                    'type' => 'vitals',
                    'title' => 'Care Notes Recorded',
                    'description' => 'Care notes for ' . ($note->patient ? $note->patient->first_name . ' ' . $note->patient->last_name : 'patient'),
                    'user' => 'You',
                    'status' => 'completed',
                    'created_at' => $note->visit_date
                ];
            });
        
        // Recent incidents (if any were reported by this nurse)
        $incidents = IncidentReport::where('reported_by', $nurseId)
            ->orderBy('created_at', 'desc')
            ->limit(2)
            ->with('patient:id,first_name,last_name')
            ->get()
            ->map(function($incident) {
                return [
                    'id' => $incident->id,
                    'type' => 'incident',
                    'title' => 'Incident Reported',
                    'description' => $incident->incident_type . ' incident reported',
                    'user' => 'You',
                    'status' => $incident->status,
                    'created_at' => $incident->created_at
                ];
            });
        
        return $activities->merge($notes)->merge($incidents)->sortByDesc('created_at')->take(5)->values();
    }

    private function getDoctorActivity($doctorId)
    {
        // Recent care plans
        $carePlans = CarePlan::where('doctor_id', $doctorId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->with('patient:id,first_name,last_name')
            ->get()
            ->map(function($plan) {
                return [
                    'id' => $plan->id,
                    'type' => 'assignment',
                    'title' => 'Care Plan Created',
                    'description' => 'New care plan for ' . ($plan->patient ? $plan->patient->first_name . ' ' . $plan->patient->last_name : 'patient'),
                    'user' => 'You',
                    'status' => $plan->status,
                    'created_at' => $plan->created_at
                ];
            });
        
        return $carePlans;
    }

    private function getAdminActivity()
    {
        $activities = collect();
        
        // Recent user verifications
        $verifications = User::whereNotNull('verified_at')
            ->orderBy('verified_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'type' => 'assignment',
                    'title' => 'User Verified',
                    'description' => $user->first_name . ' ' . $user->last_name . ' (' . $user->role . ') has been verified',
                    'user' => 'Admin Portal',
                    'status' => 'completed',
                    'created_at' => $user->verified_at
                ];
            });
        
        // Recent incidents
        $incidents = IncidentReport::orderBy('created_at', 'desc')
            ->limit(2)
            ->with('reporter:id,first_name,last_name')
            ->get()
            ->map(function($incident) {
                return [
                    'id' => $incident->id,
                    'type' => 'incident',
                    'title' => 'Incident Reported',
                    'description' => $incident->incident_type . ' - ' . $incident->severity,
                    'user' => $incident->reporter ? $incident->reporter->first_name . ' ' . $incident->reporter->last_name : 'Staff',
                    'status' => $incident->status,
                    'created_at' => $incident->created_at
                ];
            });
        
        return $activities->merge($verifications)->merge($incidents)->sortByDesc('created_at')->take(5)->values();
    }

    private function getPatientAlerts($patientId)
    {
        $alerts = collect();
        
        // Check for abnormal vitals
        $recentVitals = ProgressNote::where('patient_id', $patientId)
            ->whereNotNull('vitals')
            ->where('visit_date', '>=', Carbon::now()->subDays(7))
            ->orderBy('visit_date', 'desc')
            ->first();
        
        if ($recentVitals && isset($recentVitals->vitals['blood_pressure'])) {
            // Example: Check for high BP
            $alerts->push([
                'id' => 1,
                'type' => 'warning',
                'title' => 'Vital Signs Review',
                'message' => 'Your recent blood pressure reading requires monitoring',
                'actionRequired' => false,
                'created_at' => $recentVitals->visit_date
            ]);
        }
        
        return $alerts;
    }

    private function getNurseAlerts($nurseId)
    {
        $alerts = collect();
        
        // Critical patients using proper query structure
        $criticalPatients = DB::table('schedules')
            ->join('care_plans', 'schedules.care_plan_id', '=', 'care_plans.id')
            ->where('schedules.nurse_id', $nurseId)
            ->where('schedules.schedule_date', '>=', Carbon::today())
            ->where('care_plans.priority', 'critical')
            ->whereNull('schedules.deleted_at')
            ->whereNull('care_plans.deleted_at')
            ->count();
        
        if ($criticalPatients > 0) {
            $alerts->push([
                'id' => 1,
                'type' => 'critical',
                'title' => 'Critical Patient Alert',
                'message' => "You have {$criticalPatients} critical priority patient(s) in your schedule",
                'actionRequired' => true,
                'created_at' => Carbon::now()
            ]);
        }
        
        return $alerts;
    }

    private function getDoctorAlerts($doctorId)
    {
        $alerts = collect();
        
        // Pending reviews
        $pendingReviews = CarePlan::where('status', 'pending_approval')
            ->count();
        
        if ($pendingReviews > 0) {
            $alerts->push([
                'id' => 1,
                'type' => 'warning',
                'title' => 'Pending Reviews',
                'message' => "{$pendingReviews} care plan(s) awaiting your approval",
                'actionRequired' => true,
                'created_at' => Carbon::now()
            ]);
        }
        
        return $alerts;
    }

    private function getAdminAlerts()
    {
        $alerts = collect();
        
        // Pending verifications
        $pendingVerifications = User::where('verification_status', 'pending')->count();
        
        if ($pendingVerifications > 0) {
            $alerts->push([
                'id' => 1,
                'type' => 'warning',
                'title' => 'Pending User Verifications',
                'message' => "{$pendingVerifications} user(s) awaiting verification",
                'actionRequired' => true,
                'created_at' => Carbon::now()
            ]);
        }
        
        // Open incidents
        $openIncidents = IncidentReport::where('status', 'open')->count();
        
        if ($openIncidents > 0) {
            $alerts->push([
                'id' => 2,
                'type' => 'critical',
                'title' => 'Open Incident Reports',
                'message' => "{$openIncidents} incident(s) require immediate attention",
                'actionRequired' => true,
                'created_at' => Carbon::now()
            ]);
        }
        
        return $alerts;
    }

    private function getHealthReminders($patientId)
    {
        // Mock data - would need actual reminder/medication tables
        return [];
    }

    private function getCarePlanTasks($carePlanId)
    {
        // Mock data - would need actual task table
        return [];
    }

    private function getNurseRecentPatients($nurseId)
    {
        return Schedule::where('nurse_id', $nurseId)
            ->where('schedule_date', '>=', Carbon::now()->subDays(7))
            ->where('status', 'completed')
            ->with([
                'carePlan:id,patient_id,care_type',
                'carePlan.patient:id,first_name,last_name,avatar'
            ])
            ->orderBy('schedule_date', 'desc')
            ->limit(10)
            ->get()
            ->unique(function($schedule) {
                return $schedule->carePlan && $schedule->carePlan->patient ? $schedule->carePlan->patient->id : null;
            })
            ->filter(function($schedule) {
                return $schedule->carePlan && $schedule->carePlan->patient;
            })
            ->map(function($schedule) {
                return [
                    'id' => $schedule->carePlan->patient->id,
                    'name' => $schedule->carePlan->patient->first_name . ' ' . $schedule->carePlan->patient->last_name,
                    'condition' => $schedule->carePlan->care_type ?? 'General Care',
                    'avatar' => $schedule->carePlan->patient->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($schedule->carePlan->patient->first_name . '+' . $schedule->carePlan->patient->last_name) . '&background=e3f2fd',
                    'status' => 'stable',
                    'lastVisit' => Carbon::parse($schedule->schedule_date)->diffForHumans()
                ];
            })
            ->take(5)
            ->values();
    }

    private function getRecentFinanceRecords()
    {
        // Mock data - would need actual payment/billing tables
        return [];
    }

    private function getCareManagementSchedules()
    {
        $today = Carbon::today();
        
        return User::where('role', 'nurse')
            ->where('is_active', true)
            ->limit(4)
            ->get()
            ->map(function($nurse) use ($today) {
                $totalScheduled = Schedule::where('nurse_id', $nurse->id)
                    ->whereDate('schedule_date', $today)
                    ->count();
                
                $completed = Schedule::where('nurse_id', $nurse->id)
                    ->whereDate('schedule_date', $today)
                    ->where('status', 'completed')
                    ->count();
                
                return [
                    'id' => $nurse->id,
                    'nurseName' => $nurse->first_name . ' ' . $nurse->last_name,
                    'nurseAvatar' => $nurse->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($nurse->first_name . '+' . $nurse->last_name) . '&background=e3f2fd',
                    'specialty' => $nurse->specialization ?? 'General Care',
                    'date' => 'Today',
                    'totalPatients' => $totalScheduled,
                    'completedVisits' => $completed,
                    'pendingVisits' => $totalScheduled - $completed
                ];
            });
    }

    /**
     * Get care team schedules for doctor's care plans
     */
    private function getDoctorCareTeamSchedules($doctorId)
    {
        $today = Carbon::today();
        $nextWeek = Carbon::now()->addWeek();
        
        // Get care plans created by this doctor
        $carePlanIds = CarePlan::where('doctor_id', $doctorId)
            ->where('status', 'active')
            ->pluck('id');
        
        if ($carePlanIds->isEmpty()) {
            return [];
        }
        
        // Get schedules for these care plans for the next 7 days
        $schedules = Schedule::whereIn('care_plan_id', $carePlanIds)
            ->where('schedule_date', '>=', $today)
            ->where('schedule_date', '<=', $nextWeek)
            ->with([
                'nurse:id,first_name,last_name,specialization,avatar',
                'carePlan:id,title,care_type,priority,patient_id',
                'carePlan.patient:id,first_name,last_name,avatar'
            ])
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->get()
            ->groupBy(function($schedule) {
                return Carbon::parse($schedule->schedule_date)->format('Y-m-d');
            })
            ->map(function($daySchedules, $date) {
                $dateObj = Carbon::parse($date);
                return [
                    'date' => $date,
                    'dateDisplay' => $dateObj->isToday() ? 'Today' : 
                                   ($dateObj->isTomorrow() ? 'Tomorrow' : $dateObj->format('M d, Y')),
                    'dayOfWeek' => $dateObj->format('l'),
                    'schedules' => $daySchedules->map(function($schedule) {
                        return [
                            'id' => $schedule->id,
                            'time' => Carbon::parse($schedule->start_time)->format('g:i A'),
                            'endTime' => $schedule->end_time ? Carbon::parse($schedule->end_time)->format('g:i A') : null,
                            'duration' => $schedule->duration ?? '1h',
                            'status' => $schedule->status,
                            'nurse' => [
                                'id' => $schedule->nurse->id ?? null,
                                'name' => $schedule->nurse ? 
                                    $schedule->nurse->first_name . ' ' . $schedule->nurse->last_name : 
                                    'Unassigned',
                                'specialization' => $schedule->nurse->specialization ?? 'General Care',
                                'avatar' => $schedule->nurse->avatar ?? 
                                    'https://ui-avatars.com/api/?name=' . urlencode(($schedule->nurse->first_name ?? 'U') . '+' . ($schedule->nurse->last_name ?? 'A')) . '&background=e3f2fd'
                            ],
                            'patient' => [
                                'id' => $schedule->carePlan->patient->id ?? null,
                                'name' => $schedule->carePlan->patient ? 
                                    $schedule->carePlan->patient->first_name . ' ' . $schedule->carePlan->patient->last_name : 
                                    'Unknown Patient',
                                'avatar' => $schedule->carePlan->patient->avatar ?? 
                                    'https://ui-avatars.com/api/?name=' . urlencode(($schedule->carePlan->patient->first_name ?? 'P') . '+' . ($schedule->carePlan->patient->last_name ?? 'A')) . '&background=f3e5f5'
                            ],
                            'carePlan' => [
                                'id' => $schedule->carePlan->id ?? null,
                                'title' => $schedule->carePlan->title ?? 'Care Plan',
                                'careType' => $schedule->carePlan->care_type ?? 'General Care',
                                'priority' => $schedule->carePlan->priority ?? 'medium'
                            ],
                            'location' => $schedule->location ?? 'Patient Home',
                            'notes' => $schedule->notes
                        ];
                    })->values()
                ];
            })
            ->values()
            ->take(7); // Limit to 7 days
        
        return $schedules;
    }
}