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
use App\Models\MedicalAssessment;
use App\Models\Driver;
use App\Models\TransportRequest;

class ReportController extends Controller
{
    /**
     * ================================
     * USER MANAGEMENT REPORTS
     * ================================
     */

    /**
     * User Activity Report
     */
    public function userActivityReport(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $role = $request->get('role', 'all');

        $dateFromStart = Carbon::parse($dateFrom)->startOfDay();
        $dateToEnd = Carbon::parse($dateTo)->endOfDay();

        // User registrations over time
        $registrationsQuery = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$dateFromStart, $dateToEnd])
            ->groupBy('date')
            ->orderBy('date');

        if ($role !== 'all') {
            $registrationsQuery->where('role', $role);
        }

        $registrations = $registrationsQuery->get();

        // Login frequency
        $loginFrequency = User::selectRaw('
            CASE 
                WHEN last_login_at >= ? THEN "active_7_days"
                WHEN last_login_at >= ? THEN "active_30_days"
                WHEN last_login_at >= ? THEN "active_90_days"
                ELSE "inactive"
            END as frequency,
            COUNT(*) as count
        ', [
            Carbon::now()->subDays(7),
            Carbon::now()->subDays(30),
            Carbon::now()->subDays(90)
        ])->groupBy('frequency')->get();

        // Account status changes
        $statusChanges = User::selectRaw('verification_status, COUNT(*) as count')
            ->groupBy('verification_status')
            ->get();

        // Recent activity
        $recentActivity = User::select('id', 'first_name', 'last_name', 'email', 'role', 'last_login_at', 'created_at')
            ->orderBy('last_login_at', 'desc')
            ->limit(20)
            ->get();

        // Summary stats
        $totalUsers = User::count();
        $newUsersThisMonth = User::whereMonth('created_at', Carbon::now()->month)->count();
        $activeUsersThisWeek = User::where('last_login_at', '>=', Carbon::now()->subDays(7))->count();

        return response()->json([
            'registrations' => $registrations,
            'login_frequency' => $loginFrequency,
            'status_changes' => $statusChanges,
            'recent_activity' => $recentActivity,
            'summary' => [
                'total_users' => $totalUsers,
                'new_users_this_month' => $newUsersThisMonth,
                'active_users_this_week' => $activeUsersThisWeek,
                'growth_rate' => $totalUsers > 0 ? round(($newUsersThisMonth / $totalUsers) * 100, 2) : 0
            ]
        ]);
    }

    /**
     * Role Distribution Report
     */
    public function roleDistributionReport(Request $request)
    {
        // Role distribution
        $roleDistribution = User::selectRaw('role, COUNT(*) as count, COUNT(*) * 100.0 / (SELECT COUNT(*) FROM users WHERE deleted_at IS NULL) as percentage')
            ->groupBy('role')
            ->get();

        // Role status breakdown
        $roleStatusBreakdown = User::selectRaw('role, verification_status, COUNT(*) as count')
            ->groupBy('role', 'verification_status')
            ->get();

        // Professional info (for nurses and doctors)
        $professionalStats = User::selectRaw('
            role,
            AVG(years_experience) as avg_experience,
            COUNT(CASE WHEN specialization IS NOT NULL THEN 1 END) as with_specialization,
            COUNT(CASE WHEN license_number IS NOT NULL THEN 1 END) as with_license
        ')
        ->whereIn('role', ['nurse', 'doctor'])
        ->groupBy('role')
        ->get();

        // Active vs inactive by role
        $activeByRole = User::selectRaw('role, is_active, COUNT(*) as count')
            ->groupBy('role', 'is_active')
            ->get();

        return response()->json([
            'role_distribution' => $roleDistribution,
            'role_status_breakdown' => $roleStatusBreakdown,
            'professional_stats' => $professionalStats,
            'active_by_role' => $activeByRole
        ]);
    }

    /**
     * Verification Status Report
     */
    public function verificationStatusReport(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        $dateFromStart = Carbon::parse($dateFrom)->startOfDay();
        $dateToEnd = Carbon::parse($dateTo)->endOfDay();
        // Pending verifications
        $pendingVerifications = User::select('id', 'first_name', 'last_name', 'email', 'role', 'created_at', 'ghana_card_number')
            ->where('verification_status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        // Verification rates over time
        $verificationTrends = User::selectRaw('
            DATE(verified_at) as date,
            verification_status,
            COUNT(*) as count
        ')
        ->whereNotNull('verified_at')
        ->whereBetween('verified_at', [$dateFromStart, $dateToEnd])
        ->groupBy('date', 'verification_status')
        ->orderBy('date')
        ->get();

        // Approval rates by role
        $approvalRatesByRole = User::selectRaw('
            role,
            COUNT(CASE WHEN verification_status = "verified" THEN 1 END) as approved,
            COUNT(CASE WHEN verification_status = "rejected" THEN 1 END) as rejected,
            COUNT(*) as total,
            ROUND(COUNT(CASE WHEN verification_status = "verified" THEN 1 END) * 100.0 / COUNT(*), 2) as approval_rate
        ')
        ->whereIn('verification_status', ['verified', 'rejected'])
        ->groupBy('role')
        ->get();

        // Processing time analysis
        $processingTimes = User::selectRaw('
            AVG(TIMESTAMPDIFF(HOUR, created_at, verified_at)) as avg_processing_hours,
            MIN(TIMESTAMPDIFF(HOUR, created_at, verified_at)) as min_processing_hours,
            MAX(TIMESTAMPDIFF(HOUR, created_at, verified_at)) as max_processing_hours
        ')
        ->whereNotNull('verified_at')
        ->whereBetween('verified_at', [$dateFromStart, $dateToEnd])
        ->first();

        // Summary stats
        $totalPending = User::where('verification_status', 'pending')->count();
        $totalVerified = User::where('verification_status', 'verified')->count();
        $totalRejected = User::where('verification_status', 'rejected')->count();
        $totalSuspended = User::where('verification_status', 'suspended')->count();

        return response()->json([
            'pending_verifications' => $pendingVerifications,
            'verification_trends' => $verificationTrends,
            'approval_rates_by_role' => $approvalRatesByRole,
            'processing_times' => $processingTimes,
            'summary' => [
                'total_pending' => $totalPending,
                'total_verified' => $totalVerified,
                'total_rejected' => $totalRejected,
                'total_suspended' => $totalSuspended,
                'overall_approval_rate' => $totalVerified + $totalRejected > 0 ? 
                    round(($totalVerified / ($totalVerified + $totalRejected)) * 100, 2) : 0
            ]
        ]);
    }

    /**
     * ================================
     * CARE MANAGEMENT REPORTS
     * ================================
     */

    /**
     * Care Plan Analytics
     */
    public function carePlanAnalytics(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));


        $dateFromStart = Carbon::parse($dateFrom)->startOfDay();
        $dateToEnd = Carbon::parse($dateTo)->endOfDay();

        // Care plan types distribution
        $careTypeDistribution = CarePlan::selectRaw('care_type, COUNT(*) as count')
            ->groupBy('care_type')
            ->get();

        // Status distribution
        $statusDistribution = CarePlan::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        // Completion rates by care type
        $completionRates = CarePlan::selectRaw('
            care_type,
            COUNT(*) as total,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed,
            ROUND(COUNT(CASE WHEN status = "completed" THEN 1 END) * 100.0 / COUNT(*), 2) as completion_rate
        ')
        ->groupBy('care_type')
        ->get();

        // Priority distribution
        $priorityDistribution = CarePlan::selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->get();

        // Care plans created over time
        $creationTrends = CarePlan::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$dateFromStart, $dateToEnd])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'care_type_distribution' => $careTypeDistribution,
            'status_distribution' => $statusDistribution,
            'completion_rates' => $completionRates,
            'priority_distribution' => $priorityDistribution,
            'creation_trends' => $creationTrends
        ]);
    }

    /**
     * Patient Care Summary
     */
    public function patientCareSummary(Request $request)
    {
        // Active care plans per patient
        $activePlansPerPatient = CarePlan::selectRaw('patient_id, COUNT(*) as active_plans')
            ->where('status', 'active')
            ->groupBy('patient_id')
            ->having('active_plans', '>', 0)
            ->with('patient:id,first_name,last_name,email')
            ->get();

        // Care complexity analysis
        $complexityAnalysis = CarePlan::selectRaw('
            complexity_level,
            COUNT(*) as count,
            AVG(DATEDIFF(COALESCE(end_date, NOW()), start_date)) as avg_duration_days
        ')
        ->groupBy('complexity_level')
        ->get();

        // Patients by care type
        $patientsByCareType = CarePlan::selectRaw('care_type, COUNT(DISTINCT patient_id) as unique_patients')
            ->groupBy('care_type')
            ->get();

        // High-priority patients
        $highPriorityPatients = CarePlan::select('patient_id')
            ->with('patient:id,first_name,last_name,email,phone')
            ->where('priority', 'critical')
            ->where('status', 'active')
            ->distinct()
            ->get();

        return response()->json([
            'active_plans_per_patient' => $activePlansPerPatient,
            'complexity_analysis' => $complexityAnalysis,
            'patients_by_care_type' => $patientsByCareType,
            'high_priority_patients' => $highPriorityPatients
        ]);
    }

    /**
     * Care Plan Performance
     */
    public function carePlanPerformance(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(90)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        $dateFromStart = Carbon::parse($dateFrom)->startOfDay();
        $dateToEnd = Carbon::parse($dateTo)->endOfDay();

        // Average duration by care type
        $averageDuration = CarePlan::selectRaw('
            care_type,
            AVG(DATEDIFF(COALESCE(end_date, NOW()), start_date)) as avg_duration_days,
            COUNT(*) as total_plans
        ')
        ->whereBetween('start_date', [$dateFromStart, $dateToEnd])
        ->groupBy('care_type')
        ->get();

        // Success rates (completed vs total)
        $successRates = CarePlan::selectRaw('
            care_type,
            COUNT(*) as total,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed,
            ROUND(COUNT(CASE WHEN status = "completed" THEN 1 END) * 100.0 / COUNT(*), 2) as success_rate
        ')
        ->whereBetween('created_at', [$dateFromStart, $dateToEnd])
        ->groupBy('care_type')
        ->get();

        // Outcomes tracking
        $outcomesTracking = CarePlan::selectRaw('
            care_type,
            completion_percentage,
            COUNT(*) as count
        ')
        ->whereNotNull('outcomes')
        ->groupBy('care_type', 'completion_percentage')
        ->get();

        // Doctor performance
        $doctorPerformance = CarePlan::selectRaw('
            doctor_id,
            COUNT(*) as total_plans,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_plans,
            AVG(completion_percentage) as avg_completion_rate
        ')
        ->with('doctor:id,first_name,last_name')
        ->groupBy('doctor_id')
        ->having('total_plans', '>=', 5)
        ->get();

        return response()->json([
            'average_duration' => $averageDuration,
            'success_rates' => $successRates,
            'outcomes_tracking' => $outcomesTracking,
            'doctor_performance' => $doctorPerformance
        ]);
    }

    /**
     * ================================
     * NURSE PERFORMANCE REPORTS
     * ================================
     */

    /**
     * Nurse Productivity Report
     */
    public function nurseProductivityReport(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        $dateFromStart = Carbon::parse($dateFrom)->startOfDay();
        $dateToEnd = Carbon::parse($dateTo)->endOfDay();

        $nurseId = $request->get('nurse_id');

        // Hours worked by nurse
        $hoursWorkedQuery = TimeTracking::selectRaw('
            nurse_id,
            SUM(total_duration_minutes) / 60 as total_hours,
            COUNT(*) as total_sessions,
            AVG(total_duration_minutes) / 60 as avg_session_hours
        ')
        ->whereBetween('start_time', [$dateFromStart, $dateToEnd])
        ->where('status', 'completed')
        ->with('nurse:id,first_name,last_name')
        ->groupBy('nurse_id');

        if ($nurseId) {
            $hoursWorkedQuery->where('nurse_id', $nurseId);
        }

        $hoursWorked = $hoursWorkedQuery->get();

        // Patient visits
        $patientVisits = ProgressNote::selectRaw('
            nurse_id,
            COUNT(DISTINCT patient_id) as unique_patients,
            COUNT(*) as total_visits,
            AVG(pain_level) as avg_pain_level_recorded
        ')
        ->whereBetween('visit_date', [$dateFromStart, $dateToEnd])
        ->with('nurse:id,first_name,last_name')
        ->groupBy('nurse_id')
        ->get();

        // Care plan involvement
        $carePlanInvolvement = Schedule::selectRaw('
            nurse_id,
            COUNT(DISTINCT care_plan_id) as care_plans_involved,
            COUNT(*) as total_shifts,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_shifts
        ')
        ->whereNotNull('care_plan_id')
        ->whereBetween('schedule_date', [$dateFromStart, $dateToEnd])
        ->with('nurse:id,first_name,last_name')
        ->groupBy('nurse_id')
        ->get();

        return response()->json([
            'hours_worked' => $hoursWorked,
            'patient_visits' => $patientVisits,
            'care_plan_involvement' => $carePlanInvolvement
        ]);
    }

    /**
     * Schedule Compliance Report
     */
    public function scheduleComplianceReport(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        $dateFromStart = Carbon::parse($dateFrom)->startOfDay();
        $dateToEnd = Carbon::parse($dateTo)->endOfDay();

        // On-time rates
        $onTimeRates = Schedule::selectRaw('
            nurse_id,
            COUNT(*) as total_shifts,
            COUNT(CASE WHEN actual_start_time IS NOT NULL AND 
                  TIMESTAMPDIFF(MINUTE, CONCAT(schedule_date, " ", start_time), actual_start_time) <= 15 
                  THEN 1 END) as on_time_shifts,
            ROUND(COUNT(CASE WHEN actual_start_time IS NOT NULL AND 
                       TIMESTAMPDIFF(MINUTE, CONCAT(schedule_date, " ", start_time), actual_start_time) <= 15 
                       THEN 1 END) * 100.0 / COUNT(*), 2) as on_time_rate
        ')
        ->whereBetween('schedule_date', [$dateFromStart, $dateToEnd])
        ->with('nurse:id,first_name,last_name')
        ->groupBy('nurse_id')
        ->get();

        // No-shows and cancellations
        $noShows = Schedule::selectRaw('
            nurse_id,
            COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_shifts,
            COUNT(CASE WHEN actual_start_time IS NULL AND schedule_date < CURDATE() THEN 1 END) as no_show_shifts,
            COUNT(*) as total_scheduled
        ')
        ->whereBetween('schedule_date', [$dateFromStart, $dateToEnd])
        ->with('nurse:id,first_name,last_name')
        ->groupBy('nurse_id')
        ->get();

        // Schedule confirmations
        $confirmations = Schedule::selectRaw('
            COUNT(*) as total_scheduled,
            COUNT(CASE WHEN nurse_confirmed_at IS NOT NULL THEN 1 END) as confirmed,
            ROUND(COUNT(CASE WHEN nurse_confirmed_at IS NOT NULL THEN 1 END) * 100.0 / COUNT(*), 2) as confirmation_rate
        ')
        ->whereBetween('schedule_date', [$dateFromStart, $dateToEnd])
        ->first();

        return response()->json([
            'on_time_rates' => $onTimeRates,
            'no_shows' => $noShows,
            'confirmations' => $confirmations
        ]);
    }

    /**
     * Time Tracking Analytics
     */
    public function timeTrackingAnalytics(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        $dateFromStart = Carbon::parse($dateFrom)->startOfDay();
        $dateToEnd = Carbon::parse($dateTo)->endOfDay();

        // Total hours analysis
        $totalHours = TimeTracking::selectRaw('
            nurse_id,
            SUM(total_duration_minutes) / 60 as total_hours,
            SUM(CASE WHEN total_duration_minutes > 480 THEN total_duration_minutes - 480 ELSE 0 END) / 60 as overtime_hours,
            AVG(total_duration_minutes) / 60 as avg_session_hours
        ')
        ->whereBetween('start_time', [$dateFromStart, $dateToEnd])
        ->where('status', 'completed')
        ->with('nurse:id,first_name,last_name')
        ->groupBy('nurse_id')
        ->get();

        // Break patterns
        $breakPatterns = TimeTracking::selectRaw('
            nurse_id,
            AVG(break_count) as avg_breaks_per_shift,
            AVG(total_break_minutes) as avg_break_duration,
            SUM(total_break_minutes) / 60 as total_break_hours
        ')
        ->whereBetween('start_time', [$dateFromStart, $dateToEnd])
        ->where('status', 'completed')
        ->with('nurse:id,first_name,last_name')
        ->groupBy('nurse_id')
        ->get();

        // Session types analysis
        $sessionTypes = TimeTracking::selectRaw('
            session_type,
            COUNT(*) as count,
            SUM(total_duration_minutes) / 60 as total_hours,
            AVG(total_duration_minutes) / 60 as avg_duration
        ')
        ->whereBetween('start_time', [$dateFromStart, $dateToEnd])
        ->where('status', 'completed')
        ->groupBy('session_type')
        ->get();

        return response()->json([
            'total_hours' => $totalHours,
            'break_patterns' => $breakPatterns,
            'session_types' => $sessionTypes
        ]);
    }

    /**
     * ================================
     * PATIENT HEALTH REPORTS
     * ================================
     */

    /**
     * Patient Health Trends
     */
    public function patientHealthTrends(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $patientId = $request->get('patient_id');

        $dateFromStart = Carbon::parse($dateFrom)->startOfDay();
        $dateToEnd = Carbon::parse($dateTo)->endOfDay();

        // Vital signs trends (from progress notes)
        $vitalsTrendsQuery = ProgressNote::selectRaw('
            patient_id,
            visit_date,
            vitals,
            general_condition,
            pain_level
        ')
        ->whereBetween('visit_date', [$dateFromStart, $dateToEnd])
        ->with('patient:id,first_name,last_name')
        ->orderBy('visit_date');

        if ($patientId) {
            $vitalsTrendsQuery->where('patient_id', $patientId);
        }

        $vitalsTrends = $vitalsTrendsQuery->get();

        // Condition improvements/deteriorations
        $conditionTrends = ProgressNote::selectRaw('
            patient_id,
            general_condition,
            COUNT(*) as count,
            AVG(pain_level) as avg_pain_level
        ')
        ->whereBetween('visit_date', [$dateFromStart, $dateToEnd])
        ->with('patient:id,first_name,last_name')
        ->groupBy('patient_id', 'general_condition')
        ->get();

        return response()->json([
            'vitals_trends' => $vitalsTrends,
            'condition_trends' => $conditionTrends
        ]);
    }

    /**
     * Progress Notes Analytics
     */
    public function progressNotesAnalytics(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        $dateFromStart = Carbon::parse($dateFrom)->startOfDay();
        $dateToEnd = Carbon::parse($dateTo)->endOfDay();

        // Visit frequency by patient
        $visitFrequency = ProgressNote::selectRaw('
            patient_id,
            COUNT(*) as total_visits,
            COUNT(DISTINCT nurse_id) as different_nurses,
            AVG(pain_level) as avg_pain_level,
            MIN(visit_date) as first_visit,
            MAX(visit_date) as last_visit
        ')
        ->whereBetween('visit_date', [$dateFromStart, $dateToEnd])
        ->with('patient:id,first_name,last_name')
        ->groupBy('patient_id')
        ->get();

        // Pain level trends
        $painLevelTrends = ProgressNote::selectRaw('
            DATE(visit_date) as date,
            AVG(pain_level) as avg_pain_level,
            COUNT(*) as visits_count
        ')
        ->whereBetween('visit_date', [$dateFromStart, $dateToEnd])
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        // Intervention effectiveness (based on general condition improvements)
        $interventionEffectiveness = ProgressNote::selectRaw('
            general_condition,
            COUNT(*) as count,
            COUNT(CASE WHEN interventions IS NOT NULL THEN 1 END) as with_interventions
        ')
        ->whereBetween('visit_date', [$dateFromStart, $dateToEnd])
        ->groupBy('general_condition')
        ->get();

        return response()->json([
            'visit_frequency' => $visitFrequency,
            'pain_level_trends' => $painLevelTrends,
            'intervention_effectiveness' => $interventionEffectiveness
        ]);
    }

    /**
     * Patient Outcomes Report
     */
    public function patientOutcomesReport(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(90)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        $dateFromStart = Carbon::parse($dateFrom)->startOfDay();
        $dateToEnd = Carbon::parse($dateTo)->endOfDay();

        // Recovery rates (based on general condition trends)
        $recoveryRates = DB::table('progress_notes as pn1')
            ->select([
                'pn1.patient_id',
                DB::raw('MIN(pn1.general_condition) as initial_condition'),
                DB::raw('(SELECT general_condition FROM progress_notes pn2 WHERE pn2.patient_id = pn1.patient_id ORDER BY visit_date DESC LIMIT 1) as latest_condition'),
                DB::raw('COUNT(*) as total_visits')
            ])
            ->whereBetween('pn1.visit_date', [$dateFromStart, $dateToEnd])
            ->groupBy('pn1.patient_id')
            ->get();

        // Care satisfaction (would need feedback table, using general condition as proxy)
        $careSatisfaction = ProgressNote::selectRaw('
            CASE 
                WHEN general_condition = "improved" THEN "satisfied"
                WHEN general_condition = "stable" THEN "neutral"
                ELSE "needs_improvement"
            END as satisfaction_proxy,
            COUNT(*) as count
        ')
        ->whereBetween('visit_date', [$dateFromStart, $dateToEnd])
        ->groupBy('satisfaction_proxy')
        ->get();

        // Readmission patterns (patients with multiple care plans)
        $readmissionPatterns = CarePlan::selectRaw('
            patient_id,
            COUNT(*) as total_care_plans,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_plans
        ')
        ->with('patient:id,first_name,last_name')
        ->groupBy('patient_id')
        ->having('total_care_plans', '>', 1)
        ->get();

        return response()->json([
            'recovery_rates' => $recoveryRates,
            'care_satisfaction' => $careSatisfaction,
            'readmission_patterns' => $readmissionPatterns
        ]);
    }

    /**
     * Medical Condition Reports
     */
    public function medicalConditionReports(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(90)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        $dateFromStart = Carbon::parse($dateFrom)->startOfDay();
        $dateToEnd = Carbon::parse($dateTo)->endOfDay();

        // Disease prevalence (from medical assessments)
        $diseasePrevalence = MedicalAssessment::selectRaw('
            presenting_condition,
            COUNT(*) as count,
            COUNT(DISTINCT patient_id) as unique_patients
        ')
        ->whereBetween('created_at', [$dateFromStart, $dateToEnd])
        ->groupBy('presenting_condition')
        ->orderBy('count', 'desc')
        ->get();

        // Treatment effectiveness (based on care plan outcomes)
        $treatmentEffectiveness = CarePlan::selectRaw('
            care_type,
            COUNT(*) as total_plans,
            AVG(completion_percentage) as avg_completion,
            COUNT(CASE WHEN status = "completed" AND completion_percentage >= 80 THEN 1 END) as successful_treatments
        ')
        ->whereBetween('created_at', [$dateFromStart, $dateToEnd])
        ->groupBy('care_type')
        ->get();

        // Condition severity trends
        $severityTrends = MedicalAssessment::selectRaw('
            general_condition,
            hydration_status,
            nutrition_status,
            mobility_status,
            COUNT(*) as count
        ')
        ->whereBetween('created_at', [$dateFromStart, $dateToEnd])
        ->groupBy('general_condition', 'hydration_status', 'nutrition_status', 'mobility_status')
        ->get();

        return response()->json([
            'disease_prevalence' => $diseasePrevalence,
            'treatment_effectiveness' => $treatmentEffectiveness,
            'severity_trends' => $severityTrends
        ]);
    }

    /**
     * ================================
     * TRANSPORTATION REPORTS
     * ================================
     */

    /**
     * Transport Utilization Report
     */
    public function transportUtilizationReport(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        $dateFromStart = Carbon::parse($dateFrom)->startOfDay();
        $dateToEnd = Carbon::parse($dateTo)->endOfDay();

        // Ensure we have TransportRequest model data
        $requestVolumes = TransportRequest::selectRaw('
            DATE(created_at) as date,
            COUNT(*) as total_requests,
            COUNT(CASE WHEN transport_type = "ambulance" THEN 1 END) as ambulance_requests,
            COUNT(CASE WHEN transport_type = "regular" THEN 1 END) as regular_requests
        ')
        ->whereBetween('created_at', [$dateFromStart, $dateToEnd])
        ->groupBy('date')
        ->orderBy('date')
        ->get();


        // If no data, return empty structure instead of null
        if ($requestVolumes->isEmpty()) {
            $requestVolumes = collect([]);
        }

        return response()->json([
            'request_volumes' => $requestVolumes,
            'transport_types' => $this->getTransportTypes($dateFromStart, $dateToEnd),
            'priority_levels' => $this->getPriorityLevels($dateFromStart, $dateToEnd),
            'status_distribution' => $this->getStatusDistribution($dateFromStart, $dateToEnd),
            'peak_hours' => $this->getPeakHours($dateFromStart, $dateToEnd),
        ]);
    }

    private function getTransportTypes($dateFrom, $dateTo)
    {
        return TransportRequest::selectRaw('
            transport_type,
            COUNT(*) as count
        ')
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->groupBy('transport_type')
        ->get();
    }

    private function getPriorityLevels($dateFrom, $dateTo)
    {
        return TransportRequest::selectRaw('
            priority,
            COUNT(*) as count,
            AVG(TIMESTAMPDIFF(MINUTE, created_at, COALESCE(actual_pickup_time, updated_at))) as avg_response_time,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed,
            COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled
        ')
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->groupBy('priority')
        ->get();
    }

    private function getStatusDistribution($dateFrom, $dateTo)
    {
        return TransportRequest::selectRaw('
            status,
            COUNT(*) as count
        ')
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->groupBy('status')
        ->get();
    }

    private function getPeakHours($dateFrom, $dateTo)
    {
        return TransportRequest::selectRaw('
            HOUR(created_at) as hour,
            COUNT(*) as request_count,
            transport_type
        ')
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->groupBy('hour', 'transport_type')
        ->orderBy('request_count', 'desc')
        ->get();
    }

    /**
     * Driver Performance Report
     */
    public function driverPerformanceReport(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

          $dateFromStart = Carbon::parse($dateFrom)->startOfDay();
          $dateToEnd = Carbon::parse($dateTo)->endOfDay();

        // Trip completion rates by driver
        $completionRates = TransportRequest::selectRaw('
            driver_id,
            COUNT(*) as total_trips,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_trips,
            COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_trips,
            ROUND(COUNT(CASE WHEN status = "completed" THEN 1 END) * 100.0 / COUNT(*), 2) as completion_rate
        ')
        ->whereNotNull('driver_id')
        ->whereBetween('created_at', [$dateFromStart, $dateToEnd])
        ->with('driver:id,first_name,last_name')
        ->groupBy('driver_id')
        ->having('total_trips', '>=', 3) // Only drivers with at least 3 trips
        ->orderBy('completion_rate', 'desc')
        ->get();

        // Driver ratings
        $driverRatings = TransportRequest::selectRaw('
            driver_id,
            AVG(rating) as avg_rating,
            COUNT(CASE WHEN rating IS NOT NULL THEN 1 END) as rated_trips,
            COUNT(*) as total_trips
        ')
        ->whereNotNull('driver_id')
        ->whereNotNull('rating')
        ->whereBetween('completed_at', [$dateFromStart, $dateToEnd])
        ->with('driver:id,first_name,last_name')
        ->groupBy('driver_id')
        ->having('total_trips', '>=', 3)
        ->orderBy('avg_rating', 'desc')
        ->get();

        // Response times by driver
        $responseTimes = TransportRequest::selectRaw('
            driver_id,
            AVG(TIMESTAMPDIFF(MINUTE, created_at, actual_pickup_time)) as avg_response_time,
            MIN(TIMESTAMPDIFF(MINUTE, created_at, actual_pickup_time)) as min_response_time,
            MAX(TIMESTAMPDIFF(MINUTE, created_at, actual_pickup_time)) as max_response_time,
            COUNT(*) as completed_trips
        ')
        ->whereNotNull('driver_id')
        ->whereNotNull('actual_pickup_time')
        ->whereBetween('created_at', [$dateFromStart, $dateToEnd])
        ->with('driver:id,first_name,last_name')
        ->groupBy('driver_id')
        ->having('completed_trips', '>=', 3)
        ->orderBy('avg_response_time')
        ->get();

        // Driver availability metrics (using only existing columns)
        $availabilityMetrics = Driver::selectRaw('
            id,
            first_name,
            last_name,
            is_active,
            is_suspended,
            created_at
        ')
        ->where('is_active', true)
        ->get()
        ->map(function($driver) {
            // Calculate metrics from transport requests
            $recentTrips = TransportRequest::where('driver_id', $driver->id)
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->count();
                
            $completedTrips = TransportRequest::where('driver_id', $driver->id)
                ->where('status', 'completed')
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->count();
                
            $avgRating = TransportRequest::where('driver_id', $driver->id)
                ->whereNotNull('rating')
                ->avg('rating');

            return [
                'id' => $driver->id,
                'first_name' => $driver->first_name,
                'last_name' => $driver->last_name,
                'is_active' => $driver->is_active,
                'is_suspended' => $driver->is_suspended,
                'recent_trips' => $recentTrips,
                'completed_trips' => $completedTrips,
                'average_rating' => $avgRating ? round($avgRating, 2) : null,
                'is_available' => $driver->is_active && !$driver->is_suspended, // Calculated field
            ];
        });

        // Performance trends over time
        $performanceTrends = TransportRequest::selectRaw('
            DATE(completed_at) as date,
            COUNT(*) as completed_trips,
            AVG(rating) as avg_rating,
            AVG(TIMESTAMPDIFF(MINUTE, created_at, actual_pickup_time)) as avg_response_time
        ')
        ->where('status', 'completed')
        ->whereNotNull('completed_at')
        ->whereBetween('completed_at', [$dateFromStart, $dateToEnd])
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        return response()->json([
            'completion_rates' => $completionRates,
            'driver_ratings' => $driverRatings,
            'response_times' => $responseTimes,
            'availability_metrics' => $availabilityMetrics,
            'performance_trends' => $performanceTrends
        ]);
    }


    /**
     * Vehicle Management Report
     */
    public function vehicleManagementReport(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        $dateFromStart = Carbon::parse($dateFrom)->startOfDay();
        $dateToEnd = Carbon::parse($dateTo)->endOfDay();
        // Vehicle utilization metrics
        $vehicleUtilization = DB::table('transport_requests as tr')
            ->join('vehicles as v', 'tr.vehicle_id', '=', 'v.id')
            ->selectRaw('
                v.id as vehicle_id,
                v.registration_number,
                v.vehicle_type,
                v.status,
                COUNT(tr.id) as total_trips,
                COUNT(CASE WHEN tr.status = "completed" THEN 1 END) as completed_trips,
                SUM(tr.distance_km) as total_distance,
                AVG(tr.distance_km) as avg_distance_per_trip
            ')
            ->whereBetween('tr.created_at', [$dateFromStart, $dateToEnd])
            ->groupBy('v.id', 'v.registration_number', 'v.vehicle_type', 'v.status')
            ->orderBy('total_trips', 'desc')
            ->get();

        // Vehicle status distribution
        $statusDistribution = Vehicle::selectRaw('
            status,
            COUNT(*) as count
        ')
        ->groupBy('status')
        ->get();

        // Vehicle type distribution
        $typeDistribution = Vehicle::selectRaw('
            vehicle_type,
            COUNT(*) as count,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_count
        ')
        ->groupBy('vehicle_type')
        ->get();

        // Maintenance schedule and alerts
        $maintenanceAlerts = Vehicle::selectRaw('
            id,
            registration_number,
            vehicle_type,
            last_service_date,
            next_service_date,
            insurance_expiry,
            registration_expiry,
            mileage
        ')
        ->where(function($query) {
            $query->whereDate('insurance_expiry', '<=', Carbon::now()->addDays(30))
                ->orWhereDate('registration_expiry', '<=', Carbon::now()->addDays(30))
                ->orWhereDate('next_service_date', '<=', Carbon::now()->addDays(7));
        })
        ->where('is_active', true)
        ->orderBy('insurance_expiry')
        ->get();

        // Vehicle assignment status
        $assignmentStatus = Vehicle::selectRaw('
            COUNT(*) as total_vehicles,
            COUNT(CASE WHEN id IN (
                SELECT vehicle_id FROM driver_vehicle_assignments 
                WHERE ended_at IS NULL AND deleted_at IS NULL
            ) THEN 1 END) as assigned_vehicles,
            COUNT(CASE WHEN id NOT IN (
                SELECT vehicle_id FROM driver_vehicle_assignments 
                WHERE ended_at IS NULL AND deleted_at IS NULL
            ) THEN 1 END) as unassigned_vehicles
        ')
        ->where('is_active', true)
        ->first();

        // Daily vehicle usage trends
        $usageTrends = TransportRequest::selectRaw('
            DATE(created_at) as date,
            COUNT(DISTINCT vehicle_id) as vehicles_used,
            COUNT(*) as total_trips,
            transport_type
        ')
        ->whereNotNull('vehicle_id')
        ->whereBetween('created_at', [$dateFromStart, $dateToEnd])
        ->groupBy('date', 'transport_type')
        ->orderBy('date')
        ->get();

        return response()->json([
            'vehicle_utilization' => $vehicleUtilization,
            'status_distribution' => $statusDistribution,
            'type_distribution' => $typeDistribution,
            'maintenance_alerts' => $maintenanceAlerts,
            'assignment_status' => $assignmentStatus,
            'usage_trends' => $usageTrends
        ]);
    }

    /**
     * Transport Efficiency Report
     */
    public function transportEfficiencyReport(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        $dateFromStart = Carbon::parse($dateFrom)->startOfDay();
        $dateToEnd = Carbon::parse($dateTo)->endOfDay();
        // Response time analysis by priority
        $responseTimeAnalysis = TransportRequest::selectRaw('
            priority,
            transport_type,
            AVG(TIMESTAMPDIFF(MINUTE, created_at, actual_pickup_time)) as avg_response_time,
            COUNT(*) as total_requests,
            COUNT(CASE WHEN TIMESTAMPDIFF(MINUTE, created_at, actual_pickup_time) <= 15 THEN 1 END) as within_15_min,
            COUNT(CASE WHEN TIMESTAMPDIFF(MINUTE, created_at, actual_pickup_time) <= 30 THEN 1 END) as within_30_min
        ')
        ->whereNotNull('actual_pickup_time')
        ->whereBetween('created_at', [$dateFromStart, $dateToEnd])
        ->groupBy('priority', 'transport_type')
        ->get();

        // Cost efficiency metrics
        $costEfficiency = TransportRequest::selectRaw('
            transport_type,
            COUNT(*) as total_trips,
            AVG(actual_cost) as avg_cost_per_trip,
            AVG(distance_km) as avg_distance,
            AVG(actual_cost / NULLIF(distance_km, 0)) as cost_per_km,
            SUM(actual_cost) as total_revenue
        ')
        ->where('status', 'completed')
        ->whereNotNull('actual_cost')
        ->whereBetween('completed_at', [$dateFromStart, $dateToEnd])
        ->groupBy('transport_type')
        ->get();

        // Peak performance metrics
        $peakPerformance = TransportRequest::selectRaw('
            HOUR(created_at) as hour,
            COUNT(*) as request_count,
            AVG(TIMESTAMPDIFF(MINUTE, created_at, actual_pickup_time)) as avg_response_time,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_count
        ')
        ->whereNotNull('actual_pickup_time')
        ->whereBetween('created_at', [$dateFromStart, $dateToEnd])
        ->groupBy('hour')
        ->orderBy('hour')
        ->get();

        // Route efficiency (distance vs time)
        $routeEfficiency = TransportRequest::selectRaw('
            pickup_location,
            destination_location,
            COUNT(*) as trip_count,
            AVG(distance_km) as avg_distance,
            AVG(TIMESTAMPDIFF(MINUTE, actual_pickup_time, completed_at)) as avg_trip_duration,
            AVG(distance_km / NULLIF(TIMESTAMPDIFF(MINUTE, actual_pickup_time, completed_at), 0) * 60) as avg_speed_kmh
        ')
        ->where('status', 'completed')
        ->whereNotNull('actual_pickup_time')
        ->whereNotNull('completed_at')
        ->whereNotNull('distance_km')
        ->whereBetween('completed_at', [$dateFromStart, $dateToEnd])
        ->groupBy('pickup_location', 'destination_location')
        ->having('trip_count', '>=', 3)
        ->orderBy('trip_count', 'desc')
        ->limit(10)
        ->get();

        return response()->json([
            'response_time_analysis' => $responseTimeAnalysis,
            'cost_efficiency' => $costEfficiency,
            'peak_performance' => $peakPerformance,
            'route_efficiency' => $routeEfficiency
        ]);
    }



    /**
     * ================================
     * FINANCIAL REPORTS
     * ================================
     */

    /**
     * Cost Analysis Report
     */
    public function costAnalysisReport(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        $dateFromStart = Carbon::parse($dateFrom)->startOfDay();
        $dateToEnd = Carbon::parse($dateTo)->endOfDay();

        // Care delivery costs (estimated based on time tracking)
        $careDeliveryCosts = TimeTracking::selectRaw('
            nurse_id,
            SUM(total_duration_minutes) / 60 as total_hours,
            SUM(total_duration_minutes) / 60 * 25 as estimated_cost_usd
        ') // Assuming $25/hour average cost
        ->whereBetween('start_time', [$dateFromStart, $dateToEnd])
        ->where('status', 'completed')
        ->with('nurse:id,first_name,last_name')
        ->groupBy('nurse_id')
        ->get();

        // Transportation costs
        $transportCosts = TransportRequest::selectRaw('
            SUM(actual_cost) as total_transport_cost,
            AVG(actual_cost) as avg_cost_per_trip,
            SUM(distance_km) as total_distance,
            COUNT(*) as total_trips
        ')
        ->where('status', 'completed')
        ->whereNotNull('actual_cost')
        ->whereBetween('completed_at', [$dateFromStart, $dateToEnd])
        ->first();

        // Resource utilization by care type
        $resourceUtilization = CarePlan::selectRaw('
            care_type,
            COUNT(*) as total_plans,
            AVG(DATEDIFF(COALESCE(end_date, NOW()), start_date)) as avg_duration_days
        ')
        ->whereBetween('start_date', [$dateFromStart, $dateToEnd])
        ->groupBy('care_type')
        ->get();

        // Cost per patient analysis
        $costPerPatient = DB::table('time_trackings as tt')
            ->select([
                'tt.patient_id',
                DB::raw('SUM(tt.total_duration_minutes) / 60 as total_care_hours'),
                DB::raw('SUM(tt.total_duration_minutes) / 60 * 25 as estimated_cost')
            ])
            ->whereNotNull('tt.patient_id')
            ->whereBetween('tt.start_time', [$dateFromStart, $dateToEnd])
            ->where('tt.status', 'completed')
            ->groupBy('tt.patient_id')
            ->get();

        return response()->json([
            'care_delivery_costs' => $careDeliveryCosts,
            'transport_costs' => $transportCosts,
            'resource_utilization' => $resourceUtilization,
            'cost_per_patient' => $costPerPatient
        ]);
    }

    /**
     * Service Utilization Report
     */
    public function serviceUtilizationReport(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));


        $dateFromStart = Carbon::parse($dateFrom)->startOfDay();
        $dateToEnd = Carbon::parse($dateTo)->endOfDay();

        // Most requested services (care types)
        $mostRequestedServices = CarePlan::selectRaw('
            care_type,
            COUNT(*) as request_count,
            COUNT(*) * 100.0 / (SELECT COUNT(*) FROM care_plans WHERE deleted_at IS NULL) as percentage
        ')
        ->whereBetween('created_at', [$dateFromStart, $dateToEnd])
        ->groupBy('care_type')
        ->orderBy('request_count', 'desc')
        ->get();

        // Peak usage times (based on schedules)
        $peakUsageTimes = Schedule::selectRaw('
            HOUR(start_time) as hour,
            COUNT(*) as schedule_count,
            shift_type
        ')
        ->whereBetween('schedule_date', [$dateFromStart, $dateToEnd])
        ->groupBy('hour', 'shift_type')
        ->orderBy('schedule_count', 'desc')
        ->get();

        // Service duration analysis
        $serviceDuration = TimeTracking::selectRaw('
            session_type,
            COUNT(*) as session_count,
            AVG(total_duration_minutes) / 60 as avg_duration_hours,
            SUM(total_duration_minutes) / 60 as total_hours
        ')
        ->whereBetween('start_time', [$dateFromStart, $dateToEnd])
        ->where('status', 'completed')
        ->groupBy('session_type')
        ->get();

        // Geographic utilization (top pickup locations for transport)
        $geographicUtilization = TransportRequest::selectRaw('
            pickup_location,
            COUNT(*) as request_count
        ')
        ->whereBetween('created_at', [$dateFromStart, $dateToEnd])
        ->groupBy('pickup_location')
        ->orderBy('request_count', 'desc')
        ->limit(10)
        ->get();

        return response()->json([
            'most_requested_services' => $mostRequestedServices,
            'peak_usage_times' => $peakUsageTimes,
            'service_duration' => $serviceDuration,
            'geographic_utilization' => $geographicUtilization
        ]);
    }

    /**
     * Revenue Analytics
     */
    public function revenueAnalytics(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        $dateFromStart = Carbon::parse($dateFrom)->startOfDay();
        $dateToEnd = Carbon::parse($dateTo)->endOfDay();

        // Revenue from transport services
        $transportRevenue = TransportRequest::selectRaw('
            DATE(completed_at) as date,
            SUM(actual_cost) as daily_revenue,
            COUNT(*) as trips_completed,
            transport_type
        ')
        ->where('status', 'completed')
        ->whereNotNull('actual_cost')
        ->whereBetween('completed_at', [$dateFromStart, $dateTo])
        ->groupBy('date', 'transport_type')
        ->orderBy('date')
        ->get();

        // Revenue by service type (estimated based on care hours)
        $serviceRevenue = DB::table('time_trackings as tt')
            ->join('care_plans as cp', 'tt.care_plan_id', '=', 'cp.id')
            ->select([
                'cp.care_type',
                DB::raw('SUM(tt.total_duration_minutes) / 60 as total_hours'),
                DB::raw('SUM(tt.total_duration_minutes) / 60 * 35 as estimated_revenue') // $35/hour service rate
            ])
            ->whereNotNull('tt.care_plan_id')
            ->where('tt.status', 'completed')
            ->whereBetween('tt.start_time', [$dateFromStart, $dateToEnd])
            ->groupBy('cp.care_type')
            ->get();

        // Payment processing metrics (mock data - would need actual payment table)
        $paymentMetrics = [
            'total_processed' => TransportRequest::where('status', 'completed')
                ->whereNotNull('actual_cost')
                ->whereBetween('completed_at', [$dateFromStart, $dateToEnd])
                ->sum('actual_cost'),
            'avg_transaction_value' => TransportRequest::where('status', 'completed')
                ->whereNotNull('actual_cost')
                ->whereBetween('completed_at', [$dateFromStart, $dateToEnd])
                ->avg('actual_cost'),
            'transaction_count' => TransportRequest::where('status', 'completed')
                ->whereNotNull('actual_cost')
                ->whereBetween('completed_at', [$dateFromStart, $dateToEnd])
                ->count()
        ];

        // Outstanding balances (estimated)
        $outstandingBalances = DB::table('care_plans as cp')
            ->join('time_trackings as tt', 'cp.id', '=', 'tt.care_plan_id')
            ->select([
                'cp.patient_id',
                DB::raw('SUM(tt.total_duration_minutes) / 60 * 35 as estimated_amount_due')
            ])
            ->where('cp.status', 'active')
            ->where('tt.status', 'completed')
            ->groupBy('cp.patient_id')
            ->get();

        return response()->json([
            'transport_revenue' => $transportRevenue,
            'service_revenue' => $serviceRevenue,
            'payment_metrics' => $paymentMetrics,
            'outstanding_balances' => $outstandingBalances
        ]);
    }

    /**
     * Export report data as CSV
     */
    public function exportReport(Request $request)
    {
        $reportType = $request->get('report_type');
        $format = $request->get('format', 'csv');

        // Get the report data based on type
        $data = [];
        switch ($reportType) {
            case 'user_activity':
                $data = $this->userActivityReport($request)->getData(true);
                break;
            case 'role_distribution':
                $data = $this->roleDistributionReport($request)->getData(true);
                break;
            // Add more cases as needed
        }

        if ($format === 'csv') {
            return $this->exportToCsv($data, $reportType);
        }

        return response()->json(['error' => 'Unsupported format'], 400);
    }

    private function exportToCsv($data, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}_" . date('Y-m-d') . ".csv\"",
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers based on data structure
            if (!empty($data)) {
                $firstItem = reset($data);
                if (is_array($firstItem) && !empty($firstItem)) {
                    fputcsv($file, array_keys(reset($firstItem)));
                    
                    foreach ($data as $section) {
                        foreach ($section as $row) {
                            fputcsv($file, $row);
                        }
                    }
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }


    /**
     * Export transport reports
     */
    public function exportTransportReports(Request $request)
    {
        $reportType = $request->get('report_type');
        $format = $request->get('format', 'csv');
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        // Get the report data based on type
        $data = [];
        $filename = '';

        switch ($reportType) {
            case 'transport_utilization':
                $response = $this->transportUtilizationReport($request);
                $data = $response->getData(true);
                $filename = 'transport_utilization_report';
                break;

            case 'driver_performance':
                $response = $this->driverPerformanceReport($request);
                $data = $response->getData(true);
                $filename = 'driver_performance_report';
                break;

            case 'vehicle_management':
                $response = $this->vehicleManagementReport($request);
                $data = $response->getData(true);
                $filename = 'vehicle_management_report';
                break;

            case 'transport_efficiency':
                $response = $this->transportEfficiencyReport($request);
                $data = $response->getData(true);
                $filename = 'transport_efficiency_report';
                break;

            default:
                return response()->json(['error' => 'Invalid report type'], 400);
        }

        if ($format === 'csv') {
            return $this->exportTransportToCsv($data, $filename, $reportType);
        }

        return response()->json(['error' => 'Unsupported format'], 400);
    }

    /**
     * Export transport data to CSV format
     */
    private function exportTransportToCsv($data, $filename, $reportType)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}_" . date('Y-m-d') . ".csv\"",
        ];

        $callback = function() use ($data, $reportType) {
            $file = fopen('php://output', 'w');
            
            switch ($reportType) {
                case 'transport_utilization':
                    $this->writeTransportUtilizationCsv($file, $data);
                    break;
                case 'driver_performance':
                    $this->writeDriverPerformanceCsv($file, $data);
                    break;
                case 'vehicle_management':
                    $this->writeVehicleManagementCsv($file, $data);
                    break;
                case 'transport_efficiency':
                    $this->writeTransportEfficiencyCsv($file, $data);
                    break;
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Write transport utilization data to CSV
     */
    private function writeTransportUtilizationCsv($file, $data)
    {
        // Request volumes section
        if (!empty($data['request_volumes'])) {
            fputcsv($file, ['=== REQUEST VOLUMES ===']);
            fputcsv($file, ['Date', 'Total Requests', 'Ambulance Requests', 'Regular Requests']);
            foreach ($data['request_volumes'] as $row) {
                fputcsv($file, [
                    $row['date'],
                    $row['total_requests'],
                    $row['ambulance_requests'],
                    $row['regular_requests']
                ]);
            }
            fputcsv($file, []); // Empty row
        }

        // Priority levels section
        if (!empty($data['priority_levels'])) {
            fputcsv($file, ['=== PRIORITY ANALYSIS ===']);
            fputcsv($file, ['Priority', 'Count', 'Avg Response Time (min)', 'Completed', 'Cancelled']);
            foreach ($data['priority_levels'] as $row) {
                fputcsv($file, [
                    $row['priority'],
                    $row['count'],
                    round($row['avg_response_time'] ?? 0, 2),
                    $row['completed'],
                    $row['cancelled']
                ]);
            }
            fputcsv($file, []); // Empty row
        }

        // Status distribution section
        if (!empty($data['status_distribution'])) {
            fputcsv($file, ['=== STATUS DISTRIBUTION ===']);
            fputcsv($file, ['Status', 'Count']);
            foreach ($data['status_distribution'] as $row) {
                fputcsv($file, [$row['status'], $row['count']]);
            }
        }
    }

    /**
     * Write driver performance data to CSV
     */
    private function writeDriverPerformanceCsv($file, $data)
    {
        // Driver completion rates section
        if (!empty($data['completion_rates'])) {
            fputcsv($file, ['=== DRIVER COMPLETION RATES ===']);
            fputcsv($file, ['Driver ID', 'Driver Name', 'Total Trips', 'Completed', 'Cancelled', 'Completion Rate %']);
            foreach ($data['completion_rates'] as $row) {
                fputcsv($file, [
                    $row['driver_id'],
                    ($row['driver']['first_name'] ?? '') . ' ' . ($row['driver']['last_name'] ?? ''),
                    $row['total_trips'],
                    $row['completed_trips'],
                    $row['cancelled_trips'],
                    $row['completion_rate']
                ]);
            }
            fputcsv($file, []); // Empty row
        }

        // Driver ratings section
        if (!empty($data['driver_ratings'])) {
            fputcsv($file, ['=== DRIVER RATINGS ===']);
            fputcsv($file, ['Driver ID', 'Driver Name', 'Average Rating', 'Rated Trips', 'Total Trips']);
            foreach ($data['driver_ratings'] as $row) {
                fputcsv($file, [
                    $row['driver_id'],
                    ($row['driver']['first_name'] ?? '') . ' ' . ($row['driver']['last_name'] ?? ''),
                    round($row['avg_rating'] ?? 0, 2),
                    $row['rated_trips'],
                    $row['total_trips']
                ]);
            }
            fputcsv($file, []); // Empty row
        }

        // Response times section
        if (!empty($data['response_times'])) {
            fputcsv($file, ['=== RESPONSE TIMES ===']);
            fputcsv($file, ['Driver ID', 'Driver Name', 'Avg Response Time (min)', 'Min Time', 'Max Time', 'Completed Trips']);
            foreach ($data['response_times'] as $row) {
                fputcsv($file, [
                    $row['driver_id'],
                    ($row['driver']['first_name'] ?? '') . ' ' . ($row['driver']['last_name'] ?? ''),
                    round($row['avg_response_time'] ?? 0, 2),
                    round($row['min_response_time'] ?? 0, 2),
                    round($row['max_response_time'] ?? 0, 2),
                    $row['completed_trips']
                ]);
            }
        }
    }

    /**
     * Write vehicle management data to CSV
     */
    private function writeVehicleManagementCsv($file, $data)
    {
        // Vehicle utilization section
        if (!empty($data['vehicle_utilization'])) {
            fputcsv($file, ['=== VEHICLE UTILIZATION ===']);
            fputcsv($file, ['Vehicle ID', 'Registration', 'Type', 'Status', 'Total Trips', 'Completed Trips', 'Total Distance', 'Avg Distance/Trip']);
            foreach ($data['vehicle_utilization'] as $row) {
                fputcsv($file, [
                    $row->vehicle_id,
                    $row->registration_number,
                    $row->vehicle_type,
                    $row->status,
                    $row->total_trips,
                    $row->completed_trips,
                    round($row->total_distance ?? 0, 2),
                    round($row->avg_distance_per_trip ?? 0, 2)
                ]);
            }
            fputcsv($file, []); // Empty row
        }

        // Maintenance alerts section
        if (!empty($data['maintenance_alerts'])) {
            fputcsv($file, ['=== MAINTENANCE ALERTS ===']);
            fputcsv($file, ['Vehicle ID', 'Registration', 'Type', 'Insurance Expiry', 'Registration Expiry', 'Next Service', 'Mileage']);
            foreach ($data['maintenance_alerts'] as $row) {
                fputcsv($file, [
                    $row['id'],
                    $row['registration_number'],
                    $row['vehicle_type'],
                    $row['insurance_expiry'],
                    $row['registration_expiry'],
                    $row['next_service_date'],
                    $row['mileage']
                ]);
            }
        }
    }

    /**
     * Write transport efficiency data to CSV
     */
    private function writeTransportEfficiencyCsv($file, $data)
    {
        // Response time analysis section
        if (!empty($data['response_time_analysis'])) {
            fputcsv($file, ['=== RESPONSE TIME ANALYSIS ===']);
            fputcsv($file, ['Priority', 'Transport Type', 'Avg Response Time', 'Total Requests', 'Within 15min', 'Within 30min']);
            foreach ($data['response_time_analysis'] as $row) {
                fputcsv($file, [
                    $row['priority'],
                    $row['transport_type'],
                    round($row['avg_response_time'] ?? 0, 2),
                    $row['total_requests'],
                    $row['within_15_min'],
                    $row['within_30_min']
                ]);
            }
            fputcsv($file, []); // Empty row
        }

        // Cost efficiency section
        if (!empty($data['cost_efficiency'])) {
            fputcsv($file, ['=== COST EFFICIENCY ===']);
            fputcsv($file, ['Transport Type', 'Total Trips', 'Avg Cost/Trip', 'Avg Distance', 'Cost/KM', 'Total Revenue']);
            foreach ($data['cost_efficiency'] as $row) {
                fputcsv($file, [
                    $row['transport_type'],
                    $row['total_trips'],
                    round($row['avg_cost_per_trip'] ?? 0, 2),
                    round($row['avg_distance'] ?? 0, 2),
                    round($row['cost_per_km'] ?? 0, 2),
                    round($row['total_revenue'] ?? 0, 2)
                ]);
            }
        }
    }
        
}