<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\CarePlan;
use App\Models\Schedule;
use App\Models\TimeTracking;
use App\Models\CarePayment;
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
            priority as complexity_level,
            COUNT(*) as count,
            AVG(DATEDIFF(COALESCE(end_date, NOW()), start_date)) as avg_duration_days
        ')
        ->groupBy('priority')
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

        // Hours worked by nurse (from completed schedules)
        $hoursWorkedQuery = DB::table('schedules as s')
            ->selectRaw("
                s.nurse_id,
                -- Scheduled hours (from duration_minutes)
                COALESCE(SUM(s.duration_minutes), 0) / 60.0 as total_hours,
                -- Actual hours worked (from actual_start_time and actual_end_time)
                COALESCE(SUM(
                    CASE 
                        WHEN s.actual_start_time IS NOT NULL AND s.actual_end_time IS NOT NULL 
                        THEN TIMESTAMPDIFF(MINUTE, s.actual_start_time, s.actual_end_time) 
                        ELSE s.duration_minutes 
                    END
                ), 0) / 60.0 as actual_hours,
                COUNT(*) as total_sessions,
                COALESCE(AVG(s.duration_minutes), 0) / 60.0 as avg_session_hours
            ")
            ->whereBetween('s.schedule_date', [$dateFromStart, $dateToEnd])
            ->where('s.status', 'completed')
            ->whereNull('s.deleted_at')
            ->groupBy('s.nurse_id');

        if ($nurseId) {
            $hoursWorkedQuery->where('s.nurse_id', $nurseId);
        }

        $hoursWorked = $hoursWorkedQuery->get()
            ->map(function($item) {
                $nurse = User::select('id', 'first_name', 'last_name')->find($item->nurse_id);
                
                $totalHours = round((float)($item->total_hours ?? 0), 1);
                $actualHours = round((float)($item->actual_hours ?? 0), 1);
                
                // Calculate overtime as the difference (only if positive)
                $overtimeHours = max(0, $actualHours - $totalHours);
                
                return (object)[
                    'nurse_id' => $item->nurse_id,
                    'total_hours' => $totalHours,
                    'actual_hours' => $actualHours,
                    'overtime_hours' => round($overtimeHours, 1),
                    'total_sessions' => (int)($item->total_sessions ?? 0),
                    'avg_session_hours' => round((float)($item->avg_session_hours ?? 0), 2),
                    'nurse' => $nurse
                ];
            });

        // Patient visits (from completed schedules with care plans)
        $patientVisitsQuery = DB::table('schedules as s')
            ->join('care_plans as cp', 's.care_plan_id', '=', 'cp.id')
            ->selectRaw('
                s.nurse_id,
                COUNT(DISTINCT cp.patient_id) as unique_patients,
                COUNT(*) as total_visits,
                AVG(CASE 
                    WHEN s.actual_start_time IS NOT NULL AND s.actual_end_time IS NOT NULL 
                    THEN TIMESTAMPDIFF(MINUTE, s.actual_start_time, s.actual_end_time) 
                    ELSE s.duration_minutes 
                END) / 60 as avg_visit_duration
            ')
            ->whereBetween('s.schedule_date', [$dateFromStart, $dateToEnd])
            ->where('s.status', 'completed')
            ->whereNotNull('s.care_plan_id')
            ->whereNull('s.deleted_at')
            ->whereNull('cp.deleted_at')
            ->groupBy('s.nurse_id');

        if ($nurseId) {
            $patientVisitsQuery->where('s.nurse_id', $nurseId);
        }

        $patientVisits = $patientVisitsQuery->get()
            ->map(function($item) {
                $nurse = User::select('id', 'first_name', 'last_name')->find($item->nurse_id);
                return (object)[
                    'nurse_id' => $item->nurse_id,
                    'unique_patients' => $item->unique_patients,
                    'total_visits' => $item->total_visits,
                    'avg_visit_duration' => round($item->avg_visit_duration, 1),
                    'nurse' => $nurse
                ];
            });

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


    private function writeScheduleComplianceCsv($file, $data)
    {
        // On-time rates section
        if (!empty($data['on_time_rates'])) {
            fputcsv($file, ['=== ON-TIME PERFORMANCE ===']);
            fputcsv($file, ['Nurse ID', 'Nurse Name', 'Total Shifts', 'Completed Shifts', 'On-Time Shifts', 'On-Time Rate %']);
            foreach ($data['on_time_rates'] as $row) {
                fputcsv($file, [
                    $row['nurse_id'],
                    ($row['nurse']['first_name'] ?? '') . ' ' . ($row['nurse']['last_name'] ?? ''),
                    $row['total_shifts'],
                    $row['completed_shifts'],
                    $row['on_time_shifts'],
                    $row['on_time_rate']
                ]);
            }
            fputcsv($file, []); // Empty row
        }

        // No-shows and cancellations section
        if (!empty($data['no_shows'])) {
            fputcsv($file, ['=== NO-SHOWS AND CANCELLATIONS ===']);
            fputcsv($file, ['Nurse ID', 'Nurse Name', 'Cancelled Shifts', 'No-Show Shifts', 'Total Scheduled']);
            foreach ($data['no_shows'] as $row) {
                fputcsv($file, [
                    $row['nurse_id'],
                    ($row['nurse']['first_name'] ?? '') . ' ' . ($row['nurse']['last_name'] ?? ''),
                    $row['cancelled_shifts'],
                    $row['no_show_shifts'],
                    $row['total_scheduled']
                ]);
            }
            fputcsv($file, []); // Empty row
        }

        // Completions summary (changed from confirmations)
        if (!empty($data['completions'])) {
            fputcsv($file, ['=== COMPLETION SUMMARY ===']);
            fputcsv($file, ['Total Scheduled', 'Completed', 'Completion Rate %']);
            fputcsv($file, [
                $data['completions']['total_scheduled'],
                $data['completions']['completed'],
                $data['completions']['completion_rate']
            ]);
        }
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

        $nurseId = $request->get('nurse_id');

        // On-time rates with completed shifts
        $onTimeRatesQuery = Schedule::selectRaw('
            nurse_id,
            COUNT(*) as total_shifts,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_shifts,
            COUNT(CASE WHEN status = "completed" AND actual_start_time IS NOT NULL AND 
                TIMESTAMPDIFF(MINUTE, CONCAT(schedule_date, " ", start_time), actual_start_time) <= 15 
                THEN 1 END) as on_time_shifts,
            ROUND(COUNT(CASE WHEN status = "completed" AND actual_start_time IS NOT NULL AND 
                    TIMESTAMPDIFF(MINUTE, CONCAT(schedule_date, " ", start_time), actual_start_time) <= 15 
                    THEN 1 END) * 100.0 / GREATEST(COUNT(CASE WHEN status = "completed" THEN 1 END), 1), 2) as on_time_rate
        ')
        ->whereBetween('schedule_date', [$dateFromStart, $dateToEnd])
        ->with('nurse:id,first_name,last_name')
        ->groupBy('nurse_id');

        if ($nurseId) {
            $onTimeRatesQuery->where('nurse_id', $nurseId);
        }

        $onTimeRates = $onTimeRatesQuery->get();

        // No-shows and cancellations
        $noShowsQuery = Schedule::selectRaw('
            nurse_id,
            COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_shifts,
            COUNT(CASE WHEN actual_start_time IS NULL AND schedule_date < CURDATE() AND status != "cancelled" THEN 1 END) as no_show_shifts,
            COUNT(*) as total_scheduled
        ')
        ->whereBetween('schedule_date', [$dateFromStart, $dateToEnd])
        ->with('nurse:id,first_name,last_name')
        ->groupBy('nurse_id');

        if ($nurseId) {
            $noShowsQuery->where('nurse_id', $nurseId);
        }

        $noShows = $noShowsQuery->get();

        // Schedule completions (changed from confirmations)
        $completionsQuery = Schedule::selectRaw('
            COUNT(*) as total_scheduled,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed,
            ROUND(COUNT(CASE WHEN status = "completed" THEN 1 END) * 100.0 / COUNT(*), 2) as completion_rate
        ')
        ->whereBetween('schedule_date', [$dateFromStart, $dateToEnd]);

        if ($nurseId) {
            $completionsQuery->where('nurse_id', $nurseId);
        }

        $completions = $completionsQuery->first();

        return response()->json([
            'on_time_rates' => $onTimeRates,
            'no_shows' => $noShows,
            'completions' => $completions  
        ]);
    }


    private function writeTimeTrackingAnalyticsCsv($file, $data)
    {
        // Total hours section
        if (!empty($data['total_hours'])) {
            fputcsv($file, ['=== WORK HOURS & OVERTIME ANALYSIS ===']);
            fputcsv($file, ['Nurse ID', 'Nurse Name', 'Total Hours (Scheduled)', 'Actual Hours', 'Overtime Hours', 'Avg Session', 'Total Sessions']);
            foreach ($data['total_hours'] as $row) {
                fputcsv($file, [
                    $row->nurse_id,
                    ($row->nurse->first_name ?? '') . ' ' . ($row->nurse->last_name ?? ''),
                    round($row->total_hours, 1),
                    round($row->actual_hours, 1),
                    round($row->overtime_hours, 1),
                    round($row->avg_session_hours, 2),
                    $row->total_sessions
                ]);
            }
            fputcsv($file, []); // Empty row
        }

        // Break patterns section
        if (!empty($data['break_patterns'])) {
            fputcsv($file, ['=== BREAK PATTERNS ===']);
            fputcsv($file, ['Nurse ID', 'Nurse Name', 'Avg Breaks per Shift', 'Avg Break Duration (min)', 'Total Break Hours']);
            foreach ($data['break_patterns'] as $row) {
                fputcsv($file, [
                    $row['nurse_id'],
                    ($row['nurse']['first_name'] ?? '') . ' ' . ($row['nurse']['last_name'] ?? ''),
                    round($row['avg_breaks_per_shift'] ?? 0, 2),
                    round($row['avg_break_duration'] ?? 0, 2),
                    round($row['total_break_hours'] ?? 0, 2)
                ]);
            }
            fputcsv($file, []); // Empty row
        }

        // Session types section
        if (!empty($data['session_types'])) {
            fputcsv($file, ['=== SESSION TYPES DISTRIBUTION ===']);
            fputcsv($file, ['Session Type', 'Count', 'Total Hours', 'Avg Duration']);
            foreach ($data['session_types'] as $row) {
                fputcsv($file, [
                    $row->session_type,
                    $row->count,
                    round($row->total_hours, 2),
                    round($row->avg_duration, 2)
                ]);
            }
        }
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

        $nurseId = $request->get('nurse_id');

        // Total hours analysis (from completed schedules)
        $totalHoursQuery = DB::table('schedules as s')
            ->selectRaw("
                s.nurse_id,
                -- Scheduled hours
                COALESCE(SUM(s.duration_minutes), 0) / 60.0 as total_hours,
                -- Actual hours worked
                COALESCE(SUM(CASE 
                    WHEN s.actual_start_time IS NOT NULL AND s.actual_end_time IS NOT NULL 
                    THEN TIMESTAMPDIFF(MINUTE, s.actual_start_time, s.actual_end_time) 
                    ELSE s.duration_minutes 
                END), 0) / 60.0 as actual_hours,
                COALESCE(AVG(s.duration_minutes), 0) / 60.0 as avg_session_hours,
                COUNT(*) as total_sessions
            ")
            ->whereBetween('s.schedule_date', [$dateFromStart, $dateToEnd])
            ->where('s.status', 'completed')
            ->whereNull('s.deleted_at')
            ->groupBy('s.nurse_id');

        if ($nurseId) {
            $totalHoursQuery->where('s.nurse_id', $nurseId);
        }

        $totalHours = $totalHoursQuery->get()
            ->map(function($item) {
                $nurse = User::select('id', 'first_name', 'last_name')->find($item->nurse_id);
                
                $totalHours = round((float)($item->total_hours ?? 0), 1);
                $actualHours = round((float)($item->actual_hours ?? 0), 1);
                
                // Calculate overtime as the difference (only if positive)
                $overtimeHours = max(0, $actualHours - $totalHours);
                
                return (object)[
                    'nurse_id' => $item->nurse_id,
                    'total_hours' => $totalHours,
                    'actual_hours' => $actualHours,
                    'overtime_hours' => round($overtimeHours, 1),
                    'avg_session_hours' => round((float)($item->avg_session_hours ?? 0), 2),
                    'total_sessions' => (int)($item->total_sessions ?? 0),
                    'nurse' => $nurse
                ];
            });

        // Break patterns (from time tracking)
        $breakPatternsQuery = TimeTracking::selectRaw('
            nurse_id,
            AVG(break_count) as avg_breaks_per_shift,
            AVG(total_break_minutes) as avg_break_duration,
            SUM(total_break_minutes) / 60 as total_break_hours
        ')
        ->whereBetween('start_time', [$dateFromStart, $dateToEnd])
        ->where('status', 'completed')
        ->with('nurse:id,first_name,last_name')
        ->groupBy('nurse_id');

        if ($nurseId) {
            $breakPatternsQuery->where('nurse_id', $nurseId);
        }

        $breakPatterns = $breakPatternsQuery->get();

        // Session types analysis (from schedules)
        $sessionTypesQuery = DB::table('schedules')
            ->selectRaw('
                shift_type as session_type,
                COUNT(*) as count,
                SUM(duration_minutes) / 60 as total_hours,
                AVG(duration_minutes) / 60 as avg_duration
            ')
            ->whereBetween('schedule_date', [$dateFromStart, $dateToEnd])
            ->where('status', 'completed')
            ->whereNull('deleted_at')
            ->groupBy('shift_type');

        if ($nurseId) {
            $sessionTypesQuery->where('nurse_id', $nurseId);
        }

        $sessionTypes = $sessionTypesQuery->get();

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

        // Get latest condition record for each patient with aggregated stats
        $conditionTrends = DB::table('progress_notes as pn1')
            ->select([
                'pn1.patient_id',
                'pn1.general_condition',
                DB::raw("(SELECT COUNT(*) FROM progress_notes pn2 
                        WHERE pn2.patient_id = pn1.patient_id 
                        AND pn2.visit_date BETWEEN '{$dateFromStart}' AND '{$dateToEnd}'
                        AND pn2.deleted_at IS NULL) as count"),
                DB::raw("(SELECT AVG(pain_level) FROM progress_notes pn3 
                        WHERE pn3.patient_id = pn1.patient_id 
                        AND pn3.visit_date BETWEEN '{$dateFromStart}' AND '{$dateToEnd}'
                        AND pn3.deleted_at IS NULL) as avg_pain_level"),
                'pn1.visit_date as latest_visit'
            ])
            ->whereIn('pn1.id', function($query) use ($dateFromStart, $dateToEnd) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('progress_notes')
                    ->whereBetween('visit_date', [$dateFromStart, $dateToEnd])
                    ->whereNull('deleted_at')
                    ->groupBy('patient_id');
            })
            ->whereBetween('pn1.visit_date', [$dateFromStart, $dateToEnd])
            ->whereNull('pn1.deleted_at');

        // Apply patient filter if specified
        if ($patientId) {
            $conditionTrends->where('pn1.patient_id', $patientId);
        }

        $conditionTrends = $conditionTrends->get()
            ->map(function($item) {
                // Attach patient relationship
                $patient = User::select('id', 'first_name', 'last_name')
                            ->find($item->patient_id);
                
                return (object)[
                    'patient_id' => $item->patient_id,
                    'patient_name' => $patient ? $patient->first_name . ' ' . $patient->last_name : 'Unknown',
                    'general_condition' => $item->general_condition,
                    'count' => $item->count,
                    'avg_pain_level' => round($item->avg_pain_level ?? 0, 1),
                    'latest_visit' => $item->latest_visit,
                    'patient' => $patient
                ];
            });

        return response()->json([
            'vitals_trends' => $vitalsTrends,
            'condition_trends' => $conditionTrends
        ]);
    }
    /**
     * Progress Notes Analytics
     */
    /**
     * Progress Notes Analytics
     */
    public function progressNotesAnalytics(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $patientId = $request->get('patient_id');

        $dateFromStart = Carbon::parse($dateFrom)->startOfDay();
        $dateToEnd = Carbon::parse($dateTo)->endOfDay();

        // Visit frequency by patient (using completed schedules)
        $visitFrequencyQuery = DB::table('schedules as s')
            ->join('care_plans as cp', 's.care_plan_id', '=', 'cp.id')
            ->select([
                'cp.patient_id',
                DB::raw('COUNT(s.id) as total_visits'),
                DB::raw('COUNT(DISTINCT s.nurse_id) as different_nurses'),
                DB::raw('MIN(s.schedule_date) as first_visit'),
                DB::raw('MAX(s.schedule_date) as last_visit'),
                DB::raw("(SELECT AVG(pn.pain_level) 
                        FROM progress_notes pn 
                        WHERE pn.patient_id = cp.patient_id 
                        AND pn.visit_date BETWEEN '{$dateFromStart}' AND '{$dateToEnd}'
                        AND pn.deleted_at IS NULL) as avg_pain_level")
            ])
            ->where('s.status', 'completed')
            ->whereNotNull('s.care_plan_id')
            ->whereBetween('s.schedule_date', [$dateFromStart, $dateToEnd])
            ->whereNull('s.deleted_at')
            ->whereNull('cp.deleted_at')
            ->groupBy('cp.patient_id');

        // Apply patient filter if specified
        if ($patientId) {
            $visitFrequencyQuery->where('cp.patient_id', $patientId);
        }

        $visitFrequency = $visitFrequencyQuery->get()
            ->map(function($item) {
                // Attach patient relationship
                $patient = User::select('id', 'first_name', 'last_name')
                            ->find($item->patient_id);
                
                return (object)[
                    'patient_id' => $item->patient_id,
                    'total_visits' => $item->total_visits,
                    'different_nurses' => $item->different_nurses,
                    'avg_pain_level' => round($item->avg_pain_level ?? 0, 1),
                    'first_visit' => $item->first_visit,
                    'last_visit' => $item->last_visit,
                    'patient' => $patient
                ];
            });

        // Pain level trends (from progress notes)
        $painLevelTrendsQuery = ProgressNote::selectRaw('
            DATE(visit_date) as date,
            AVG(pain_level) as avg_pain_level,
            COUNT(*) as visits_count
        ')
        ->whereBetween('visit_date', [$dateFromStart, $dateToEnd])
        ->groupBy('date')
        ->orderBy('date');

        if ($patientId) {
            $painLevelTrendsQuery->where('patient_id', $patientId);
        }

        $painLevelTrends = $painLevelTrendsQuery->get();

        // Intervention effectiveness (based on general condition improvements)
        $interventionEffectivenessQuery = ProgressNote::selectRaw('
            general_condition,
            COUNT(*) as count,
            COUNT(CASE WHEN interventions IS NOT NULL AND interventions != "" THEN 1 END) as with_interventions
        ')
        ->whereBetween('visit_date', [$dateFromStart, $dateToEnd])
        ->groupBy('general_condition');

        if ($patientId) {
            $interventionEffectivenessQuery->where('patient_id', $patientId);
        }

        $interventionEffectiveness = $interventionEffectivenessQuery->get();

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
 * ================================
 * FINANCIAL REPORTS
 * ================================
 */

/**
 * Payment Statistics Report
 */
public function paymentStatisticsReport(Request $request)
{
    $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
    $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

    $dateFromStart = Carbon::parse($dateFrom)->startOfDay();
    $dateToEnd = Carbon::parse($dateTo)->endOfDay();

    // Basic counts by status
    $totalPayments = CarePayment::whereBetween('created_at', [$dateFromStart, $dateToEnd])->count();
    $completedPayments = CarePayment::where('status', 'completed')
        ->whereBetween('created_at', [$dateFromStart, $dateToEnd])
        ->count();
    $pendingPayments = CarePayment::whereIn('status', ['pending', 'processing'])
        ->whereBetween('created_at', [$dateFromStart, $dateToEnd])
        ->count();
    $failedPayments = CarePayment::where('status', 'failed')
        ->whereBetween('created_at', [$dateFromStart, $dateToEnd])
        ->count();

    // Revenue calculations
    $totalRevenue = CarePayment::where('status', 'completed')
        ->whereBetween('paid_at', [$dateFromStart, $dateToEnd])
        ->sum('total_amount');
    
    $assessmentRevenue = CarePayment::where('status', 'completed')
        ->where('payment_type', 'assessment_fee')
        ->whereBetween('paid_at', [$dateFromStart, $dateToEnd])
        ->sum('total_amount');
    
    $careRevenue = CarePayment::where('status', 'completed')
        ->where('payment_type', 'care_fee')
        ->whereBetween('paid_at', [$dateFromStart, $dateToEnd])
        ->sum('total_amount');

    // This period's revenue
    $thisPeriodRevenue = $totalRevenue;

    // Previous period revenue for comparison
    $periodDays = $dateFromStart->diffInDays($dateToEnd);
    $previousPeriodStart = $dateFromStart->copy()->subDays($periodDays);
    $previousPeriodEnd = $dateFromStart->copy()->subDay();

    $previousPeriodRevenue = CarePayment::where('status', 'completed')
        ->whereBetween('paid_at', [$previousPeriodStart, $previousPeriodEnd])
        ->sum('total_amount');

    // Calculate period-over-period change
    $revenueChange = 0;
    if ($previousPeriodRevenue > 0) {
        $revenueChange = (($thisPeriodRevenue - $previousPeriodRevenue) / $previousPeriodRevenue) * 100;
    } elseif ($thisPeriodRevenue > 0) {
        $revenueChange = 100;
    }

    // Average payment amount
    $averagePaymentAmount = CarePayment::where('status', 'completed')
        ->whereBetween('paid_at', [$dateFromStart, $dateToEnd])
        ->avg('total_amount');

    // Payment method breakdown
    $paymentMethodBreakdown = CarePayment::where('status', 'completed')
        ->whereBetween('paid_at', [$dateFromStart, $dateToEnd])
        ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as total'))
        ->groupBy('payment_method')
        ->get()
        ->mapWithKeys(function ($item) {
            return [$item->payment_method ?: 'not_specified' => [
                'count' => $item->count,
                'total' => $item->total
            ]];
        });

    // Payment trends over the period
    $paymentTrends = CarePayment::whereBetween('created_at', [$dateFromStart, $dateToEnd])
        ->selectRaw('DATE(created_at) as date, count(*) as count, sum(CASE WHEN status = "completed" THEN total_amount ELSE 0 END) as total')
        ->groupBy('date')
        ->orderBy('date')
        ->get();

    // Payment type breakdown
    $paymentTypeBreakdown = CarePayment::where('status', 'completed')
        ->whereBetween('paid_at', [$dateFromStart, $dateToEnd])
        ->select('payment_type', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as total'))
        ->groupBy('payment_type')
        ->get();

    return response()->json([
        'total_payments' => $totalPayments,
        'completed_payments' => $completedPayments,
        'pending_payments' => $pendingPayments,
        'failed_payments' => $failedPayments,
        'total_revenue' => round($totalRevenue, 2),
        'assessment_revenue' => round($assessmentRevenue, 2),
        'care_revenue' => round($careRevenue, 2),
        'this_period_revenue' => round($thisPeriodRevenue, 2),
        'revenue_change_percentage' => round($revenueChange, 2),
        'average_payment_amount' => round($averagePaymentAmount ?? 0, 2),
        'payment_method_breakdown' => $paymentMethodBreakdown,
        'payment_trends' => $paymentTrends,
        'payment_type_breakdown' => $paymentTypeBreakdown,
        'completion_rate' => $totalPayments > 0 
            ? round(($completedPayments / $totalPayments) * 100, 2) 
            : 0
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

    // Most requested services (care types from care requests with payments)
    $mostRequestedServices = DB::table('care_payments as cp')
        ->join('care_requests as cr', 'cp.care_request_id', '=', 'cr.id')
        ->select('cr.care_type', DB::raw('COUNT(*) as request_count'))
        ->whereBetween('cp.created_at', [$dateFromStart, $dateToEnd])
        ->whereNull('cp.deleted_at')
        ->whereNull('cr.deleted_at')
        ->groupBy('cr.care_type')
        ->orderBy('request_count', 'desc')
        ->get();

    $totalRequests = $mostRequestedServices->sum('request_count');

    $mostRequestedServices = $mostRequestedServices->map(function($item) use ($totalRequests) {
        return [
            'care_type' => $item->care_type,
            'request_count' => $item->request_count,
            'percentage' => $totalRequests > 0 ? round(($item->request_count / $totalRequests) * 100, 2) : 0
        ];
    });

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

    // Service duration analysis (from completed schedules)
    $serviceDuration = Schedule::selectRaw('
        shift_type as session_type,
        COUNT(*) as session_count,
        SUM(duration_minutes) / 60 as total_hours,
        AVG(duration_minutes) / 60 as avg_duration_hours
    ')
    ->whereBetween('schedule_date', [$dateFromStart, $dateToEnd])
    ->where('status', 'completed')
    ->groupBy('shift_type')
    ->get();

    // Geographic utilization (top service locations from care requests)
    $geographicUtilization = DB::table('care_requests as cr')
        ->join('care_payments as cp', 'cr.id', '=', 'cp.care_request_id')
        ->select('cr.city as pickup_location', DB::raw('COUNT(*) as request_count'))
        ->whereBetween('cp.created_at', [$dateFromStart, $dateToEnd])
        ->whereNotNull('cr.city')
        ->whereNull('cr.deleted_at')
        ->whereNull('cp.deleted_at')
        ->groupBy('cr.city')
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

    // Revenue trends over time
    $revenueTrends = CarePayment::where('status', 'completed')
        ->whereBetween('paid_at', [$dateFromStart, $dateToEnd])
        ->selectRaw('
            DATE(paid_at) as date,
            payment_type,
            SUM(total_amount) as daily_revenue,
            COUNT(*) as transactions
        ')
        ->groupBy('date', 'payment_type')
        ->orderBy('date')
        ->get();

    // Revenue by payment type
    $revenueByType = CarePayment::where('status', 'completed')
        ->whereBetween('paid_at', [$dateFromStart, $dateToEnd])
        ->select('payment_type', DB::raw('SUM(total_amount) as total_revenue'), DB::raw('COUNT(*) as count'))
        ->groupBy('payment_type')
        ->get();

    // Revenue by care type (joining with care_requests)
    $revenueByCareType = DB::table('care_payments as cp')
        ->join('care_requests as cr', 'cp.care_request_id', '=', 'cr.id')
        ->select('cr.care_type', DB::raw('SUM(cp.total_amount) as total_revenue'), DB::raw('COUNT(*) as count'))
        ->where('cp.status', 'completed')
        ->whereBetween('cp.paid_at', [$dateFromStart, $dateToEnd])
        ->whereNull('cp.deleted_at')
        ->whereNull('cr.deleted_at')
        ->groupBy('cr.care_type')
        ->orderBy('total_revenue', 'desc')
        ->get();

    // Payment metrics
    $paymentMetrics = [
        'total_processed' => CarePayment::where('status', 'completed')
            ->whereBetween('paid_at', [$dateFromStart, $dateToEnd])
            ->sum('total_amount'),
        'avg_transaction_value' => CarePayment::where('status', 'completed')
            ->whereBetween('paid_at', [$dateFromStart, $dateToEnd])
            ->avg('total_amount'),
        'transaction_count' => CarePayment::where('status', 'completed')
            ->whereBetween('paid_at', [$dateFromStart, $dateToEnd])
            ->count(),
        'total_tax_collected' => CarePayment::where('status', 'completed')
            ->whereBetween('paid_at', [$dateFromStart, $dateToEnd])
            ->sum('tax_amount')
    ];

    // Monthly comparison
    $thisMonth = Carbon::now();
    $lastMonth = Carbon::now()->subMonth();

    $thisMonthRevenue = CarePayment::where('status', 'completed')
        ->whereYear('paid_at', $thisMonth->year)
        ->whereMonth('paid_at', $thisMonth->month)
        ->sum('total_amount');

    $lastMonthRevenue = CarePayment::where('status', 'completed')
        ->whereYear('paid_at', $lastMonth->year)
        ->whereMonth('paid_at', $lastMonth->month)
        ->sum('total_amount');

    $monthlyComparison = [
        'this_month' => round($thisMonthRevenue, 2),
        'last_month' => round($lastMonthRevenue, 2),
        'change_amount' => round($thisMonthRevenue - $lastMonthRevenue, 2),
        'change_percentage' => $lastMonthRevenue > 0 
            ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 2) 
            : ($thisMonthRevenue > 0 ? 100 : 0)
    ];

    // Top paying patients
    $topPayingPatients = DB::table('care_payments as cp')
        ->join('users as u', 'cp.patient_id', '=', 'u.id')
        ->select(
            'cp.patient_id',
            'u.first_name',
            'u.last_name',
            'u.email',
            DB::raw('SUM(cp.total_amount) as total_paid'),
            DB::raw('COUNT(*) as payment_count')
        )
        ->where('cp.status', 'completed')
        ->whereBetween('cp.paid_at', [$dateFromStart, $dateToEnd])
        ->whereNull('cp.deleted_at')
        ->groupBy('cp.patient_id', 'u.first_name', 'u.last_name', 'u.email')
        ->orderBy('total_paid', 'desc')
        ->limit(10)
        ->get();

    // Revenue by currency
    $revenueByCurrency = CarePayment::where('status', 'completed')
        ->whereBetween('paid_at', [$dateFromStart, $dateToEnd])
        ->select('currency', DB::raw('SUM(total_amount) as total'), DB::raw('COUNT(*) as count'))
        ->groupBy('currency')
        ->get();

    return response()->json([
        'revenue_trends' => $revenueTrends,
        'revenue_by_type' => $revenueByType,
        'revenue_by_care_type' => $revenueByCareType,
        'payment_metrics' => $paymentMetrics,
        'monthly_comparison' => $monthlyComparison,
        'top_paying_patients' => $topPayingPatients,
        'revenue_by_currency' => $revenueByCurrency
    ]);
}

/**
 * Export financial reports
 */
public function exportFinancialReports(Request $request)
{
    $reportType = $request->get('report_type');
    $format = $request->get('format', 'csv');
    $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
    $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

    $data = [];
    $filename = '';

    switch ($reportType) {
        case 'payment_statistics':
            $response = $this->paymentStatisticsReport($request);
            $data = $response->getData(true);
            $filename = 'payment_statistics_report';
            break;

        case 'service_utilization':
            $response = $this->serviceUtilizationReport($request);
            $data = $response->getData(true);
            $filename = 'service_utilization_report';
            break;

        case 'revenue_analytics':
            $response = $this->revenueAnalytics($request);
            $data = $response->getData(true);
            $filename = 'revenue_analytics_report';
            break;

        default:
            return response()->json(['error' => 'Invalid report type'], 400);
    }

    if ($format === 'csv') {
        return $this->exportFinancialToCsv($data, $filename, $reportType);
    }

    return response()->json(['error' => 'Unsupported format'], 400);
}

/**
 * Export financial data to CSV format
 */
private function exportFinancialToCsv($data, $filename, $reportType)
{
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"{$filename}_" . date('Y-m-d') . ".csv\"",
    ];

    $callback = function() use ($data, $reportType) {
        $file = fopen('php://output', 'w');
        
        switch ($reportType) {
            case 'payment_statistics':
                $this->writePaymentStatisticsCsv($file, $data);
                break;
            case 'service_utilization':
                $this->writeServiceUtilizationCsv($file, $data);
                break;
            case 'revenue_analytics':
                $this->writeRevenueAnalyticsCsv($file, $data);
                break;
        }
        
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

/**
 * Write payment statistics data to CSV
 */
private function writePaymentStatisticsCsv($file, $data)
{
    // Summary section
    fputcsv($file, ['=== PAYMENT SUMMARY ===']);
    fputcsv($file, ['Metric', 'Value']);
    fputcsv($file, ['Total Payments', $data['total_payments']]);
    fputcsv($file, ['Completed Payments', $data['completed_payments']]);
    fputcsv($file, ['Pending Payments', $data['pending_payments']]);
    fputcsv($file, ['Failed Payments', $data['failed_payments']]);
    fputcsv($file, ['Total Revenue', '$' . number_format($data['total_revenue'], 2)]);
    fputcsv($file, ['Assessment Revenue', '$' . number_format($data['assessment_revenue'], 2)]);
    fputcsv($file, ['Care Revenue', '$' . number_format($data['care_revenue'], 2)]);
    fputcsv($file, ['Average Payment', '$' . number_format($data['average_payment_amount'], 2)]);
    fputcsv($file, ['Completion Rate', $data['completion_rate'] . '%']);
    fputcsv($file, ['Revenue Change', $data['revenue_change_percentage'] . '%']);
    fputcsv($file, []);

    // Payment trends section
    if (!empty($data['payment_trends'])) {
        fputcsv($file, ['=== PAYMENT TRENDS ===']);
        fputcsv($file, ['Date', 'Count', 'Total Amount']);
        foreach ($data['payment_trends'] as $trend) {
            fputcsv($file, [
                $trend['date'],
                $trend['count'],
                '$' . number_format($trend['total'], 2)
            ]);
        }
        fputcsv($file, []);
    }

    // Payment method breakdown
    if (!empty($data['payment_method_breakdown'])) {
        fputcsv($file, ['=== PAYMENT METHOD BREAKDOWN ===']);
        fputcsv($file, ['Payment Method', 'Count', 'Total Amount']);
        foreach ($data['payment_method_breakdown'] as $method => $info) {
            fputcsv($file, [
                ucwords(str_replace('_', ' ', $method)),
                $info['count'],
                '$' . number_format($info['total'], 2)
            ]);
        }
    }
}

/**
 * Write service utilization data to CSV
 */
private function writeServiceUtilizationCsv($file, $data)
{
    // Most requested services
    if (!empty($data['most_requested_services'])) {
        fputcsv($file, ['=== MOST REQUESTED SERVICES ===']);
        fputcsv($file, ['Care Type', 'Request Count', 'Percentage']);
        foreach ($data['most_requested_services'] as $service) {
            fputcsv($file, [
                ucwords(str_replace('_', ' ', $service['care_type'])),
                $service['request_count'],
                $service['percentage'] . '%'
            ]);
        }
        fputcsv($file, []);
    }

    // Service duration
    if (!empty($data['service_duration'])) {
        fputcsv($file, ['=== SERVICE DURATION ANALYSIS ===']);
        fputcsv($file, ['Session Type', 'Session Count', 'Total Hours', 'Avg Duration (hours)']);
        foreach ($data['service_duration'] as $duration) {
            fputcsv($file, [
                ucwords(str_replace('_', ' ', $duration['session_type'])),
                $duration['session_count'],
                round($duration['total_hours'], 2),
                round($duration['avg_duration_hours'], 2)
            ]);
        }
        fputcsv($file, []);
    }

    // Geographic utilization
    if (!empty($data['geographic_utilization'])) {
        fputcsv($file, ['=== TOP SERVICE LOCATIONS ===']);
        fputcsv($file, ['Location', 'Request Count']);
        foreach ($data['geographic_utilization'] as $location) {
            fputcsv($file, [
                $location->pickup_location,
                $location->request_count
            ]);
        }
    }
}

/**
 * Write revenue analytics data to CSV
 */
private function writeRevenueAnalyticsCsv($file, $data)
{
    // Payment metrics
    if (!empty($data['payment_metrics'])) {
        fputcsv($file, ['=== PAYMENT METRICS ===']);
        fputcsv($file, ['Metric', 'Value']);
        fputcsv($file, ['Total Processed', '$' . number_format($data['payment_metrics']['total_processed'], 2)]);
        fputcsv($file, ['Avg Transaction Value', '$' . number_format($data['payment_metrics']['avg_transaction_value'], 2)]);
        fputcsv($file, ['Transaction Count', $data['payment_metrics']['transaction_count']]);
        fputcsv($file, ['Total Tax Collected', '$' . number_format($data['payment_metrics']['total_tax_collected'], 2)]);
        fputcsv($file, []);
    }

    // Monthly comparison
    if (!empty($data['monthly_comparison'])) {
        fputcsv($file, ['=== MONTHLY COMPARISON ===']);
        fputcsv($file, ['Period', 'Revenue', 'Change']);
        fputcsv($file, [
            'This Month',
            '$' . number_format($data['monthly_comparison']['this_month'], 2),
            ''
        ]);
        fputcsv($file, [
            'Last Month',
            '$' . number_format($data['monthly_comparison']['last_month'], 2),
            ''
        ]);
        fputcsv($file, [
            'Change',
            '$' . number_format($data['monthly_comparison']['change_amount'], 2),
            $data['monthly_comparison']['change_percentage'] . '%'
        ]);
        fputcsv($file, []);
    }

    // Revenue by type
    if (!empty($data['revenue_by_type'])) {
        fputcsv($file, ['=== REVENUE BY PAYMENT TYPE ===']);
        fputcsv($file, ['Payment Type', 'Total Revenue', 'Count']);
        foreach ($data['revenue_by_type'] as $type) {
            fputcsv($file, [
                ucwords(str_replace('_', ' ', $type['payment_type'])),
                '$' . number_format($type['total_revenue'], 2),
                $type['count']
            ]);
        }
        fputcsv($file, []);
    }

    // Top paying patients
    if (!empty($data['top_paying_patients'])) {
        fputcsv($file, ['=== TOP PAYING PATIENTS ===']);
        fputcsv($file, ['Patient Name', 'Email', 'Total Paid', 'Payment Count']);
        foreach ($data['top_paying_patients'] as $patient) {
            fputcsv($file, [
                $patient->first_name . ' ' . $patient->last_name,
                $patient->email,
                '$' . number_format($patient->total_paid, 2),
                $patient->payment_count
            ]);
        }
    }
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