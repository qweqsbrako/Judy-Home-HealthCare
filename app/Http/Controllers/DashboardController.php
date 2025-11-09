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
use App\Models\Driver;
use App\Models\TransportRequest;
use App\Models\IncidentReport;
use App\Models\CareRequest;
use App\Models\CarePayment;

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
        
        // Financial summary from CarePayment
        $totalSpent = CarePayment::where('patient_id', $user->id)
            ->where('status', 'completed')
            ->sum('total_amount');
        
        $pendingBills = CarePayment::where('patient_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->sum('total_amount');
        
        // Insurance covered (if you have this data, otherwise set to 0)
        $insuranceCovered = 0;
        
        // Next appointment
        $nextAppointment = Schedule::whereHas('carePlan', function($q) use ($user) {
                $q->where('patient_id', $user->id);
            })
            ->where('schedule_date', '>=', Carbon::today())
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with('nurse:id,first_name,last_name')
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->first();
        
        // Current care plan
        $carePlan = CarePlan::where('patient_id', $user->id)
            ->where('status', 'active')
            ->with('doctor:id,first_name,last_name')
            ->first();
        
        // Upcoming appointments for the week
        $upcomingAppointments = $this->getPatientUpcomingAppointments($user->id);
        
        // Recent activity
        $recentActivity = $this->getPatientActivity($user->id);
        
        // Health reminders
        $healthReminders = $this->getHealthReminders($user->id);
        
        // Alerts (including care requests)
        $alerts = $this->getPatientAlerts($user->id);

        return response()->json([
            'user' => $user,
            'stats' => [
                'totalSessions' => $totalSessions,
                'vitalsRecorded' => $vitalsRecorded,
                'assignedNurses' => $assignedNurses,
                'totalSpent' => round($totalSpent, 2),
                'pendingBills' => round($pendingBills, 2),
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
            'upcomingAppointments' => $upcomingAppointments,
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
        
        // Active patients
        $activePatients = DB::table('schedules')
            ->join('care_plans', 'schedules.care_plan_id', '=', 'care_plans.id')
            ->where('schedules.nurse_id', $user->id)
            ->where('schedules.schedule_date', '>=', $today)
            ->where('care_plans.status', 'active')
            ->whereNull('schedules.deleted_at')
            ->whereNull('care_plans.deleted_at')
            ->distinct('care_plans.patient_id')
            ->count('care_plans.patient_id');
        
        // New patients this week
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
            ->where('total_duration_minutes', '>', 480)
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
        
        // Today's schedule with full details
        $todaysSchedule = Schedule::where('nurse_id', $user->id)
            ->whereDate('schedule_date', $today)
            ->with([
                'carePlan.patient:id,first_name,last_name,avatar',
                'carePlan:id,care_type,priority,patient_id'
            ])
            ->orderBy('start_time')
            ->get()
            ->map(function($schedule) {
                $startTime = Carbon::parse($schedule->schedule_date . ' ' . $schedule->start_time);
                $endTime = Carbon::parse($schedule->schedule_date . ' ' . $schedule->end_time);
                $durationMinutes = $startTime->diffInMinutes($endTime);
                $durationHours = floor($durationMinutes / 60);
                $durationRemainder = $durationMinutes % 60;
                $duration = $durationHours > 0 
                    ? ($durationRemainder > 0 ? "{$durationHours}h {$durationRemainder}m" : "{$durationHours}h")
                    : "{$durationRemainder}m";

                return [
                    'id' => $schedule->id,
                    'time' => $startTime->format('g:i A'),
                    'duration' => $duration,
                    'patient_name' => $schedule->carePlan && $schedule->carePlan->patient 
                        ? $schedule->carePlan->patient->first_name . ' ' . $schedule->carePlan->patient->last_name
                        : 'Unknown Patient',
                    'patient_id' => $schedule->carePlan->patient_id ?? null,
                    'patient_avatar' => $schedule->carePlan && $schedule->carePlan->patient 
                        ? ($schedule->carePlan->patient->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($schedule->carePlan->patient->first_name . '+' . $schedule->carePlan->patient->last_name) . '&background=e3f2fd')
                        : 'https://ui-avatars.com/api/?name=Unknown&background=e3f2fd',
                    'care_type' => $schedule->carePlan->care_type ?? 'General Care',
                    'location' => $schedule->location ?? 'Patient Home',
                    'status' => $schedule->status,
                    'priority' => $schedule->carePlan->priority ?? 'medium'
                ];
            });
        
        // Upcoming appointments
        $upcomingAppointments = $this->getNurseUpcomingAppointments($user->id);
        
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
            'upcomingAppointments' => $upcomingAppointments,
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
        
        // Recent assessments
        $recentAssessments = 0;
        
        // Pending reviews
        $pendingReviews = CarePlan::where('status', 'pending_approval')
            ->count();
        
        // Upcoming appointments
        $upcomingAppointments = $this->getDoctorUpcomingAppointments($user->id);
        
        // Care team schedules
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
            'upcomingAppointments' => $upcomingAppointments,
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
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $thisWeek = Carbon::now()->startOfWeek();
        
        // Total users
        $totalUsers = User::count();
        
        // Active users
        $activeUsers = User::where('last_login_at', '>=', Carbon::now()->subDays(7))
            ->count();
        
        // Pending verifications
        $pendingVerifications = User::where('verification_status', 'pending')
            ->count();
        
        // Monthly revenue from CarePayment
        $monthlyRevenue = CarePayment::where('status', 'completed')
            ->where('paid_at', '>=', $thisMonth)
            ->sum('total_amount');
        
        // Last month revenue for growth calculation
        $lastMonthRevenue = CarePayment::where('status', 'completed')
            ->where('paid_at', '>=', $lastMonth)
            ->where('paid_at', '<', $thisMonth)
            ->sum('total_amount');
        
        // Revenue growth percentage
        $revenueGrowth = $lastMonthRevenue > 0 
            ? round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;
        
        // Average bill
        $completedPayments = CarePayment::where('status', 'completed')
            ->where('paid_at', '>=', $thisMonth)
            ->count();
        $avgBill = $completedPayments > 0 ? round($monthlyRevenue / $completedPayments, 2) : 0;
        
        // Open incidents
        $openIncidents = IncidentReport::whereIn('status', ['pending', 'investigated'])
            ->count();
        
        // Resolved incidents today
        $resolvedIncidents = IncidentReport::where('status', 'resolved')
            ->whereDate('updated_at', $today)
            ->count();
        
        // Quality score (percentage of completed schedules vs total)
        $totalSchedules = Schedule::where('schedule_date', '<', $today)
            ->where('schedule_date', '>=', $thisMonth)
            ->count();
        $completedSchedules = Schedule::where('schedule_date', '<', $today)
            ->where('schedule_date', '>=', $thisMonth)
            ->where('status', 'completed')
            ->count();
        $qualityScore = $totalSchedules > 0 
            ? round(($completedSchedules / $totalSchedules) * 100, 1)
            : 0;
        
        // Recent activity
        $recentActivity = $this->getAdminActivity();
        
        // Alerts (including care requests)
        $alerts = $this->getAdminAlerts();
        
        // Recent finance records
        $recentFinanceRecords = $this->getRecentFinanceRecords();
        
        // Recent care requests
        $recentCareRequests = $this->getRecentCareRequests();
        
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
        
        // Daily revenue
        $dailyRevenue = CarePayment::where('status', 'completed')
            ->whereDate('paid_at', $today)
            ->sum('total_amount');
        
        // Pending payments
        $pendingPayments = CarePayment::whereIn('status', ['pending', 'processing'])
            ->sum('total_amount');
        
        // Upcoming appointments (all schedules)
        $upcomingAppointments = $this->getAdminUpcomingAppointments();

        return response()->json([
            'user' => $user,
            'stats' => [
                'totalUsers' => $totalUsers,
                'activeUsers' => $activeUsers,
                'pendingVerifications' => $pendingVerifications,
                'monthlyRevenue' => round($monthlyRevenue, 2),
                'revenueGrowth' => $revenueGrowth,
                'avgBill' => $avgBill,
                'openIncidents' => $openIncidents,
                'resolvedIncidents' => $resolvedIncidents,
                'qualityScore' => $qualityScore
            ],
            'recentActivity' => $recentActivity,
            'alerts' => $alerts,
            'upcomingAppointments' => $upcomingAppointments,
            'recentFinanceRecords' => $recentFinanceRecords,
            'recentCareRequests' => $recentCareRequests,
            'transportationRequests' => $transportationRequests,
            'careManagementSchedules' => $careManagementSchedules,
            'recentNurseApplications' => $recentNurseApplications,
            'applicationSummary' => [
                'pendingApplications' => $pendingApplications,
                'underReviewApplications' => $underReviewApplications,
                'approvedThisWeek' => $approvedThisWeek
            ],
            'financeSummary' => [
                'dailyRevenue' => round($dailyRevenue, 2),
                'pendingPayments' => round($pendingPayments, 2)
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
        
        // Recent payments
        $payments = CarePayment::where('patient_id', $patientId)
            ->where('status', 'completed')
            ->orderBy('paid_at', 'desc')
            ->limit(2)
            ->get()
            ->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'type' => 'payment',
                    'title' => 'Payment Completed',
                    'description' => $payment->description . ' - GHS ' . number_format($payment->total_amount, 2),
                    'user' => 'Payment System',
                    'status' => 'completed',
                    'created_at' => $payment->paid_at
                ];
            });
        
        return $activities->merge($vitals)->merge($payments)->sortByDesc('created_at')->take(5)->values();
    }

    private function getPatientUpcomingAppointments($patientId)
    {
        return Schedule::whereHas('carePlan', function($q) use ($patientId) {
                $q->where('patient_id', $patientId);
            })
            ->where('schedule_date', '>', Carbon::today())
            ->where('schedule_date', '<=', Carbon::now()->addWeek())
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with('nurse:id,first_name,last_name')
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get()
            ->map(function($schedule) {
                $scheduleDate = Carbon::parse($schedule->schedule_date);
                return [
                    'id' => $schedule->id,
                    'name' => $schedule->nurse 
                        ? $schedule->nurse->first_name . ' ' . $schedule->nurse->last_name . ', RN'
                        : 'Nurse TBD',
                    'day' => $scheduleDate->format('d'),
                    'month' => $scheduleDate->format('M'),
                    'time' => Carbon::parse($schedule->start_time)->format('g:i A'),
                    'type' => $schedule->shift_type ?? 'Care Visit',
                    'status' => $schedule->status,
                    'condition' => $schedule->carePlan->care_type ?? 'General Care',
                    'priority' => $schedule->carePlan->priority ?? 'medium',
                    'nextVisit' => $scheduleDate->format('l, F j') . ' at ' . Carbon::parse($schedule->start_time)->format('g:i A'),
                    'medications' => 0, // Would need medication table
                    'lastVisit' => 'N/A'
                ];
            });
    }

    private function getNurseUpcomingAppointments($nurseId)
    {
        return Schedule::where('nurse_id', $nurseId)
            ->where('schedule_date', '>', Carbon::today())
            ->where('schedule_date', '<=', Carbon::now()->addWeek())
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with([
                'carePlan.patient:id,first_name,last_name,avatar',
                'carePlan:id,patient_id,care_type,priority'
            ])
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get()
            ->map(function($schedule) {
                $scheduleDate = Carbon::parse($schedule->schedule_date);
                return [
                    'id' => $schedule->id,
                    'name' => $schedule->carePlan && $schedule->carePlan->patient
                        ? $schedule->carePlan->patient->first_name . ' ' . $schedule->carePlan->patient->last_name
                        : 'Patient',
                    'day' => $scheduleDate->format('d'),
                    'month' => $scheduleDate->format('M'),
                    'time' => Carbon::parse($schedule->start_time)->format('g:i A'),
                    'type' => $schedule->shift_type ?? 'Care Visit',
                    'status' => $schedule->status,
                    'condition' => $schedule->carePlan->care_type ?? 'General Care',
                    'priority' => $schedule->carePlan->priority ?? 'medium',
                    'nextVisit' => $scheduleDate->format('l, F j') . ' at ' . Carbon::parse($schedule->start_time)->format('g:i A'),
                    'medications' => 0,
                    'lastVisit' => 'N/A'
                ];
            });
    }

    private function getDoctorUpcomingAppointments($doctorId)
    {
        $carePlanIds = CarePlan::where('doctor_id', $doctorId)
            ->where('status', 'active')
            ->pluck('id');

        if ($carePlanIds->isEmpty()) {
            return collect([]);
        }

        return Schedule::whereIn('care_plan_id', $carePlanIds)
            ->where('schedule_date', '>', Carbon::today())
            ->where('schedule_date', '<=', Carbon::now()->addWeek())
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with([
                'nurse:id,first_name,last_name',
                'carePlan.patient:id,first_name,last_name',
                'carePlan:id,patient_id,care_type,priority'
            ])
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get()
            ->map(function($schedule) {
                $scheduleDate = Carbon::parse($schedule->schedule_date);
                return [
                    'id' => $schedule->id,
                    'name' => $schedule->carePlan && $schedule->carePlan->patient
                        ? $schedule->carePlan->patient->first_name . ' ' . $schedule->carePlan->patient->last_name
                        : 'Patient',
                    'day' => $scheduleDate->format('d'),
                    'month' => $scheduleDate->format('M'),
                    'time' => Carbon::parse($schedule->start_time)->format('g:i A'),
                    'type' => $schedule->shift_type ?? 'Care Visit',
                    'status' => $schedule->status,
                    'condition' => $schedule->carePlan->care_type ?? 'General Care',
                    'priority' => $schedule->carePlan->priority ?? 'medium',
                    'nurse' => $schedule->nurse 
                        ? $schedule->nurse->first_name . ' ' . $schedule->nurse->last_name
                        : 'Not assigned',
                    'nextVisit' => $scheduleDate->format('l, F j') . ' at ' . Carbon::parse($schedule->start_time)->format('g:i A'),
                    'medications' => 0,
                    'lastVisit' => 'N/A'
                ];
            });
    }

    private function getAdminUpcomingAppointments()
    {
        return Schedule::where('schedule_date', '>', Carbon::today())
            ->where('schedule_date', '<=', Carbon::now()->addWeek())
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with([
                'nurse:id,first_name,last_name',
                'carePlan.patient:id,first_name,last_name',
                'carePlan:id,patient_id,care_type,priority'
            ])
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get()
            ->map(function($schedule) {
                $scheduleDate = Carbon::parse($schedule->schedule_date);
                $patientName = $schedule->carePlan && $schedule->carePlan->patient
                    ? $schedule->carePlan->patient->first_name . ' ' . $schedule->carePlan->patient->last_name
                    : 'Unknown Patient';
                $nurseName = $schedule->nurse 
                    ? $schedule->nurse->first_name . ' ' . $schedule->nurse->last_name
                    : 'Not assigned';
                
                return [
                    'id' => $schedule->id,
                    'name' => 'Patient: ' . $patientName . ' | Nurse: ' . $nurseName,
                    'day' => $scheduleDate->format('d'),
                    'month' => $scheduleDate->format('M'),
                    'time' => Carbon::parse($schedule->start_time)->format('g:i A'),
                    'type' => $schedule->shift_type ?? 'Care Visit',
                    'status' => $schedule->status,
                    'condition' => $schedule->carePlan->care_type ?? 'General Care',
                    'priority' => $schedule->carePlan->priority ?? 'medium',
                    'nurse' => $nurseName,
                    'patient' => $patientName,
                    'nextVisit' => $scheduleDate->format('l, F j') . ' at ' . Carbon::parse($schedule->start_time)->format('g:i A'),
                    'medications' => 0,
                    'lastVisit' => 'N/A'
                ];
            });
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
        
        return $notes;
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
        
        // Recent care requests
        $careRequests = CareRequest::orderBy('created_at', 'desc')
            ->limit(2)
            ->with('patient:id,first_name,last_name')
            ->get()
            ->map(function($request) {
                return [
                    'id' => $request->id,
                    'type' => 'assignment',
                    'title' => 'New Care Request',
                    'description' => ($request->patient ? $request->patient->first_name . ' ' . $request->patient->last_name : 'Patient') . ' requested ' . $request->care_type,
                    'user' => 'Patient Portal',
                    'status' => $request->status,
                    'created_at' => $request->created_at
                ];
            });
        
        // Recent payments
        $payments = CarePayment::where('status', 'completed')
            ->orderBy('paid_at', 'desc')
            ->limit(2)
            ->with('patient:id,first_name,last_name')
            ->get()
            ->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'type' => 'payment',
                    'title' => 'Payment Received',
                    'description' => 'GHS ' . number_format($payment->total_amount, 2) . ' from ' . ($payment->patient ? $payment->patient->first_name . ' ' . $payment->patient->last_name : 'patient'),
                    'user' => 'Payment System',
                    'status' => 'completed',
                    'created_at' => $payment->paid_at
                ];
            });
        
        return $activities->merge($verifications)->merge($careRequests)->merge($payments)->sortByDesc('created_at')->take(5)->values();
    }

    private function getPatientAlerts($patientId)
    {
        $alerts = collect();
        
        // Pending care requests
        $pendingRequests = CareRequest::where('patient_id', $patientId)
            ->where('status', 'pending_payment')
            ->get();
        
        foreach ($pendingRequests as $request) {
            $alerts->push([
                'id' => 'request_' . $request->id,
                'type' => 'warning',
                'title' => 'Payment Required',
                'message' => 'Complete payment for your ' . $request->care_type . ' care request',
                'actionRequired' => true,
                'created_at' => $request->created_at
            ]);
        }
        
        // Awaiting care payment
        $awaitingCarePayment = CareRequest::where('patient_id', $patientId)
            ->where('status', 'awaiting_care_payment')
            ->get();
        
        foreach ($awaitingCarePayment as $request) {
            $alerts->push([
                'id' => 'care_payment_' . $request->id,
                'type' => 'info',
                'title' => 'Care Plan Ready',
                'message' => 'Your care plan is ready. Complete payment to begin care',
                'actionRequired' => true,
                'created_at' => $request->updated_at
            ]);
        }
        
        // Pending payment reminders
        $pendingPayments = CarePayment::where('patient_id', $patientId)
            ->whereIn('status', ['pending', 'processing'])
            ->get();
        
        foreach ($pendingPayments as $payment) {
            if ($payment->expires_at && $payment->expires_at->isPast()) {
                $alerts->push([
                    'id' => 'payment_' . $payment->id,
                    'type' => 'critical',
                    'title' => 'Payment Expired',
                    'message' => 'Payment for ' . $payment->description . ' has expired',
                    'actionRequired' => true,
                    'created_at' => $payment->expires_at
                ]);
            }
        }
        
        return $alerts->sortByDesc('created_at')->values();
    }

    private function getNurseAlerts($nurseId)
    {
        $alerts = collect();
        
        // Critical patients
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
        
        // Unconfirmed schedules
        $unconfirmedSchedules = Schedule::where('nurse_id', $nurseId)
            ->where('schedule_date', '>=', Carbon::today())
            ->whereNull('nurse_confirmed_at')
            ->whereIn('status', ['scheduled'])
            ->count();
        
        if ($unconfirmedSchedules > 0) {
            $alerts->push([
                'id' => 2,
                'type' => 'warning',
                'title' => 'Unconfirmed Schedules',
                'message' => "{$unconfirmedSchedules} schedule(s) need your confirmation",
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
        
        // Pending care requests
        $pendingCareRequests = CareRequest::where('status', 'payment_received')
            ->count();
        
        if ($pendingCareRequests > 0) {
            $alerts->push([
                'id' => 2,
                'type' => 'info',
                'title' => 'Pending Care Requests',
                'message' => "{$pendingCareRequests} care request(s) need nurse assignment",
                'actionRequired' => true,
                'created_at' => Carbon::now()
            ]);
        }
        
        // Under review requests
        $underReview = CareRequest::where('status', 'under_review')
            ->count();
        
        if ($underReview > 0) {
            $alerts->push([
                'id' => 3,
                'type' => 'warning',
                'title' => 'Care Requests Under Review',
                'message' => "{$underReview} care request(s) under review need care cost issuance",
                'actionRequired' => true,
                'created_at' => Carbon::now()
            ]);
        }
        
        // Open incidents
        $openIncidents = IncidentReport::where('status', 'open')->count();
        
        if ($openIncidents > 0) {
            $alerts->push([
                'id' => 4,
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
        return [];
    }

    private function getCarePlanTasks($carePlanId)
    {
        return [];
    }

    private function getRecentFinanceRecords()
    {
        return CarePayment::where('status', 'completed')
            ->orderBy('paid_at', 'desc')
            ->limit(10)
            ->with('patient:id,first_name,last_name')
            ->get()
            ->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'patientName' => $payment->patient 
                        ? $payment->patient->first_name . ' ' . $payment->patient->last_name
                        : 'Unknown',
                    'amount' => $payment->total_amount,
                    'type' => $payment->payment_type,
                    'description' => $payment->description,
                    'paidAt' => $payment->paid_at,
                    'reference' => $payment->reference_number
                ];
            });
    }

    private function getRecentCareRequests()
    {
        return CareRequest::orderBy('created_at', 'desc')
            ->limit(5)
            ->with([
                'patient:id,first_name,last_name,phone,email',
                'assignedNurse:id,first_name,last_name',
                'assessmentPayment',
                'carePayment'
            ])
            ->get()
            ->map(function($request) {
                return [
                    'id' => $request->id,
                    'patient' => $request->patient ? [
                        'id' => $request->patient->id,
                        'name' => $request->patient->first_name . ' ' . $request->patient->last_name,
                        'phone' => $request->patient->phone,
                        'email' => $request->patient->email
                    ] : null,
                    'assignedNurse' => $request->assignedNurse ? [
                        'id' => $request->assignedNurse->id,
                        'name' => $request->assignedNurse->first_name . ' ' . $request->assignedNurse->last_name
                    ] : null,
                    'care_type' => $request->care_type,
                    'urgency_level' => $request->urgency_level,
                    'description' => $request->description,
                    'status' => $request->status,
                    'formatted_status' => $request->formatted_status,
                    'service_address' => $request->service_address,
                    'city' => $request->city,
                    'region' => $request->region,
                    'preferred_start_date' => $request->preferred_start_date,
                    'assessment_scheduled_at' => $request->assessment_scheduled_at,
                    'created_at' => $request->created_at,
                    'updated_at' => $request->updated_at,
                    'assessment_payment' => $request->assessmentPayment ? [
                        'id' => $request->assessmentPayment->id,
                        'amount' => $request->assessmentPayment->total_amount,
                        'status' => $request->assessmentPayment->status
                    ] : null,
                    'care_payment' => $request->carePayment ? [
                        'id' => $request->carePayment->id,
                        'amount' => $request->carePayment->total_amount,
                        'status' => $request->carePayment->status
                    ] : null
                ];
            });
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

    private function getDoctorCareTeamSchedules($doctorId)
    {
        $today = Carbon::today();
        $nextWeek = Carbon::now()->addWeek();
        
        $carePlanIds = CarePlan::where('doctor_id', $doctorId)
            ->where('status', 'active')
            ->pluck('id');
        
        if ($carePlanIds->isEmpty()) {
            return [];
        }
        
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
            ->take(7);
        
        return $schedules;
    }
}