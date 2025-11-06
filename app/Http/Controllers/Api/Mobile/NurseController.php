<?php
namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CarePlan;
use App\Models\TimeTracking;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Schedule;
use App\Models\ProgressNote;
use App\Models\Driver;
use App\Models\TransportRequest;
use App\Models\IncidentReport;

class NurseController extends Controller
{
    /**
     * Get patients assigned to the authenticated nurse with pagination
     * GET /api/mobile/nurse/patients
     */
    public function getPatients(Request $request): JsonResponse
    {
                try {
            $nurse = auth()->user();
            
            // Verify the user is a nurse
            if ($nurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can access this endpoint.'
                ], 403);
            }

            // Get pagination parameters
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);

            // Get all active care plans where this nurse is assigned
            $carePlansQuery = CarePlan::where(function($query) use ($nurse) {
                    $query->where('primary_nurse_id', $nurse->id)
                        ->orWhere('secondary_nurse_id', $nurse->id);
                })
                ->where(function($query) {
                    $query->where('status', 'active')
                        ->orWhere('status', 'completed');
                })
                ->with([
                    'patient:id,first_name,last_name,date_of_birth,phone,avatar,emergency_contact_name,emergency_contact_phone,address',
                    'patient.latestProgressNote:id,patient_id,nurse_id,vitals,visit_date',
                    'schedules' => function($query) use ($nurse) {
                        $query->where('nurse_id', $nurse->id)
                            ->orderBy('schedule_date', 'desc')
                            ->orderBy('start_time', 'desc');
                    }
                ]);

            // Apply search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $carePlansQuery->where(function($query) use ($search) {
                    $query->whereHas('patient', function($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('care_type', 'like', "%{$search}%");
                });
            }

            // Apply priority filter
            if ($request->has('priority') && $request->priority !== 'all' && $request->priority !== 'All') {
                $priorityMap = [
                    'High Priority' => 'high',
                    'Medium Priority' => 'medium',
                    'Low Priority' => 'low',
                    'high' => 'high',
                    'medium' => 'medium',
                    'low' => 'low'
                ];
                
                if (isset($priorityMap[$request->priority])) {
                    $carePlansQuery->where('priority', $priorityMap[$request->priority]);
                }
            }

            // Get all care plans (before pagination)
            $allCarePlans = $carePlansQuery->get();

            // Group care plans by patient_id to get unique patients
            $groupedByPatient = $allCarePlans->groupBy('patient_id');

            // Get unique patient IDs
            $patientIds = $groupedByPatient->keys();

            // Paginate the patient IDs
            $paginatedPatientIds = $patientIds->forPage($page, $perPage);

            // Calculate pagination data
            $total = $patientIds->count();
            $lastPage = ceil($total / $perPage);
            $from = (($page - 1) * $perPage) + 1;
            $to = min($page * $perPage, $total);

            // Calculate priority counts for all patients
            $allCarePlansForCounts = CarePlan::where(function($query) use ($nurse) {
                    $query->where('primary_nurse_id', $nurse->id)
                        ->orWhere('secondary_nurse_id', $nurse->id);
                })
                ->where(function($query) {
                    $query->where('status', 'active')
                        ->orWhere('status', 'completed');
                });

            // Apply same search filter for counts
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $allCarePlansForCounts->where(function($query) use ($search) {
                    $query->whereHas('patient', function($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('care_type', 'like', "%{$search}%");
                });
            }

            $priorityCounts = [
                'all' => $allCarePlansForCounts->distinct('patient_id')->count('patient_id'),
                'high' => (clone $allCarePlansForCounts)->where('priority', 'high')->distinct('patient_id')->count('patient_id'),
                'medium' => (clone $allCarePlansForCounts)->where('priority', 'medium')->distinct('patient_id')->count('patient_id'),
                'low' => (clone $allCarePlansForCounts)->where('priority', 'low')->distinct('patient_id')->count('patient_id'),
            ];

            // Transform data for paginated patients
            $patients = $paginatedPatientIds->map(function($patientId) use ($groupedByPatient, $nurse) {
                $patientCarePlans = $groupedByPatient[$patientId];
                $firstCarePlan = $patientCarePlans->first();
                $patient = $firstCarePlan->patient;
                
                if (!$patient) {
                    return null;
                }

                // Calculate age
                $age = $patient->date_of_birth ? 
                    \Carbon\Carbon::parse($patient->date_of_birth)->age : 
                    null;

                // Get the highest priority from all care plans
                $priorities = $patientCarePlans->pluck('priority')->toArray();
                $highestPriority = in_array('high', $priorities) ? 'high' : 
                                (in_array('medium', $priorities) ? 'medium' : 'low');
                
                $priorityMap = [
                    'high' => 'High',
                    'medium' => 'Medium',
                    'low' => 'Low'
                ];
                $priority = $priorityMap[$highestPriority] ?? 'Medium';

                // Get all care types
                $careTypes = CarePlan::getCareTypes();
                $allCareTypes = $patientCarePlans->map(function($cp) use ($careTypes) {
                    return $careTypes[$cp->care_type] ?? ucfirst(str_replace('_', ' ', $cp->care_type));
                })->unique()->values()->toArray();
                
                // Use first care type for main display, but include all
                $primaryCareType = $allCareTypes[0] ?? 'General Care';

                // Get recent vitals from latest progress note
                $latestNote = $patient->latestProgressNote;

                $vitals = [
                    'blood_pressure' => 'N/A',
                    'heart_rate' => 'N/A',
                    'temperature' => 'N/A',
                    'spo2' => 'N/A',
                    'pulse' => 'N/A',
                    'respiration' => 'N/A',
                    'recordedAt' => null
                ];
                
                if ($latestNote && $latestNote->vitals) {
                    $vitals['blood_pressure'] = $latestNote->vitals['blood_pressure'] ?? 'N/A';
                    $vitals['heart_rate'] = $latestNote->vitals['pulse'] ?? 'N/A';
                    $vitals['temperature'] = $latestNote->vitals['temperature'] ?? 'N/A';
                    $vitals['spo2'] = $latestNote->vitals['spo2'] ?? 'N/A';
                    $vitals['pulse'] = $latestNote->vitals['pulse'] ?? 'N/A';
                    $vitals['respiration'] = $latestNote->vitals['respiration'] ?? 'N/A';
                    $vitals['recordedAt'] = $latestNote->visit_date;
                }

                // Get all schedules from all care plans
                $allSchedules = collect();
                foreach ($patientCarePlans as $cp) {
                    $schedules = $cp->schedules->filter(function($schedule) use ($nurse) {
                        return $schedule->nurse_id === $nurse->id;
                    });
                    $allSchedules = $allSchedules->merge($schedules);
                }

                $today = \Carbon\Carbon::today();
                $now = \Carbon\Carbon::now();

                // Last visit - most recent completed schedule
                $lastVisit = $allSchedules
                    ->where('status', 'completed')
                    ->sortByDesc(function($schedule) {
                        return $this->getScheduleDateTime($schedule);
                    })
                    ->first();

                // Next visit - upcoming scheduled or in-progress visit
                $nextVisit = $allSchedules
                    ->filter(function($schedule) use ($today, $now) {
                        try {
                            $scheduleDateTime = $this->getScheduleDateTime($schedule);
                            $scheduleDate = \Carbon\Carbon::parse($scheduleDateTime)->startOfDay();
                            
                            return ($scheduleDate->isAfter($today) || 
                                    ($scheduleDate->isToday() && \Carbon\Carbon::parse($scheduleDateTime)->isAfter($now)))
                                && in_array($schedule->status, ['scheduled', 'pending', 'in_progress']);
                        } catch (\Exception $e) {
                            \Log::error('Error filtering schedule: ' . $e->getMessage());
                            return false;
                        }
                    })
                    ->sortBy(function($schedule) {
                        return $this->getScheduleDateTime($schedule);
                    })
                    ->first();

                // Build avatar URL
                $avatarUrl = $patient->avatar 
                    ? url('/storage/' . $patient->avatar)
                    : 'https://ui-avatars.com/api/?name=' . 
                        urlencode($patient->first_name . '+' . $patient->last_name) . 
                        '&background=e3f2fd&color=1976d2';

                // Format all care plans for this patient
                $carePlansData = $patientCarePlans->map(function($cp) use ($careTypes) {
                    return [
                        'id' => $cp->id,
                        'title' => $cp->title,
                        'care_type' => $careTypes[$cp->care_type] ?? ucfirst(str_replace('_', ' ', $cp->care_type)),
                        'priority' => ucfirst($cp->priority),
                        'status' => ucfirst($cp->status),
                        'start_date' => $cp->start_date,
                        'end_date' => $cp->end_date,
                        'goals' => $cp->goals,
                        'interventions' => $cp->interventions,
                    ];
                })->values()->toArray();

                return [
                    'id' => $patient->id,
                    'name' => $patient->first_name . ' ' . $patient->last_name,
                    'age' => $age,
                    'careType' => $primaryCareType,
                    'allCareTypes' => $allCareTypes, 
                    'condition' => $firstCarePlan->title ?? 'Care Plan',
                    'carePlanId' => $firstCarePlan->id, 
                    'carePlanTitle' => $firstCarePlan->title,
                    'carePlans' => $carePlansData, 
                    'carePlansCount' => $patientCarePlans->count(), 
                    'lastVisit' => $lastVisit ? $this->formatScheduleDateTime($lastVisit) : null,
                    'nextVisit' => $nextVisit ? $this->formatScheduleDateTime($nextVisit) : null,
                    'status' => 'Active',
                    'priority' => $priority, // Highest priority from all care plans
                    'vitals' => $vitals,
                    'address' => $patient->address ?? 'Address not provided',
                    'phone' => $patient->phone,
                    'emergencyContact' => $patient->emergency_contact_name ?? 'Not provided',
                    'emergencyPhone' => $patient->emergency_contact_phone ?? 'Not provided',
                    'avatar' => $avatarUrl
                ];
            })
            ->filter() // Remove null values
            ->values(); // Reset array keys

            return response()->json([
                'success' => true,
                'data' => $patients,
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage,
                'from' => $from,
                'to' => $to,
                'counts' => $priorityCounts,
                'message' => $patients->isEmpty() ? 'No patients assigned yet.' : null
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching nurse patients: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch patients. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get detailed patient information
     * GET /api/mobile/nurse/patients/{patientId}
     */
    public function getPatientDetail(Request $request, $patientId): JsonResponse
    {
                 try {
                $nurse = auth()->user();
                
                // Verify the user is a nurse
                if ($nurse->role !== 'nurse') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only nurses can access this endpoint.'
                    ], 403);
                }

                // Find the patient
                $patient = User::where('id', $patientId)
                    ->where('role', 'patient')
                    ->first();

                if (!$patient) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Patient not found.'
                    ], 404);
                }

                // Get ALL active care plans for this patient where nurse is assigned
                $carePlans = CarePlan::where('patient_id', $patientId)
                    ->where(function($query) use ($nurse) {
                        $query->where('primary_nurse_id', $nurse->id)
                            ->orWhere('secondary_nurse_id', $nurse->id);
                    })
                    ->whereIn('status', ['active', 'completed'])
                    ->with([
                        'doctor:id,first_name,last_name,specialization',
                        'schedules' => function($query) use ($nurse) {
                            $query->where('nurse_id', $nurse->id)
                                ->orderBy('schedule_date', 'desc')
                                ->orderBy('start_time', 'desc')
                                ->limit(10);
                        }
                    ])
                    ->orderBy('priority', 'desc') // High priority first
                    ->orderBy('created_at', 'desc')
                    ->get();

                if ($carePlans->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are not assigned to this patient.'
                    ], 403);
                }

                // Get recent progress notes with ALL fields
                $recentNotes = \App\Models\ProgressNote::where('patient_id', $patientId)
                    ->where('nurse_id', $nurse->id)
                    ->orderBy('visit_date', 'desc')
                    ->orderBy('visit_time', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function($note) {
                        return [
                            'id' => $note->id,
                            'visit_date' => $note->visit_date ? 
                                \Carbon\Carbon::parse($note->visit_date)->format('Y-m-d') : null,
                            'visit_time' => $note->visit_time ? 
                                \Carbon\Carbon::parse($note->visit_time)->format('H:i') : null,
                            'vitals' => $note->vitals,
                            'interventions' => $note->interventions,
                            'general_condition' => $note->general_condition,
                            'pain_level' => $note->pain_level,
                            'wound_status' => $note->wound_status,
                            'other_observations' => $note->other_observations,
                            'education_provided' => $note->education_provided,
                            'family_concerns' => $note->family_concerns,
                            'next_steps' => $note->next_steps,
                            'created_at' => $note->created_at ? 
                                $note->created_at->toIso8601String() : null,
                        ];
                    });

                // Get latest vitals
                $latestNote = \App\Models\ProgressNote::where('patient_id', $patientId)
                    ->whereNotNull('vitals')
                    ->orderBy('visit_date', 'desc')
                    ->orderBy('visit_time', 'desc')
                    ->first();

                $vitals = null;
                if ($latestNote && $latestNote->vitals) {
                    $vitals = [
                        'blood_pressure' => $latestNote->vitals['blood_pressure'] ?? 'N/A',
                        'temperature' => $latestNote->vitals['temperature'] ?? 'N/A',
                        'spo2' => $latestNote->vitals['spo2'] ?? 'N/A',
                        'pulse' => $latestNote->vitals['pulse'] ?? 'N/A',
                        'respiration' => $latestNote->vitals['respiration'] ?? 'N/A',
                        'recordedAt' => $latestNote->visit_date
                    ];
                }

                // Calculate age
                $age = $patient->date_of_birth ? 
                    \Carbon\Carbon::parse($patient->date_of_birth)->age : 
                    null;

                // Build full avatar URL
                $avatarUrl = $patient->avatar 
                    ? url('/storage/' . $patient->avatar)
                    : 'https://ui-avatars.com/api/?name=' . 
                        urlencode($patient->first_name . '+' . $patient->last_name) . 
                        '&background=e3f2fd&color=1976d2';

                // Helper functions
                $careTypes = CarePlan::getCareTypes();
                $priorityMap = [
                    'high' => 'High',
                    'medium' => 'Medium',
                    'low' => 'Low'
                ];

                // Get all schedules from all care plans
                $allSchedules = collect();
                foreach ($carePlans as $cp) {
                    $allSchedules = $allSchedules->merge($cp->schedules);
                }

                // Format all care plans
                $carePlansData = $carePlans->map(function($carePlan) use ($careTypes, $priorityMap) {
                    $careTypeLabel = $careTypes[$carePlan->care_type] ?? ucfirst(str_replace('_', ' ', $carePlan->care_type));
                    $priority = $priorityMap[$carePlan->priority] ?? 'Medium';

                    return [
                        'id' => $carePlan->id,
                        'title' => $carePlan->title,
                        'description' => $carePlan->description,
                        'careType' => $careTypeLabel,
                        'priority' => $priority,
                        'startDate' => $carePlan->start_date,
                        'endDate' => $carePlan->end_date,
                        'frequency' => $carePlan->frequency,
                        'careTasks' => $carePlan->care_tasks ?? [],
                        'completedTasks' => $carePlan->completed_tasks ?? [],
                        'medications' => $carePlan->medications ?? [],
                        'vitalMonitoring' => $carePlan->vital_monitoring ?? [],
                        'dietaryRequirements' => $carePlan->dietary_requirements,
                        'mobilityAssistance' => $carePlan->mobility_assistance,
                        'specialInstructions' => $carePlan->special_instructions,
                        'emergencyProcedures' => $carePlan->emergency_procedures,
                        'completionPercentage' => $carePlan->completion_percentage ?? 0,
                        'status' => ucfirst($carePlan->status),
                        'doctor' => $carePlan->doctor ? [
                            'id' => $carePlan->doctor->id,
                            'name' => $carePlan->doctor->first_name . ' ' . $carePlan->doctor->last_name,
                            'specialization' => $carePlan->doctor->specialization ?? 'General Practice'
                        ] : null,
                    ];
                })->values()->toArray();

                // Use the first (primary) care plan for backward compatibility
                $primaryCarePlan = $carePlansData[0];

                $patientDetail = [
                    'id' => $patient->id,
                    'name' => $patient->first_name . ' ' . $patient->last_name,
                    'age' => $age,
                    'dateOfBirth' => $patient->date_of_birth,
                    'gender' => $patient->gender,
                    'phone' => $patient->phone,
                    'email' => $patient->email,
                    'address' => $patient->address ?? 'Address not provided',
                    'avatar' => $avatarUrl,
                    'emergencyContact' => [
                        'name' => $patient->emergency_contact_name ?? 'Not provided',
                        'phone' => $patient->emergency_contact_phone ?? 'Not provided'
                    ],
                    'medicalInfo' => [
                        'conditions' => $patient->medical_conditions ?? [],
                        'allergies' => $patient->allergies ?? [],
                        'currentMedications' => $patient->current_medications ?? []
                    ],
                    // Primary care plan for backward compatibility
                    'carePlan' => $primaryCarePlan,
                    'carePlans' => $carePlansData,
                    'carePlansCount' => count($carePlansData),
                    'vitals' => $vitals,
                    'recentNotes' => $recentNotes,
                    // Combined schedules from all care plans
                    'schedules' => $allSchedules->map(function($schedule) {
                        return [
                            'id' => $schedule->id,
                            'date' => $schedule->schedule_date,
                            'startTime' => $schedule->start_time,
                            'endTime' => $schedule->end_time,
                            'duration' => $schedule->duration,
                            'status' => $schedule->status,
                            'location' => $schedule->location,
                            'notes' => $schedule->notes
                        ];
                    })->values()->toArray()
                ];

                return response()->json([
                    'success' => true,
                    'data' => $patientDetail
                ]);

            } catch (\Exception $e) {
                \Log::error('Error fetching patient detail: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch patient details. Please try again.',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
    }

    /**
     * Helper: Transform patient data for list view
     */
    private function transformPatientData($patientCarePlans, $nurse): ?array
    {
        $firstCarePlan = $patientCarePlans->first();
        $patient = $firstCarePlan->patient;
        
        if (!$patient) return null;

        // Calculate age
        $age = $patient->date_of_birth ? 
            Carbon::parse($patient->date_of_birth)->age : null;

        // Get highest priority
        $priorities = $patientCarePlans->pluck('priority')->toArray();
        $highestPriority = in_array('high', $priorities) ? 'high' : 
                        (in_array('medium', $priorities) ? 'medium' : 'low');

        return [
            'id' => $patient->id,
            'name' => $patient->first_name . ' ' . $patient->last_name,
            'age' => $age,
            'avatar' => $patient->avatar 
                ? url('/storage/' . $patient->avatar)
                : 'https://ui-avatars.com/api/?name=' . urlencode($patient->first_name),
            'carePlansCount' => $patientCarePlans->count(),
            'priority' => ucfirst($highestPriority),
            // Add more fields as needed
        ];
    }

    /**
     * Helper: Build patient detail response
     */
    private function buildPatientDetailResponse($patient, $carePlans, $nurse): array
    {
        return [
            'id' => $patient->id,
            'name' => $patient->first_name . ' ' . $patient->last_name,
            'email' => $patient->email,
            'phone' => $patient->phone,
            'carePlans' => $carePlans->map(function($cp) {
                return [
                    'id' => $cp->id,
                    'title' => $cp->title,
                    'status' => $cp->status,
                    // Add more fields
                ];
            }),
            // Add more fields
        ];
    }


    public function nurseMobileDashboard(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'nurse') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        $now = Carbon::now();

        // 1. Shift total hours for the week
        $weekHours = TimeTracking::where('nurse_id', $user->id)
            ->whereBetween('start_time', [$weekStart, $weekEnd])
            ->where('status', 'completed')
            ->sum(DB::raw('total_duration_minutes')) / 60;

        // 2. Total active care plans
        $activePlans = CarePlan::where('status', 'active')
            ->where(function($query) use ($user) {
                $query->where('primary_nurse_id', $user->id)
                    ->orWhere('secondary_nurse_id', $user->id);
            })
            ->count();

        // 3. Patients assigned for today
        $todayPatients = DB::table('schedules')
            ->join('care_plans', 'schedules.care_plan_id', '=', 'care_plans.id')
            ->where('schedules.nurse_id', $user->id)
            ->whereDate('schedules.schedule_date', $today)
            ->where('care_plans.status', 'active')
            ->whereNull('schedules.deleted_at')
            ->whereNull('care_plans.deleted_at')
            ->distinct('care_plans.patient_id')
            ->count('care_plans.patient_id');

        // 4. Weekly schedules with details (INCLUDING ACTUAL TRACKED TIME)
        $weekSchedules = Schedule::where('nurse_id', $user->id)
            ->whereBetween('schedule_date', [$weekStart, $weekEnd])
            ->with([
                'carePlan:id,title,care_type,priority,patient_id',
                'carePlan.patient:id,first_name,last_name,date_of_birth,avatar',
                'timeTracking' => function($query) {
                    $query->where('status', 'completed')
                        ->select('id', 'schedule_id', 'total_duration_minutes', 'start_time', 'end_time');
                }
            ])
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->get()
            ->map(function ($schedule) {
                $patient = $schedule->carePlan->patient ?? null;
                
                // Initialize duration from scheduled duration_minutes
                $duration = $this->formatDuration($schedule->duration_minutes ?? 60);
                
                // Override with actual tracked time if completed
                if ($schedule->status === 'completed' && $schedule->timeTracking) {
                    $totalMinutes = $schedule->timeTracking->total_duration_minutes;
                    if ($totalMinutes > 0) {
                        $duration = $this->formatDuration($totalMinutes);
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
                    'duration' => $duration,
                    'status' => $schedule->status,
                    'carePlanTitle' => $schedule->carePlan->title ?? 'Care Plan Visit',
                    'careType' => $schedule->carePlan->care_type ?? 'General Care',
                    'priority' => $schedule->carePlan->priority ?? 'medium',
                    'location' => $schedule->location ?? 'Patient Home',
                    'patient' => $patient ? [
                        'id' => $patient->id,
                        'name' => $patient->first_name . ' ' . $patient->last_name,
                        'age' => Carbon::parse($patient->date_of_birth)->age,
                        'avatar' => $patient->avatar ?? 'https://ui-avatars.com/api/?name=' .
                            urlencode($patient->first_name . '+' . $patient->last_name) .
                            '&background=e3f2fd&color=1976d2'
                    ] : null
                ];
            });

        // 5. Upcoming schedules (next 2 unique patients)
        $upcomingSchedulesRaw = Schedule::where('nurse_id', $user->id)
            ->where(function ($query) use ($today, $now) {
                $query->where('schedule_date', '>', $today)
                    ->orWhere(function ($q) use ($today, $now) {
                        $q->where('schedule_date', '=', $today)
                            ->where('start_time', '>=', $now->format('H:i:s'));
                    });
            })
            ->whereIn('status', ['scheduled', 'pending'])
            ->with([
                'carePlan:id,title,care_type,priority,patient_id',
                'carePlan.patient:id,first_name,last_name,date_of_birth,avatar,phone,emergency_contact_name,emergency_contact_phone',
                'carePlan.patient.latestProgressNote:id,patient_id,vitals,visit_date'
            ])
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->limit(10)
            ->get();

        $upcomingPatients = $upcomingSchedulesRaw
            ->groupBy(fn($s) => $s->carePlan->patient_id ?? null)
            ->filter(fn($schedules, $pid) => $pid !== null)
            ->take(2)
            ->map(function ($schedules) use ($now) {
                $firstSchedule = $schedules->first();
                $patient = $firstSchedule->carePlan->patient ?? null;

                if (!$patient) return null;

                $latestNote = $patient->latestProgressNote;

                $patientSchedules = $schedules->map(function ($schedule) use ($now) {
                    // ALWAYS use schedule_date as the authoritative date
                    $scheduleDate = Carbon::parse($schedule->schedule_date);
                    
                    // Parse start_time - extract time only, then combine with schedule_date
                    $startTime = $schedule->start_time;
                    if (strlen($startTime) > 8) {
                        // It's a full datetime, extract time part only
                        $tempCarbon = Carbon::parse($startTime);
                        $timeOnly = $tempCarbon->format('H:i:s');
                        $startTimeCarbon = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $timeOnly);
                    } else {
                        // It's just time, combine with schedule date
                        $startTimeCarbon = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $startTime);
                    }
                    
                    $scheduleDateTime = $startTimeCarbon;
                    
                    // Calculate duration from start_time and end_time
                    $duration = $this->formatDuration($schedule->duration_minutes ?? 60);
                    $endTime = null;
                    
                    if ($schedule->end_time) {
                        // Parse end_time - extract time only, then combine with schedule_date
                        $endTimeValue = $schedule->end_time;
                        if (strlen($endTimeValue) > 8) {
                            // It's a full datetime, extract time part only
                            $tempCarbon = Carbon::parse($endTimeValue);
                            $timeOnly = $tempCarbon->format('H:i:s');
                            $endTimeCarbon = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $timeOnly);
                        } else {
                            // It's just time, combine with schedule date
                            $endTimeCarbon = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $endTimeValue);
                        }
                        
                        $endTime = $endTimeCarbon->format('h:i A');
                        
                        // Calculate actual duration in minutes from time difference
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
                        'location' => $schedule->location ?? 'Patient Home',
                        'duration' => $duration,
                    ];
                })->values();

                $nextSchedule = $patientSchedules->first();
                
                // Calculate duration for the main schedule (first schedule)
                $mainDuration = $this->formatDuration($firstSchedule->duration_minutes ?? 60);
                $mainEndTime = null;
                
                if ($firstSchedule->end_time) {
                    // ALWAYS use schedule_date as the authoritative date
                    $scheduleDate = Carbon::parse($firstSchedule->schedule_date);
                    
                    // Parse start_time - extract time only
                    $startTime = $firstSchedule->start_time;
                    if (strlen($startTime) > 8) {
                        $tempCarbon = Carbon::parse($startTime);
                        $timeOnly = $tempCarbon->format('H:i:s');
                        $startTimeCarbon = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $timeOnly);
                    } else {
                        $startTimeCarbon = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $startTime);
                    }
                    
                    // Parse end_time - extract time only
                    $endTimeValue = $firstSchedule->end_time;
                    if (strlen($endTimeValue) > 8) {
                        $tempCarbon = Carbon::parse($endTimeValue);
                        $timeOnly = $tempCarbon->format('H:i:s');
                        $endTimeCarbon = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $timeOnly);
                    } else {
                        $endTimeCarbon = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $endTimeValue);
                    }
                    
                    $mainEndTime = $endTimeCarbon->format('h:i A');
                    
                    // Calculate actual duration in minutes from time difference
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
                    'location' => $firstSchedule->location ?? 'Patient Home',
                    'duration' => $mainDuration,
                    'upcomingSchedules' => $patientSchedules,
                    'totalSchedules' => $schedules->count(),
                    'patient' => [
                        'id' => $patient->id,
                        'name' => $patient->first_name . ' ' . $patient->last_name,
                        'age' => Carbon::parse($patient->date_of_birth)->age,
                        'phone' => $patient->phone,
                        'avatar' => $patient->avatar ?? 'https://ui-avatars.com/api/?name=' .
                            urlencode($patient->first_name . '+' . $patient->last_name) .
                            '&background=e3f2fd&color=1976d2',
                        'emergencyContact' => [
                            'name' => $patient->emergency_contact_name,
                            'phone' => $patient->emergency_contact_phone
                        ],
                        'lastVitals' => $latestNote && $latestNote->vitals ? [
                            'blood_pressure' => $latestNote->vitals['blood_pressure'] ?? 'N/A',
                            'temperature' => $latestNote->vitals['temperature'] ?? 'N/A',
                            'pulse' => $latestNote->vitals['pulse'] ?? 'N/A',
                            'spo2' => $latestNote->vitals['spo2'] ?? 'N/A',
                            'respiration' => $latestNote->vitals['respiration'] ?? 'N/A',
                            'recordedAt' => Carbon::parse($latestNote->visit_date)->diffForHumans()
                        ] : null
                    ]
                ];
            })
            ->filter()
            ->values();
        return response()->json([
            'success' => true,
            'data' => [
                'weekHours' => round($weekHours, 1),
                'activePlans' => $activePlans,
                'todayPatients' => $todayPatients,
                'weekSchedules' => $weekSchedules->groupBy('date')->map(function ($daySchedules) {
                    return [
                        'date' => $daySchedules->first()['date'],
                        'dateDisplay' => $daySchedules->first()['dateDisplay'],
                        'count' => $daySchedules->count(),
                        'schedules' => $daySchedules->values()
                    ];
                })->values(),
                'scheduleVisits' => $weekSchedules->values(),
                'upcomingPatients' => $upcomingPatients 
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

            /**
         * Helper method to get schedule datetime as Carbon instance
         * This extracts just the TIME from start_time and combines with schedule_date
         */
        private function getScheduleDateTime($schedule): Carbon
        {
            try {
                // Get schedule date
                $scheduleDate = Carbon::parse($schedule->schedule_date);
                
                // Parse start_time and extract ONLY the time components
                $startTime = Carbon::parse($schedule->start_time);
                
                // Create a new Carbon instance with correct date and time
                return Carbon::create(
                    $scheduleDate->year,
                    $scheduleDate->month,
                    $scheduleDate->day,
                    $startTime->hour,
                    $startTime->minute,
                    $startTime->second
                );
            } catch (\Exception $e) {
                \Log::error('Error parsing schedule datetime', [
                    'error' => $e->getMessage(),
                    'schedule_id' => $schedule->id ?? 'unknown',
                    'schedule_date' => $schedule->schedule_date ?? 'unknown',
                    'start_time' => $schedule->start_time ?? 'unknown'
                ]);
                
                // Fallback: return schedule date at midnight
                return Carbon::parse($schedule->schedule_date)->startOfDay();
            }
        }

        /**
         * Helper method to format schedule datetime as ISO 8601 string
         */
        private function formatScheduleDateTime($schedule): string
        {
            try {
                return $this->getScheduleDateTime($schedule)->toIso8601String();
            } catch (\Exception $e) {
                \Log::error('Error formatting schedule datetime: ' . $e->getMessage());
                return Carbon::now()->toIso8601String();
            }
        }

}