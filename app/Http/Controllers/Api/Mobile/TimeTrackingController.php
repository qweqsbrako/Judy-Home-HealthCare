<?php
namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\TimeTracking;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TimeTrackingController extends Controller
{
    /**
     * Get active time tracking session
     * GET /api/mobile/time-tracking/active
     */
    public function getActive(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if ($user->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can access this endpoint.'
                ], 403);
            }

            $activeSession = TimeTracking::where('nurse_id', $user->id)
                ->whereNull('end_time')
                ->whereIn('status', ['active', 'paused'])
                ->with(['patient', 'schedule.carePlan'])
                ->first();

            if (!$activeSession) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'No active session found.'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $this->transformActiveSession($activeSession)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching active session: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active session.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Clock in for a schedule
     * POST /api/mobile/time-tracking/clock-in
     */
    public function clockIn(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if ($user->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can clock in.'
                ], 403);
            }

            // Check for existing active session
            $existingSession = TimeTracking::where('nurse_id', $user->id)
                ->whereNull('end_time')
                ->exists();

            if ($existingSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have an active session. Please clock out first.'
                ], 422);
            }

            $validated = $request->validate([
                'schedule_id' => 'required|exists:schedules,id',
                'location' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ]);

            $schedule = Schedule::with('carePlan')->find($validated['schedule_id']);

            if ($schedule->nurse_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to this schedule.'
                ], 403);
            }

            // Geocode location if needed
            $locationName = $this->getLocationName($validated);

            DB::beginTransaction();

            try {
                $timeTracking = TimeTracking::create([
                    'nurse_id' => $user->id,
                    'schedule_id' => $validated['schedule_id'],
                    'patient_id' => $schedule->carePlan?->patient_id,
                    'care_plan_id' => $schedule->care_plan_id,
                    'session_type' => 'scheduled_shift',
                    'start_time' => now(),
                    'clock_in_location' => $locationName,
                    'clock_in_latitude' => $validated['latitude'] ?? null,
                    'clock_in_longitude' => $validated['longitude'] ?? null,
                    'clock_in_ip' => $request->ip(),
                    'status' => 'active',
                ]);

                $schedule->update([
                    'status' => 'in_progress',
                    'actual_start_time' => $timeTracking->start_time
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Clocked in successfully.',
                    'data' => $this->transformActiveSession($timeTracking)
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error clocking in: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to clock in.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Clock out from active session
     * POST /api/mobile/time-tracking/clock-out
     */
    public function clockOut(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if ($user->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can clock out.'
                ], 403);
            }

            $activeSession = TimeTracking::where('nurse_id', $user->id)
                ->whereNull('end_time')
                ->where('status', 'active')
                ->first();

            if (!$activeSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active session found.'
                ], 422);
            }

            $validated = $request->validate([
                'location' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'work_notes' => 'nullable|string|max:1000',
            ]);

            $locationName = $this->getLocationName($validated);

            DB::beginTransaction();

            try {
                $endTime = now();
                $totalMinutes = $activeSession->start_time->diffInMinutes($endTime);

                $activeSession->update([
                    'end_time' => $endTime,
                    'clock_out_location' => $locationName,
                    'clock_out_latitude' => $validated['latitude'] ?? null,
                    'clock_out_longitude' => $validated['longitude'] ?? null,
                    'work_notes' => $validated['work_notes'] ?? null,
                    'total_duration_minutes' => $totalMinutes,
                    'status' => 'completed'
                ]);

                if ($activeSession->schedule) {
                    $activeSession->schedule->update([
                        'status' => 'completed',
                        'actual_end_time' => $endTime
                    ]);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Clocked out successfully.',
                    'data' => [
                        'total_duration' => $this->formatDuration($totalMinutes),
                        'start_time' => $activeSession->start_time->toIso8601String(),
                        'end_time' => $endTime->toIso8601String(),
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error clocking out: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to clock out.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get time logs history
     * GET /api/mobile/time-tracking/logs
     */
    public function getLogs(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if ($user->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can access this endpoint.'
                ], 403);
            }

            $perPage = $request->input('per_page', 10);
            
            $query = TimeTracking::where('nurse_id', $user->id)
                ->with(['patient', 'schedule.carePlan'])
                ->whereNotNull('end_time');

            // Apply filters
            $this->applyLogFilters($query, $request);

            // Apply sorting
            $this->applyLogSorting($query, $request);

            $timeLogs = $query->paginate($perPage);

            $transformedLogs = $timeLogs->getCollection()->map(function($log, $index) use ($timeLogs) {
                return $this->transformTimeLog($log, $index, $timeLogs);
            });

            return response()->json([
                'success' => true,
                'data' => $transformedLogs->values(),
                'summary' => $this->calculateSummary($user->id),
                'pagination' => [
                    'current_page' => $timeLogs->currentPage(),
                    'last_page' => $timeLogs->lastPage(),
                    'per_page' => $timeLogs->perPage(),
                    'total' => $timeLogs->total()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching time logs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch time logs.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Transform active session for response
     */
    private function transformActiveSession($session): array
    {
        $elapsedSeconds = $session->start_time->diffInSeconds(now());
        
        return [
            'id' => $session->id,
            'schedule_id' => $session->schedule_id,
            'patient' => $session->patient ? [
                'id' => $session->patient->id,
                'name' => $session->patient->first_name . ' ' . $session->patient->last_name,
            ] : null,
            'start_time' => $session->start_time->toIso8601String(),
            'elapsed_seconds' => max(0, $elapsedSeconds),
            'status' => $session->status,
            'location' => $session->clock_in_location,
        ];
    }

    /**
     * Transform time log for response
     */
    private function transformTimeLog($log, $index, $pagination): array
    {
        $totalMinutes = $log->total_duration_minutes ?? 0;
        $sessionNumber = $pagination->total() - ($pagination->perPage() * ($pagination->currentPage() - 1)) - $index;
        
        return [
            'id' => 'Session #' . $sessionNumber,
            'session_id' => $log->id,
            'date' => $log->start_time->format('Y-m-d'),
            'patient' => $log->patient ? 
                $log->patient->first_name . ' ' . $log->patient->last_name : 
                'Unknown',
            'clockIn' => $log->start_time->format('h:i A'),
            'clockOut' => $log->end_time ? $log->end_time->format('h:i A') : 'N/A',
            'duration' => $this->formatDuration($totalMinutes),
            'status' => ucfirst($log->status),
        ];
    }

    /**
     * Get location name from coordinates or provided location
     */
    private function getLocationName(array $validated): ?string
    {
        if (isset($validated['latitude']) && isset($validated['longitude'])) {
            try {
                $geocodeService = app(\App\Services\GeocodeService::class);
                $location = $geocodeService->getShortAddressFromCoordinates(
                    $validated['latitude'],
                    $validated['longitude']
                );
                return $location ?? "{$validated['latitude']}, {$validated['longitude']}";
            } catch (\Exception $e) {
                \Log::error('Geocoding failed: ' . $e->getMessage());
                return "{$validated['latitude']}, {$validated['longitude']}";
            }
        }
        
        return $validated['location'] ?? null;
    }

    /**
     * Apply filters to log query
     */
    private function applyLogFilters($query, Request $request): void
    {
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('start_time', [$startDate, $endDate]);
        }

        if ($request->has('period')) {
            $now = Carbon::now();
            switch ($request->period) {
                case 'today':
                    $query->whereDate('start_time', $now->toDateString());
                    break;
                case 'week':
                    $query->whereBetween('start_time', [$now->startOfWeek(), $now->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('start_time', $now->month)
                        ->whereYear('start_time', $now->year);
                    break;
            }
        }
    }

    /**
     * Apply sorting to log query
     */
    private function applyLogSorting($query, Request $request): void
    {
        $sort = $request->input('sort', 'newest');
        
        switch ($sort) {
            case 'oldest':
                $query->orderBy('start_time', 'asc');
                break;
            case 'longest':
                $query->orderBy('total_duration_minutes', 'desc');
                break;
            case 'shortest':
                $query->orderBy('total_duration_minutes', 'asc');
                break;
            default:
                $query->orderBy('start_time', 'desc');
        }
    }

    /**
     * Calculate summary statistics
     */
    private function calculateSummary($nurseId): array
    {
        $logs = TimeTracking::where('nurse_id', $nurseId)
            ->whereNotNull('end_time')
            ->where('status', 'completed')
            ->get();

        $now = Carbon::now();
        
        $todayMinutes = $logs->filter(fn($log) => $log->start_time->isToday())
            ->sum('total_duration_minutes');
        
        return [
            'total_hours_today' => $this->formatDuration($todayMinutes),
            'sessions_today' => $logs->filter(fn($log) => $log->start_time->isToday())->count(),
        ];
    }

    /**
     * Format duration in minutes to HH:MM
     */
    private function formatDuration(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%dh %dm', $hours, $mins);
    }
}