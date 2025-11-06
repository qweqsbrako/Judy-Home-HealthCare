<?php
namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CarePlan;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use App\Models\TimeTracking;
use Illuminate\Support\Facades\DB;
use App\Models\ProgressNote;
use App\Models\Driver;
use App\Models\TransportRequest;

class PatientController extends Controller
{
    /**
     * Get patient dashboard data
     * GET /api/mobile/patient/dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $patient = auth()->user();
            
            if ($patient->role !== 'patient') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only patients can access this endpoint.'
                ], 403);
            }

            // Get active care plans
            $activeCarePlans = CarePlan::where('patient_id', $patient->id)
                ->where('status', 'active')
                ->with(['primaryNurse', 'doctor'])
                ->get();

            // Get upcoming appointments
            $upcomingAppointments = Schedule::whereHas('carePlan', function($query) use ($patient) {
                    $query->where('patient_id', $patient->id);
                })
                ->where('schedule_date', '>=', now())
                ->where('status', 'scheduled')
                ->with(['nurse', 'carePlan'])
                ->orderBy('schedule_date', 'asc')
                ->limit(5)
                ->get();

            // Get recent progress notes
            $recentNotes = \App\Models\ProgressNote::where('patient_id', $patient->id)
                ->orderBy('visit_date', 'desc')
                ->limit(3)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'patient' => [
                        'id' => $patient->id,
                        'name' => $patient->first_name . ' ' . $patient->last_name,
                        'email' => $patient->email,
                        'phone' => $patient->phone,
                        'avatar' => $patient->avatar ? url('/storage/' . $patient->avatar) : null,
                    ],
                    'active_care_plans_count' => $activeCarePlans->count(),
                    'upcoming_appointments_count' => $upcomingAppointments->count(),
                    'next_appointment' => $upcomingAppointments->first() ? [
                        'date' => $upcomingAppointments->first()->schedule_date,
                        'time' => $upcomingAppointments->first()->start_time,
                        'nurse' => $upcomingAppointments->first()->nurse ? 
                            $upcomingAppointments->first()->nurse->first_name . ' ' . 
                            $upcomingAppointments->first()->nurse->last_name : 
                            'TBD',
                    ] : null,
                    'care_plans' => $activeCarePlans->map(function($cp) {
                        return [
                            'id' => $cp->id,
                            'title' => $cp->title,
                            'progress' => $cp->completion_percentage,
                            'nurse' => $cp->primaryNurse ? 
                                $cp->primaryNurse->first_name . ' ' . $cp->primaryNurse->last_name : 
                                'Not assigned',
                        ];
                    }),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching patient dashboard: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get patient's care plans
     * GET /api/mobile/patient/care-plans
     */
    public function carePlans(Request $request): JsonResponse
    {
        try {
            $patient = auth()->user();
            
            if ($patient->role !== 'patient') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only patients can access this endpoint.'
                ], 403);
            }

            $carePlans = CarePlan::where('patient_id', $patient->id)
                ->with(['primaryNurse', 'secondaryNurse', 'doctor'])
                ->orderBy('created_at', 'desc')
                ->get();

            $transformedPlans = $carePlans->map(function($cp) {
                return [
                    'id' => $cp->id,
                    'title' => $cp->title,
                    'description' => $cp->description,
                    'status' => ucfirst($cp->status),
                    'priority' => ucfirst($cp->priority),
                    'start_date' => $cp->start_date,
                    'end_date' => $cp->end_date,
                    'progress' => $cp->completion_percentage,
                    'nurse' => $cp->primaryNurse ? [
                        'name' => $cp->primaryNurse->first_name . ' ' . $cp->primaryNurse->last_name,
                        'phone' => $cp->primaryNurse->phone,
                        'avatar' => $cp->primaryNurse->avatar,
                    ] : null,
                    'doctor' => $cp->doctor ? [
                        'name' => $cp->doctor->first_name . ' ' . $cp->doctor->last_name,
                        'specialization' => $cp->doctor->specialization,
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedPlans
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching patient care plans: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch care plans.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get patient's care plan detail
     * GET /api/mobile/patient/care-plans/{id}
     */
    public function carePlanDetail($id): JsonResponse
    {
        try {
            $patient = auth()->user();
            
            if ($patient->role !== 'patient') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only patients can access this endpoint.'
                ], 403);
            }

            $carePlan = CarePlan::where('id', $id)
                ->where('patient_id', $patient->id)
                ->with(['primaryNurse', 'secondaryNurse', 'doctor'])
                ->first();

            if (!$carePlan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care plan not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $carePlan->id,
                    'title' => $carePlan->title,
                    'description' => $carePlan->description,
                    'status' => ucfirst($carePlan->status),
                    'priority' => ucfirst($carePlan->priority),
                    'care_tasks' => $carePlan->care_tasks ?? [],
                    'completed_tasks' => $carePlan->completed_tasks ?? [],
                    'medications' => $carePlan->medications ?? [],
                    'dietary_requirements' => $carePlan->dietary_requirements,
                    'special_instructions' => $carePlan->special_instructions,
                    // Add more fields as needed
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching care plan detail: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch care plan details.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get patient's schedules
     * GET /api/mobile/patient/schedules
     */
    public function schedules(Request $request): JsonResponse
    {
        try {
            $patient = auth()->user();
            
            if ($patient->role !== 'patient') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only patients can access this endpoint.'
                ], 403);
            }

            $schedules = Schedule::whereHas('carePlan', function($query) use ($patient) {
                    $query->where('patient_id', $patient->id);
                })
                ->with(['nurse', 'carePlan'])
                ->orderBy('schedule_date', 'desc')
                ->get();

            $transformedSchedules = $schedules->map(function($schedule) {
                return [
                    'id' => $schedule->id,
                    'date' => $schedule->schedule_date,
                    'start_time' => Carbon::parse($schedule->start_time)->format('h:i A'),
                    'end_time' => Carbon::parse($schedule->end_time)->format('h:i A'),
                    'status' => ucfirst($schedule->status),
                    'nurse' => $schedule->nurse ? [
                        'name' => $schedule->nurse->first_name . ' ' . $schedule->nurse->last_name,
                        'phone' => $schedule->nurse->phone,
                        'avatar' => $schedule->nurse->avatar,
                    ] : null,
                    'care_plan_title' => $schedule->carePlan?->title,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedSchedules
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching patient schedules: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch schedules.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get patient's progress notes
     * GET /api/mobile/patient/progress-notes
     */
    public function progressNotes(Request $request): JsonResponse
    {
        try {
            $patient = auth()->user();
            
            if ($patient->role !== 'patient') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only patients can access this endpoint.'
                ], 403);
            }

            $notes = \App\Models\ProgressNote::where('patient_id', $patient->id)
                ->with('nurse:id,first_name,last_name')
                ->orderBy('visit_date', 'desc')
                ->orderBy('visit_time', 'desc')
                ->get();

            $transformedNotes = $notes->map(function($note) {
                return [
                    'id' => $note->id,
                    'visit_date' => $note->visit_date,
                    'visit_time' => Carbon::parse($note->visit_time)->format('h:i A'),
                    'general_condition' => ucfirst($note->general_condition),
                    'pain_level' => $note->pain_level,
                    'vitals' => $note->vitals,
                    'nurse' => $note->nurse ? [
                        'name' => $note->nurse->first_name . ' ' . $note->nurse->last_name,
                    ] : null,
                    'other_observations' => $note->other_observations,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedNotes
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching patient progress notes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch progress notes.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get patient mobile dashboard data
     * GET /api/mobile/patient/dashboard
     */
    public function patientMobileDashboard(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'patient') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        $now = Carbon::now();

        // 1. Week total hours (time nurses spent caring for this patient)
        $weekHours = TimeTracking::whereHas('schedule.carePlan', function($query) use ($user) {
                $query->where('patient_id', $user->id);
            })
            ->whereBetween('start_time', [$weekStart, $weekEnd])
            ->where('status', 'completed')
            ->sum(DB::raw('total_duration_minutes')) / 60;

        // 2. Nurses scheduled for today
        $nursesToday = DB::table('schedules')
            ->join('care_plans', 'schedules.care_plan_id', '=', 'care_plans.id')
            ->where('care_plans.patient_id', $user->id)
            ->whereDate('schedules.schedule_date', $today)
            ->whereIn('schedules.status', ['scheduled', 'pending', 'in_progress'])
            ->whereNull('schedules.deleted_at')
            ->whereNull('care_plans.deleted_at')
            ->distinct('schedules.nurse_id')
            ->count('schedules.nurse_id');

        // 3. Total active care plans for this patient
        $activePlans = CarePlan::where('patient_id', $user->id)
            ->where('status', 'active')
            ->count();

        // 4. Weekly schedules with nurse details
        $weekSchedules = Schedule::whereHas('carePlan', function($query) use ($user) {
                $query->where('patient_id', $user->id);
            })
            ->whereBetween('schedule_date', [$weekStart, $weekEnd])
            ->with([
                'carePlan:id,title,care_type,priority',
                'nurse:id,first_name,last_name,specialization,phone,avatar',
                'timeTracking' => function($query) {
                    $query->where('status', 'completed')
                        ->select('id', 'schedule_id', 'total_duration_minutes', 'start_time', 'end_time');
                }
            ])
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->get()
            ->map(function ($schedule) {
                $nurse = $schedule->nurse ?? null;
                
                // Calculate SCHEDULED duration based on start_time and end_time
                $duration = '0m';
                
                if ($schedule->start_time && $schedule->end_time) {
                    // Calculate from schedule times
                    $scheduleDate = Carbon::parse($schedule->schedule_date);
                    $startTime = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $schedule->start_time);
                    $endTime = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $schedule->end_time);
                    $durationMinutes = $startTime->diffInMinutes($endTime);
                    $duration = $this->formatDuration($durationMinutes);
                } elseif ($schedule->duration_minutes) {
                    // Fallback to duration_minutes field
                    $duration = $this->formatDuration($schedule->duration_minutes);
                }

                // Calculate ACTUAL COMPLETED duration from time tracking
                $durationCompleted = null;
                if ($schedule->status === 'completed' && $schedule->timeTracking) {
                    $totalMinutes = $schedule->timeTracking->total_duration_minutes;
                    if ($totalMinutes > 0) {
                        $durationCompleted = $this->formatDuration($totalMinutes);
                    }
                }

                return [
                    'id' => $schedule->id,
                    'date' => Carbon::parse($schedule->schedule_date)->format('Y-m-d'),
                    'dateDisplay' => Carbon::parse($schedule->schedule_date)->isToday()
                        ? 'Today'
                        : Carbon::parse($schedule->schedule_date)->format('M d, Y'),
                    'time' => Carbon::parse($schedule->start_time)->format('h:i A'),
                    'endTime' => $schedule->end_time ? Carbon::parse($schedule->end_time)->format('h:i A') : null,
                    'duration' => $duration, // Scheduled duration (8h)
                    'duration_completed' => $durationCompleted, // Actual tracked duration (4m)
                    'status' => $schedule->status,
                    'carePlanTitle' => $schedule->carePlan->title ?? 'Care Plan Visit',
                    'careType' => $schedule->carePlan->care_type ?? 'General Care',
                    'priority' => $schedule->carePlan->priority ?? 'medium',
                    'location' => $schedule->location ?? 'Home',
                    'nurse' => $nurse ? [
                        'id' => $nurse->id,
                        'name' => $nurse->first_name . ' ' . $nurse->last_name,
                        'specialization' => $nurse->specialization ?? 'Nurse',
                        'phone' => $nurse->phone,
                        'avatar' => $nurse->avatar 
                            ? url('/storage/' . $nurse->avatar)
                            : 'https://ui-avatars.com/api/?name=' .
                                urlencode($nurse->first_name . '+' . $nurse->last_name) .
                                '&background=e3f2fd&color=1976d2'
                    ] : null
                ];
            });

        // 5. Upcoming nurses (next 2 unique nurses with their schedules)
        $upcomingSchedulesRaw = Schedule::whereHas('carePlan', function($query) use ($user) {
                $query->where('patient_id', $user->id);
            })
            ->where(function ($query) use ($today, $now) {
                $query->where('schedule_date', '>', $today)
                    ->orWhere(function ($q) use ($today, $now) {
                        $q->where('schedule_date', '=', $today)
                            ->where('start_time', '>=', $now->format('H:i:s'));
                    });
            })
            ->whereIn('status', ['scheduled', 'pending'])
            ->with([
                'carePlan:id,title,care_type,priority',
                'nurse:id,first_name,last_name,specialization,phone,avatar',
            ])
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->limit(10)
            ->get();

        // Get latest vitals for this patient
        $latestVitals = ProgressNote::where('patient_id', $user->id)
            ->whereNotNull('vitals')
            ->orderBy('visit_date', 'desc')
            ->orderBy('visit_time', 'desc')
            ->first();

        $currentVitals = null;
        if ($latestVitals && $latestVitals->vitals) {
            $currentVitals = [
                'blood_pressure' => $latestVitals->vitals['blood_pressure'] ?? 'N/A',
                'temperature' => $latestVitals->vitals['temperature'] ?? 'N/A',
                'pulse' => $latestVitals->vitals['pulse'] ?? 'N/A',
                'spo2' => $latestVitals->vitals['spo2'] ?? 'N/A',
                'respiration' => $latestVitals->vitals['respiration'] ?? 'N/A',
                'recordedAt' => $latestVitals->visit_date ? 
                    Carbon::parse($latestVitals->visit_date)->diffForHumans() : 'N/A'
            ];
        }

        $upcomingNurses = $upcomingSchedulesRaw
            ->groupBy(fn($s) => $s->nurse_id ?? null)
            ->filter(fn($schedules, $nurseId) => $nurseId !== null)
            ->take(2)
            ->map(function ($schedules) use ($now, $currentVitals) {
                $firstSchedule = $schedules->first();
                $nurse = $firstSchedule->nurse ?? null;

                if (!$nurse) return null;

                $nurseSchedules = $schedules->map(function ($schedule) use ($now) {
                    $scheduleDate = Carbon::parse($schedule->schedule_date);
                    $startTime = $schedule->start_time;
                    
                    if (strlen($startTime) > 8) {
                        $tempCarbon = Carbon::parse($startTime);
                        $timeOnly = $tempCarbon->format('H:i:s');
                        $startTimeCarbon = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $timeOnly);
                    } else {
                        $startTimeCarbon = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $startTime);
                    }
                    
                    $scheduleDateTime = $startTimeCarbon;
                    $duration = $this->formatDuration($schedule->duration_minutes ?? 60);
                    $endTime = null;
                    
                    if ($schedule->end_time) {
                        $endTimeValue = $schedule->end_time;
                        if (strlen($endTimeValue) > 8) {
                            $tempCarbon = Carbon::parse($endTimeValue);
                            $timeOnly = $tempCarbon->format('H:i:s');
                            $endTimeCarbon = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $timeOnly);
                        } else {
                            $endTimeCarbon = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $endTimeValue);
                        }
                        
                        $endTime = $endTimeCarbon->format('h:i A');
                        $durationMinutes = $startTimeCarbon->diffInMinutes($endTimeCarbon);
                        $duration = $this->formatDuration($durationMinutes);
                    }

                    return [
                        'scheduleId' => $schedule->id,
                        'scheduledTime' => $scheduleDateTime->format('h:i A'),
                        'scheduledDate' => $scheduleDateTime->format('M d, Y'),
                        'endTime' => $endTime,
                        'timeUntil' => $scheduleDateTime->diffForHumans($now),
                        'isToday' => $scheduleDateTime->isToday(),
                        'careType' => $schedule->carePlan->care_type ?? 'General Care',
                        'location' => $schedule->location ?? 'Home',
                        'duration' => $duration,
                    ];
                })->values();

                $nextSchedule = $nurseSchedules->first();
                
                $mainDuration = $this->formatDuration($firstSchedule->duration_minutes ?? 60);
                $mainEndTime = null;
                
                if ($firstSchedule->end_time) {
                    $scheduleDate = Carbon::parse($firstSchedule->schedule_date);
                    $startTime = $firstSchedule->start_time;
                    
                    if (strlen($startTime) > 8) {
                        $tempCarbon = Carbon::parse($startTime);
                        $timeOnly = $tempCarbon->format('H:i:s');
                        $startTimeCarbon = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $timeOnly);
                    } else {
                        $startTimeCarbon = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $startTime);
                    }
                    
                    $endTimeValue = $firstSchedule->end_time;
                    if (strlen($endTimeValue) > 8) {
                        $tempCarbon = Carbon::parse($endTimeValue);
                        $timeOnly = $tempCarbon->format('H:i:s');
                        $endTimeCarbon = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $timeOnly);
                    } else {
                        $endTimeCarbon = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $endTimeValue);
                    }
                    
                    $mainEndTime = $endTimeCarbon->format('h:i A');
                    $durationMinutes = $startTimeCarbon->diffInMinutes($endTimeCarbon);
                    $mainDuration = $this->formatDuration($durationMinutes);
                }

                return [
                    'scheduleId' => $firstSchedule->id,
                    'scheduledTime' => $nextSchedule['scheduledTime'],
                    'scheduledDate' => $nextSchedule['scheduledDate'],
                    'endTime' => $mainEndTime,
                    'timeUntil' => $nextSchedule['timeUntil'],
                    'isToday' => $nextSchedule['isToday'],
                    'careType' => $firstSchedule->carePlan->care_type ?? 'General Care',
                    'priority' => $firstSchedule->carePlan->priority ?? 'medium',
                    'location' => $firstSchedule->location ?? 'Home',
                    'duration' => $mainDuration,
                    'upcomingSchedules' => $nurseSchedules,
                    'totalSchedules' => $schedules->count(),
                    'nurse' => [
                        'id' => $nurse->id,
                        'name' => $nurse->first_name . ' ' . $nurse->last_name,
                        'specialization' => $nurse->specialization ?? 'Nurse',
                        'phone' => $nurse->phone,
                        'avatar' => $nurse->avatar 
                            ? url('/storage/' . $nurse->avatar)
                            : 'https://ui-avatars.com/api/?name=' .
                                urlencode($nurse->first_name . '+' . $nurse->last_name) .
                                '&background=e3f2fd&color=1976d2'
                    ],
                    'currentVitals' => $currentVitals
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'weekHours' => round($weekHours, 1),
                'nursesToday' => $nursesToday,
                'activePlans' => $activePlans,
                'scheduleVisits' => $weekSchedules->values(),
                'upcomingNurses' => $upcomingNurses
            ]
        ]);
    }

    // Add this helper method to your controller
    private function formatDuration($minutes)
    {
        // Handle null, zero, or negative values
        if (!$minutes || $minutes <= 0) {
            return '0m';
        }
        
        // Ensure positive value
        $minutes = abs($minutes);
        
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($hours > 0 && $mins > 0) {
            return "{$hours}h {$mins}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$mins}m";
        }
    }
}