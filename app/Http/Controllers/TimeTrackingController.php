<?php

namespace App\Http\Controllers;

use App\Models\TimeTracking;
use App\Models\User;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class TimeTrackingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $query = TimeTracking::with(['nurse', 'patient', 'schedule.carePlan', 'carePlan', 'approvedBy']);

        // If user is a nurse, only show their own records
        if ($user->role === 'nurse') {
            $query->where('nurse_id', $user->id);
        }

        // Filter by nurse
        if ($request->filled('nurse_id') && $request->nurse_id !== 'all') {
            $query->where('nurse_id', $request->nurse_id);
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by session type
        if ($request->filled('session_type') && $request->session_type !== 'all') {
            $query->where('session_type', $request->session_type);
        }

        // Date range filters
        if ($request->filled('start_date')) {
            $query->whereDate('start_time', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('start_time', '<=', $request->end_date);
        }

        // Today's records
        if ($request->filled('view') && $request->view === 'today') {
            $query->whereDate('start_time', today());
        }

        // This week's records
        if ($request->filled('view') && $request->view === 'this_week') {
            $query->whereBetween('start_time', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ]);
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('work_notes', 'like', "%{$search}%")
                  ->orWhere('clock_in_location', 'like', "%{$search}%")
                  ->orWhereHas('nurse', function($nq) use ($search) {
                      $nq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('patient', function($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $timeTrackings = $query->latest('start_time')->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $timeTrackings,
            'filters' => [
                'statuses' => TimeTracking::getStatuses(),
                'session_types' => TimeTracking::getSessionTypes(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        // Determine the nurse ID based on user role
        $nurseId = $user->role === 'nurse' ? $user->id : $request->nurse_id;
        
        // Check if nurse already has an active session
        $activeSession = TimeTracking::getActiveSessionForNurse($nurseId);
        if ($activeSession) {
            return response()->json([
                'success' => false,
                'message' => 'This nurse already has an active time tracking session.',
                'active_session' => $activeSession
            ], 422);
        }

        // Validation rules
        $rules = [
            'schedule_id' => 'nullable|exists:schedules,id',
            'patient_id' => 'nullable|exists:users,id',
            'care_plan_id' => 'nullable|exists:care_plans,id',
            'session_type' => ['required', Rule::in(array_keys(TimeTracking::getSessionTypes()))],
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'device_info' => 'nullable|string|max:255',
            'activities_performed' => 'nullable|array',
            'auto_clock_in' => 'boolean'
        ];

        // For admins/superadmins, nurse_id is required
        if (in_array($user->role, ['admin', 'superadmin'])) {
            $rules['nurse_id'] = 'required|exists:users,id';
        }

        // Schedule is required for scheduled_shift session type
        if ($request->session_type === 'scheduled_shift') {
            $rules['schedule_id'] = 'required|exists:schedules,id';
        }

        $validated = $request->validate($rules);

        // If schedule_id is provided, validate and get related info
        if (!empty($validated['schedule_id'])) {
            $schedule = Schedule::with('carePlan')->find($validated['schedule_id']);
            
            // Only validate nurse assignment if the current user is a nurse
            // Admins/superadmins can assign any nurse to any schedule
            if ($user->role === 'nurse' && $schedule && $schedule->nurse_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to this schedule.',
                ], 422);
            }

            // Auto-fill related fields from schedule
            if ($schedule) {
                $validated['patient_id'] = $validated['patient_id'] ?? $schedule->carePlan?->patient_id;
                $validated['care_plan_id'] = $validated['care_plan_id'] ?? $schedule->care_plan_id;
            }
        }

        $timeTracking = TimeTracking::create([
            'nurse_id' => $nurseId,
            'schedule_id' => $validated['schedule_id'] ?? null,
            'patient_id' => $validated['patient_id'] ?? null,
            'care_plan_id' => $validated['care_plan_id'] ?? null,
            'session_type' => $validated['session_type'],
            'activities_performed' => $validated['activities_performed'] ?? null,
            'clock_in_ip' => $request->ip(),
            'device_info' => $validated['device_info'] ?? null,
            'status' => 'active'
        ]);

        // Auto clock in if requested
        if ($request->boolean('auto_clock_in', false)) {
            $timeTracking->clockIn([
                'location' => $validated['location'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'device_info' => $validated['device_info'] ?? null,
            ]);
        }

        $timeTracking->load(['nurse', 'patient', 'schedule.carePlan', 'carePlan']);

        return response()->json([
            'success' => true,
            'message' => 'Time tracking session created successfully.',
            'data' => $timeTracking
        ], 201);
    }

    public function show(TimeTracking $timeTracking): JsonResponse
    {
        $user = auth()->user();

        // Nurses can only see their own records
        if ($user->role === 'nurse' && $timeTracking->nurse_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        $timeTracking->load(['nurse', 'patient', 'schedule.carePlan', 'carePlan', 'approvedBy']);

        return response()->json([
            'success' => true,
            'data' => $timeTracking
        ]);
    }

    public function clockIn(Request $request, TimeTracking $timeTracking): JsonResponse
    {
        $user = auth()->user();

        // Allow admins/superadmins to clock in for any nurse
        if ($user->role === 'nurse' && $timeTracking->nurse_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        if ($timeTracking->start_time) {
            return response()->json([
                'success' => false,
                'message' => 'Time tracking session already started.'
            ], 422);
        }

        $validated = $request->validate([
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'device_info' => 'nullable|string|max:255',
        ]);

        // If coordinates provided, get location name from Google Geocoding API
        if (isset($validated['latitude']) && isset($validated['longitude'])) {
            $geocodeService = app(\App\Services\GeocodeService::class);
            $locationName = $geocodeService->getShortAddressFromCoordinates(
                $validated['latitude'],
                $validated['longitude']
            );
            
            if ($locationName) {
                $validated['location'] = $locationName;
            } else {
                // Fallback to coordinates if geocoding fails
                $validated['location'] = "{$validated['latitude']}, {$validated['longitude']}";
            }
        }

        $success = $timeTracking->clockIn($validated);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clock in. Please try again.'
            ], 422);
        }

        // Update related schedule status
        if ($timeTracking->schedule) {
            $timeTracking->schedule->update([
                'status' => 'in_progress',
                'actual_start_time' => $timeTracking->start_time
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Clocked in successfully.',
            'data' => $timeTracking->fresh(['nurse', 'patient', 'schedule.carePlan', 'carePlan'])
        ]);
    }

    public function clockOut(Request $request, TimeTracking $timeTracking): JsonResponse
    {
        $user = auth()->user();

        // Allow admins/superadmins to clock out for any nurse
        if ($user->role === 'nurse' && $timeTracking->nurse_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        if (!$timeTracking->start_time) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot clock out before clocking in.'
            ], 422);
        }

        if ($timeTracking->end_time) {
            return response()->json([
                'success' => false,
                'message' => 'Time tracking session already completed.'
            ], 422);
        }

        $validated = $request->validate([
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'work_notes' => 'nullable|string|max:1000',
            'activities_performed' => 'nullable|array',
        ]);

        // If coordinates provided, get location name from Google Geocoding API
        if (isset($validated['latitude']) && isset($validated['longitude'])) {
            $geocodeService = app(\App\Services\GeocodeService::class);
            $locationName = $geocodeService->getShortAddressFromCoordinates(
                $validated['latitude'],
                $validated['longitude']
            );
            
            if ($locationName) {
                $validated['location'] = $locationName;
            } else {
                // Fallback to coordinates if geocoding fails
                $validated['location'] = "{$validated['latitude']}, {$validated['longitude']}";
            }
        }

        $success = $timeTracking->clockOut($validated);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clock out. Please try again.'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Clocked out successfully.',
            'data' => $timeTracking->fresh(['nurse', 'patient', 'schedule.carePlan', 'carePlan'])
        ]);
    }

    public function pause(Request $request, TimeTracking $timeTracking): JsonResponse
    {
        $user = auth()->user();

        // Allow admins/superadmins to pause for any nurse
        if ($user->role === 'nurse' && $timeTracking->nurse_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:255'
        ]);

        $success = $timeTracking->pause($validated['reason'] ?? null);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to pause timer. Session may not be active.'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Timer paused successfully.',
            'data' => $timeTracking->fresh(['nurse', 'patient', 'schedule.carePlan', 'carePlan'])
        ]);
    }

    public function resume(TimeTracking $timeTracking): JsonResponse
    {
        $user = auth()->user();

        // Allow admins/superadmins to resume for any nurse
        if ($user->role === 'nurse' && $timeTracking->nurse_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        $success = $timeTracking->resume();

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resume timer. Session may not be paused.'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Timer resumed successfully.',
            'data' => $timeTracking->fresh(['nurse', 'patient', 'schedule.carePlan', 'carePlan'])
        ]);
    }

    public function cancel(Request $request, TimeTracking $timeTracking): JsonResponse
    {
        $user = auth()->user();

        if ($timeTracking->nurse_id !== $user->id && !in_array($user->role, ['admin', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $success = $timeTracking->cancel($validated['reason']);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel session.'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Time tracking session cancelled.',
            'data' => $timeTracking->fresh(['nurse', 'patient', 'schedule.carePlan', 'carePlan'])
        ]);
    }

    public function getCurrentSession(): JsonResponse
    {
        $user = auth()->user();
        
        // For nurses, get their own session. For admins, this endpoint doesn't make much sense
        // but we'll still allow it and return null
        $nurseId = $user->role === 'nurse' ? $user->id : null;
        
        if (!$nurseId) {
            return response()->json([
                'success' => true,
                'data' => null
            ]);
        }

        $activeSession = TimeTracking::getActiveSessionForNurse($nurseId);

        return response()->json([
            'success' => true,
            'data' => $activeSession ? $activeSession->load(['nurse', 'patient', 'schedule.carePlan', 'carePlan']) : null
        ]);
    }

    public function getTodaysSummary(): JsonResponse
    {
        $user = auth()->user();
        
        if ($user->role === 'nurse') {
            // For nurses, get their own summary
            $summary = TimeTracking::getWorkingSummaryForNurse($user->id, today()->format('Y-m-d'));
            
            $todaysSessions = TimeTracking::forNurse($user->id)
                ->forDate(today())
                ->with(['patient', 'schedule'])
                ->latest('start_time')
                ->get();
        } else {
            // For admins/superadmins, get system-wide summary
            $todaysSessions = TimeTracking::whereDate('start_time', today())
                ->with(['nurse', 'patient', 'schedule'])
                ->latest('start_time')
                ->get();
            
            $summary = [
                'total_hours' => $todaysSessions->where('status', 'completed')->sum('total_duration_minutes') / 60,
                'total_sessions' => $todaysSessions->count(),
                'active_sessions' => $todaysSessions->where('status', 'active')->count(),
                'completed_sessions' => $todaysSessions->where('status', 'completed')->count(),
                'paused_sessions' => $todaysSessions->where('status', 'paused')->count(),
                'cancelled_sessions' => $todaysSessions->where('status', 'cancelled')->count(),
                'total_nurses_worked' => $todaysSessions->pluck('nurse_id')->unique()->count(),
                'total_patients_served' => $todaysSessions->pluck('patient_id')->filter()->unique()->count()
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'sessions' => $todaysSessions
            ]
        ]);
    }

    public function getWeeklySummary(): JsonResponse
    {
        $user = auth()->user();
        
        if ($user->role === 'nurse') {
            // For nurses, get their own weekly summary
            $startOfWeek = now()->startOfWeek()->format('Y-m-d');
            $endOfWeek = now()->endOfWeek()->format('Y-m-d');
            
            $totalHours = TimeTracking::getTotalHoursForNurse($user->id, $startOfWeek, $endOfWeek);
            
            $dailySummaries = [];
            for ($i = 0; $i < 7; $i++) {
                $date = now()->startOfWeek()->addDays($i);
                $dailySummaries[] = [
                    'date' => $date->format('Y-m-d'),
                    'day_name' => $date->format('l'),
                    'summary' => TimeTracking::getWorkingSummaryForNurse($user->id, $date->format('Y-m-d'))
                ];
            }
        } else {
            // For admins/superadmins, get system-wide weekly summary
            $startOfWeek = now()->startOfWeek();
            $endOfWeek = now()->endOfWeek();
            
            $weekSessions = TimeTracking::whereBetween('start_time', [$startOfWeek, $endOfWeek])
                ->with(['nurse'])
                ->get();
            
            $totalHours = $weekSessions->where('status', 'completed')->sum('total_duration_minutes') / 60;
            
            $dailySummaries = [];
            for ($i = 0; $i < 7; $i++) {
                $date = now()->startOfWeek()->addDays($i);
                $daySessions = $weekSessions->filter(function($session) use ($date) {
                    return $session->start_time && $session->start_time->isSameDay($date);
                });
                
                $dailySummaries[] = [
                    'date' => $date->format('Y-m-d'),
                    'day_name' => $date->format('l'),
                    'summary' => [
                        'total_hours' => $daySessions->where('status', 'completed')->sum('total_duration_minutes') / 60,
                        'total_sessions' => $daySessions->count(),
                        'active_sessions' => $daySessions->where('status', 'active')->count(),
                        'completed_sessions' => $daySessions->where('status', 'completed')->count(),
                        'total_nurses' => $daySessions->pluck('nurse_id')->unique()->count(),
                        'total_patients' => $daySessions->pluck('patient_id')->filter()->unique()->count()
                    ]
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_hours_this_week' => $totalHours,
                'daily_summaries' => $dailySummaries
            ]
        ]);
    }

    public function addBreak(Request $request, TimeTracking $timeTracking): JsonResponse
    {
        $user = auth()->user();

        // Allow admins/superadmins to add breaks for any nurse
        if ($user->role === 'nurse' && $timeTracking->nurse_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        $validated = $request->validate([
            'minutes' => 'required|integer|min:1|max:480' // Max 8 hours break
        ]);

        $success = $timeTracking->addBreak($validated['minutes']);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add break.'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Break time added successfully.',
            'data' => $timeTracking->fresh(['nurse', 'patient', 'schedule.carePlan', 'carePlan'])
        ]);
    }

    public function getStatistics(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        if ($user->role === 'nurse') {
            // Nurses can only see their own stats
            $nurseId = $user->id;
            
            $stats = [
                'total_sessions' => TimeTracking::forNurse($nurseId)->count(),
                'active_sessions' => TimeTracking::forNurse($nurseId)->active()->count(),
                'completed_sessions' => TimeTracking::forNurse($nurseId)->completed()->count(),
                'cancelled_sessions' => TimeTracking::forNurse($nurseId)->where('status', 'cancelled')->count(),
                'paused_sessions' => TimeTracking::forNurse($nurseId)->where('status', 'paused')->count(),
                'total_hours_this_month' => TimeTracking::forNurse($nurseId)
                    ->whereMonth('start_time', now()->month)
                    ->whereYear('start_time', now()->year)
                    ->where('status', 'completed')
                    ->sum('total_duration_minutes') / 60,
                'average_session_duration' => TimeTracking::forNurse($nurseId)
                    ->completed()
                    ->avg('total_duration_minutes'),
                'total_patients_served' => TimeTracking::forNurse($nurseId)
                    ->completed()
                    ->distinct('patient_id')
                    ->count('patient_id')
            ];
        } else {
            // Admins/superadmins see system-wide stats
            $nurseId = $request->get('nurse_id'); // Optional filter by specific nurse
            
            $query = TimeTracking::query();
            if ($nurseId) {
                $query->forNurse($nurseId);
            }
            
            $stats = [
                'total_sessions' => $query->count(),
                'active_sessions' => (clone $query)->active()->count(),
                'completed_sessions' => (clone $query)->completed()->count(),
                'cancelled_sessions' => (clone $query)->where('status', 'cancelled')->count(),
                'paused_sessions' => (clone $query)->where('status', 'paused')->count(),
                'total_hours_this_month' => (clone $query)
                    ->whereMonth('start_time', now()->month)
                    ->whereYear('start_time', now()->year)
                    ->where('status', 'completed')
                    ->sum('total_duration_minutes') / 60,
                'average_session_duration' => (clone $query)
                    ->completed()
                    ->avg('total_duration_minutes'),
                'total_patients_served' => (clone $query)
                    ->completed()
                    ->distinct('patient_id')
                    ->count('patient_id'),
                'total_nurses_active' => (clone $query)
                    ->distinct('nurse_id')
                    ->count('nurse_id'),
                'total_hours_all_time' => (clone $query)
                    ->where('status', 'completed')
                    ->sum('total_duration_minutes') / 60
            ];
            
            // Add additional system-wide metrics
            if (!$nurseId) {
                $stats['nurses_worked_today'] = TimeTracking::whereDate('start_time', today())
                    ->distinct('nurse_id')
                    ->count('nurse_id');
                    
                $stats['patients_served_today'] = TimeTracking::whereDate('start_time', today())
                    ->where('status', 'completed')
                    ->distinct('patient_id')
                    ->count('patient_id');
            }
        }

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        $query = TimeTracking::with(['nurse', 'patient', 'schedule.carePlan', 'carePlan']);

        // If user is a nurse, only export their own records
        if ($user->role === 'nurse') {
            $query->where('nurse_id', $user->id);
        }

        // Apply same filters as index method
        if ($request->filled('nurse_id') && $request->nurse_id !== 'all') {
            $query->where('nurse_id', $request->nurse_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('start_time', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('start_time', '<=', $request->end_date);
        }

        $timeTrackings = $query->latest('start_time')->get();

        $filename = 'time_tracking_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=' . $filename,
        ];

        $callback = function() use ($timeTrackings) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF"); // BOM for UTF-8

            // CSV Headers
            $headers = [
                'ID',
                'Nurse Name',
                'Patient Name',
                'Session Type',
                'Start Time',
                'End Time',
                'Total Duration',
                'Status',
                'Clock In Location',
                'Clock Out Location',
                'Work Notes',
                'Break Count',
                'Total Break Minutes',
                'Created At'
            ];
            
            fputcsv($file, $headers);
            
            foreach ($timeTrackings as $tracking) {
                $row = [
                    $tracking->id,
                    $tracking->nurse ? $tracking->nurse->first_name . ' ' . $tracking->nurse->last_name : 'N/A',
                    $tracking->patient ? $tracking->patient->first_name . ' ' . $tracking->patient->last_name : 'N/A',
                    $tracking->session_type_display,
                    $tracking->start_time ? $tracking->start_time->format('Y-m-d H:i:s') : '',
                    $tracking->end_time ? $tracking->end_time->format('Y-m-d H:i:s') : '',
                    $tracking->formatted_duration,
                    ucfirst($tracking->status),
                    $tracking->clock_in_location ?: '',
                    $tracking->clock_out_location ?: '',
                    $tracking->work_notes ?: '',
                    $tracking->break_count,
                    $tracking->total_break_minutes,
                    $tracking->created_at->format('Y-m-d H:i:s')
                ];
                
                fputcsv($file, $row);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}