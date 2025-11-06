<?php
namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\CarePlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    /**
     * Get schedules for the authenticated user
     * GET /api/mobile/schedules
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $query = $this->buildScheduleQuery($user, $request);
            $schedules = $query->get();
            
            $transformedSchedules = $schedules->map(function($schedule) use ($user) {
                return $this->transformSchedule($schedule, $user->role);
            });

            $counts = $this->calculateScheduleCounts($transformedSchedules);

            return response()->json([
                'success' => true,
                'data' => $transformedSchedules,
                'counts' => $counts,
                'message' => $transformedSchedules->isEmpty() ? 'No schedules found.' : null
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching schedules: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch schedules.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get a specific schedule
     * GET /api/mobile/schedules/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $schedule = Schedule::with(['carePlan.patient', 'nurse'])
                ->where('id', $id)
                ->first();

            if (!$schedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Schedule not found.'
                ], 404);
            }

            // Authorization check
            if (!$this->userCanAccessSchedule($user, $schedule)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $this->transformSchedule($schedule, $user->role)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching schedule: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch schedule.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Build schedule query based on user role and filters
     */
    private function buildScheduleQuery($user, Request $request)
    {
        $query = Schedule::with([
            'carePlan.patient:id,first_name,last_name,avatar',
            'carePlan:id,patient_id,title,care_type,priority'
        ])->orderBy('schedule_date', 'desc')
          ->orderBy('start_time', 'desc');

        // Role-based filtering
        if ($user->role === 'nurse') {
            $query->where('nurse_id', $user->id);
        } elseif ($user->role === 'patient') {
            $query->whereHas('carePlan', function($q) use ($user) {
                $q->where('patient_id', $user->id);
            });
        }

        // Apply filters
        $this->applyFilters($query, $request);

        return $query;
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->has('status') && $request->status !== 'all') {
            $statusMap = [
                'upcoming' => ['scheduled', 'confirmed', 'in_progress'],
                'completed' => ['completed'],
                'cancelled' => ['cancelled']
            ];
            
            if (isset($statusMap[$request->status])) {
                $query->whereIn('status', $statusMap[$request->status]);
            }
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            if ($request->start_date === $request->end_date) {
                $query->whereDate('schedule_date', $request->start_date);
            } else {
                $query->whereBetween('schedule_date', [$request->start_date, $request->end_date]);
            }
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('location', 'like', "%{$search}%")
                  ->orWhereHas('carePlan.patient', function($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }
    }

    /**
     * Transform schedule for API response
     */
    private function transformSchedule($schedule, $userRole): array
    {
        $patient = $schedule->carePlan?->patient;
        $carePlan = $schedule->carePlan;

        $data = [
            'id' => $schedule->id,
            'date' => $schedule->schedule_date->toIso8601String(),
            'startTime' => Carbon::parse($schedule->start_time)->format('h:i A'),
            'endTime' => Carbon::parse($schedule->end_time)->format('h:i A'),
            'location' => $schedule->location ?? 'Location not specified',
            'status' => $schedule->status,
            'notes' => $schedule->shift_notes ?? '',
        ];

        // Add patient info for nurses
        if ($userRole === 'nurse' && $patient) {
            $data['patientName'] = $patient->first_name . ' ' . $patient->last_name;
            $data['patientAge'] = $patient->date_of_birth ? 
                Carbon::parse($patient->date_of_birth)->age : null;
        }

        // Add nurse info for patients
        if ($userRole === 'patient' && $schedule->nurse) {
            $data['nurseName'] = $schedule->nurse->first_name . ' ' . $schedule->nurse->last_name;
            $data['nursePhone'] = $schedule->nurse->phone;
        }

        return $data;
    }

    /**
     * Calculate schedule counts
     */
    private function calculateScheduleCounts($schedules): array
    {
        return [
            'upcoming' => $schedules->filter(function($s) {
                return in_array($s['status'], ['scheduled', 'confirmed', 'in_progress']);
            })->count(),
            'completed' => $schedules->filter(fn($s) => $s['status'] === 'completed')->count(),
            'all' => $schedules->count(),
        ];
    }

    /**
     * Check if user can access schedule
     */
    private function userCanAccessSchedule($user, $schedule): bool
    {
        if ($user->role === 'nurse') {
            return $schedule->nurse_id === $user->id;
        }
        
        if ($user->role === 'patient') {
            return $schedule->carePlan?->patient_id === $user->id;
        }

        return false;
    }
}