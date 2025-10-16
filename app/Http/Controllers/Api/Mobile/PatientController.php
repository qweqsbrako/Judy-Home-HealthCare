<?php
namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\CarePlan;
use App\Models\Schedule;
use App\Models\MedicalAssessment;
use App\Models\IncidentReport;
use App\Models\TransportRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Mail\UserInvitationMail;
use App\Http\Resources\UserResource;
use Carbon\Carbon;


class PatientController extends Controller
{
    /**
     * Get patients assigned to the authenticated nurse
     * Used for the nurse's patient list screen
     */
        public function nursePatients(Request $request): JsonResponse
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

                // Get all active care plans where this nurse is assigned (primary or secondary)
                $carePlansQuery = CarePlan::where(function($query) use ($nurse) {
                        $query->where('primary_nurse_id', $nurse->id)
                            ->orWhere('secondary_nurse_id', $nurse->id);
                    })
                ->where('status', 'active')
                    ->orWhere('status', 'completed')
                    ->with([
                        'patient:id,first_name,last_name,date_of_birth,phone,avatar,emergency_contact_name,emergency_contact_phone',
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
                if ($request->has('priority') && $request->priority !== 'all') {
                    $priorityMap = [
                        'High Priority' => 'high',
                        'Medium Priority' => 'medium',
                        'Low Priority' => 'low'
                    ];
                    
                    if (isset($priorityMap[$request->priority])) {
                        $carePlansQuery->where('priority', $priorityMap[$request->priority]);
                    }
                }

                $carePlans = $carePlansQuery->get();

                // Transform the data for the mobile app
                $patients = $carePlans->map(function($carePlan) use ($nurse) {
                    $patient = $carePlan->patient;
                    
                    if (!$patient) {
                        return null;
                    }

                    // Calculate age
                    $age = $patient->date_of_birth ? 
                        \Carbon\Carbon::parse($patient->date_of_birth)->age : 
                        null;

                    // Get care type label
                    $careTypes = CarePlan::getCareTypes();
                    $careTypeLabel = $careTypes[$carePlan->care_type] ?? ucfirst(str_replace('_', ' ', $carePlan->care_type));

                    // Get priority
                    $priorityMap = [
                        'high' => 'High',
                        'medium' => 'Medium',
                        'low' => 'Low'
                    ];
                    $priority = $priorityMap[$carePlan->priority] ?? 'Medium';

                    // Get recent vitals from latest progress note
                    $latestNote = $patient->latestProgressNote;

                    // Initialize with default structure
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
                        $vitals['heart_rate'] = $latestNote->vitals['pulse'] ?? 'N/A'; // pulse = heartRate
                        $vitals['temperature'] = $latestNote->vitals['temperature'] ?? 'N/A';
                        $vitals['spo2'] = $latestNote->vitals['spo2'] ?? 'N/A'; // spo2 = oxygen saturation
                        $vitals['pulse'] = $latestNote->vitals['pulse'] ?? 'N/A';
                        $vitals['respiration'] = $latestNote->vitals['respiration'] ?? 'N/A';
                        $vitals['recordedAt'] = $latestNote->visit_date;
                    }

                    // Get last and next visit schedules
                    $schedules = $carePlan->schedules->filter(function($schedule) use ($nurse) {
                        return $schedule->nurse_id === $nurse->id;
                    });

                    $today = \Carbon\Carbon::today();
                    $now = \Carbon\Carbon::now();

                    // Last visit - most recent completed schedule
                    $lastVisit = $schedules
                        ->where('status', 'completed')
                        ->sortByDesc(function($schedule) {
                            return $this->getScheduleDateTime($schedule);
                        })
                        ->first();

                    // Next visit - upcoming scheduled or in-progress visit
                    $nextVisit = $schedules
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

                    return [
                        'id' => $patient->id,
                        'name' => $patient->first_name . ' ' . $patient->last_name,
                        'age' => $age,
                        'careType' => $careTypeLabel,
                        'condition' => $carePlan->title ?? 'Care Plan',
                        'carePlanId' => $carePlan->id,
                        'carePlanTitle' => $carePlan->title,
                        'lastVisit' => $lastVisit ? $this->formatScheduleDateTime($lastVisit) : null,
                        'nextVisit' => $nextVisit ? $this->formatScheduleDateTime($nextVisit) : null,
                        'status' => 'Active',
                        'priority' => $priority,
                        'vitals' => $vitals ?: [
                            'bloodPressure' => 'N/A',
                            'heartRate' => 'N/A',
                            'temperature' => 'N/A',
                            'oxygenSaturation' => 'N/A',
                            'recordedAt' => null
                        ],
                        'address' => $patient->address ?? 'Address not provided',
                        'phone' => $patient->phone,
                        'emergencyContact' => $patient->emergency_contact_name ?? 'Not provided',
                        'emergencyPhone' => $patient->emergency_contact_phone ?? 'Not provided',
                        'avatar' => $patient->avatar ?? 'https://ui-avatars.com/api/?name=' . 
                            urlencode($patient->first_name . '+' . $patient->last_name) . 
                            '&background=e3f2fd&color=1976d2'
                    ];
                })
                ->filter() // Remove null values
                ->values(); // Reset array keys

                \Log::info($patients);

                return response()->json([
                    'success' => true,
                    'data' => $patients,
                    'total' => $patients->count(),
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

        /**
         * Get detailed patient information for nurse
         * Used when nurse taps on a specific patient
         */
        public function nursePatientDetail(Request $request, $patientId): JsonResponse
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

                // Get the active care plan for this patient where nurse is assigned
                $carePlan = CarePlan::where('patient_id', $patientId)
                    ->where(function($query) use ($nurse) {
                        $query->where('primary_nurse_id', $nurse->id)
                            ->orWhere('secondary_nurse_id', $nurse->id);
                    })
                    ->where('status', 'active')
                    ->with([
                        'doctor:id,first_name,last_name,specialization',
                        'schedules' => function($query) use ($nurse) {
                            $query->where('nurse_id', $nurse->id)
                                ->orderBy('schedule_date', 'desc')
                                ->orderBy('start_time', 'desc')
                                ->limit(10);
                        }
                    ])
                    ->first();

                if (!$carePlan) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are not assigned to this patient.'
                    ], 403);
                }

                // Get recent progress notes
                $recentNotes = \App\Models\ProgressNote::where('patient_id', $patientId)
                    ->where('nurse_id', $nurse->id)
                    ->orderBy('visit_date', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function($note) {
                        return [
                            'id' => $note->id,
                            'visitDate' => $note->visit_date,
                            'vitals' => $note->vitals,
                            'generalCondition' => $note->general_condition,
                            'painLevel' => $note->pain_level,
                            'observations' => $note->observations,
                            'interventionsProvided' => $note->interventions_provided,
                            'createdAt' => $note->created_at
                        ];
                    });

                // Get latest vitals
                $latestNote = \App\Models\ProgressNote::where('patient_id', $patientId)
                    ->whereNotNull('vitals')
                    ->orderBy('visit_date', 'desc')
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

                // Get care type label
                $careTypes = CarePlan::getCareTypes();
                $careTypeLabel = $careTypes[$carePlan->care_type] ?? ucfirst(str_replace('_', ' ', $carePlan->care_type));

                // Get priority
                $priorityMap = [
                    'high' => 'High',
                    'medium' => 'Medium',
                    'low' => 'Low'
                ];
                $priority = $priorityMap[$carePlan->priority] ?? 'Medium';

                $patientDetail = [
                    'id' => $patient->id,
                    'name' => $patient->first_name . ' ' . $patient->last_name,
                    'age' => $age,
                    'dateOfBirth' => $patient->date_of_birth,
                    'gender' => $patient->gender,
                    'phone' => $patient->phone,
                    'email' => $patient->email,
                    'address' => $patient->address ?? 'Address not provided',
                    'avatar' => $patient->avatar ?? 'https://ui-avatars.com/api/?name=' . 
                        urlencode($patient->first_name . '+' . $patient->last_name) . 
                        '&background=e3f2fd&color=1976d2',
                    'emergencyContact' => [
                        'name' => $patient->emergency_contact_name ?? 'Not provided',
                        'phone' => $patient->emergency_contact_phone ?? 'Not provided'
                    ],
                    'medicalInfo' => [
                        'conditions' => $patient->medical_conditions ?? [],
                        'allergies' => $patient->allergies ?? [],
                        'currentMedications' => $patient->current_medications ?? []
                    ],
                    'carePlan' => [
                        'id' => $carePlan->id,
                        'title' => $carePlan->title,
                        'description' => $carePlan->description,
                        'careType' => $careTypeLabel,
                        'priority' => $priority,
                        'startDate' => $carePlan->start_date,
                        'endDate' => $carePlan->end_date,
                        'frequency' => $carePlan->frequency,
                        'careTasks' => $carePlan->care_tasks,
                        'medications' => $carePlan->medications,
                        'vitalMonitoring' => $carePlan->vital_monitoring,
                        'dietaryRequirements' => $carePlan->dietary_requirements,
                        'mobilityAssistance' => $carePlan->mobility_assistance,
                        'specialInstructions' => $carePlan->special_instructions,
                        'emergencyProcedures' => $carePlan->emergency_procedures,
                        'completionPercentage' => $carePlan->completion_percentage
                    ],
                    'doctor' => $carePlan->doctor ? [
                        'id' => $carePlan->doctor->id,
                        'name' => $carePlan->doctor->first_name . ' ' . $carePlan->doctor->last_name,
                        'specialization' => $carePlan->doctor->specialization ?? 'General Practice'
                    ] : null,
                    'vitals' => $vitals,
                    'recentNotes' => $recentNotes,
                    'schedules' => $carePlan->schedules->map(function($schedule) {
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
                    })
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
         * Create a progress note for a patient
         * POST /api/nurse/patients/{patientId}/progress-notes
         */
        public function createProgressNote(Request $request, $patientId): JsonResponse
        {
            try {
                $nurse = auth()->user();
                
                // Verify the user is a nurse
                if ($nurse->role !== 'nurse') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only nurses can create progress notes.'
                    ], 403);
                }

                // Verify patient exists and is a patient
                $patient = User::where('id', $patientId)
                    ->where('role', 'patient')
                    ->first();

                if (!$patient) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Patient not found.'
                    ], 404);
                }

                // Check if nurse has access to this patient (assigned via care plan)
                $hasAccess = CarePlan::where('patient_id', $patientId)
                    ->where(function($query) use ($nurse) {
                        $query->where('primary_nurse_id', $nurse->id)
                            ->orWhere('secondary_nurse_id', $nurse->id);
                    })
                    ->where('status', 'active')
                    ->exists();

                if (!$hasAccess) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are not assigned to this patient.'
                    ], 403);
                }

                // Validate the request
                $validator = Validator::make($request->all(), [
                    'visit_date' => 'required|date',
                    'visit_time' => 'required|date_format:H:i',
                    'vitals' => 'nullable|array',
                    'vitals.temperature' => 'nullable|numeric|min:0|max:50',
                    'vitals.pulse' => 'nullable|integer|min:0|max:300',
                    'vitals.respiration' => 'nullable|integer|min:0|max:100',
                    'vitals.blood_pressure' => 'nullable|string|max:20',
                    'vitals.spo2' => 'nullable|integer|min:0|max:100',
                    'interventions' => 'nullable|array',
                    'interventions.medication_administered' => 'nullable|boolean',
                    'interventions.medication_details' => 'nullable|string',
                    'interventions.wound_care' => 'nullable|boolean',
                    'interventions.wound_care_details' => 'nullable|string',
                    'interventions.physiotherapy' => 'nullable|boolean',
                    'interventions.physiotherapy_details' => 'nullable|string',
                    'interventions.nutrition_support' => 'nullable|boolean',
                    'interventions.nutrition_details' => 'nullable|string',
                    'interventions.hygiene_care' => 'nullable|boolean',
                    'interventions.hygiene_details' => 'nullable|string',
                    'interventions.counseling' => 'nullable|boolean',
                    'interventions.counseling_details' => 'nullable|string',
                    'interventions.other_interventions' => 'nullable|boolean',
                    'interventions.other_details' => 'nullable|string',
                    'general_condition' => 'required|in:Stable,Improving,Declining,Critical',
                    'pain_level' => 'required|integer|min:0|max:10',
                    'wound_status' => 'nullable|string',
                    'other_observations' => 'nullable|string',
                    'education_provided' => 'nullable|string',
                    'family_concerns' => 'nullable|string',
                    'next_steps' => 'nullable|string',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed.',
                        'errors' => $validator->errors()
                    ], 422);
                }

                // Check for duplicate note on same date/time
                $existingNote = \App\Models\ProgressNote::where([
                    'patient_id' => $patientId,
                    'nurse_id' => $nurse->id,
                    'visit_date' => $request->visit_date,
                    'visit_time' => $request->visit_time
                ])->first();

                if ($existingNote) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A progress note already exists for this date and time. Please edit the existing note or choose a different time.'
                    ], 422);
                }

                // Map general_condition to database enum values
                $conditionMap = [
                    'Stable' => 'stable',
                    'Improving' => 'improved',
                    'Declining' => 'deteriorating',
                    'Critical' => 'deteriorating'
                ];

                // Create the progress note
                $progressNote = \App\Models\ProgressNote::create([
                    'patient_id' => $patientId,
                    'nurse_id' => $nurse->id,
                    'visit_date' => $request->visit_date,
                    'visit_time' => $request->visit_time,
                    'vitals' => $request->vitals,
                    'interventions' => $request->interventions,
                    'general_condition' => $conditionMap[$request->general_condition] ?? 'stable',
                    'pain_level' => $request->pain_level,
                    'wound_status' => $request->wound_status,
                    'other_observations' => $request->other_observations,
                    'education_provided' => $request->education_provided,
                    'family_concerns' => $request->family_concerns,
                    'next_steps' => $request->next_steps,
                    'signed_at' => now(),
                    'signature_method' => 'digital'
                ]);

                // Load relationships for response
                $progressNote->load(['patient:id,first_name,last_name', 'nurse:id,first_name,last_name']);

                return response()->json([
                    'success' => true,
                    'message' => 'Progress note created successfully.',
                    'data' => [
                        'id' => $progressNote->id,
                        'patient' => [
                            'id' => $progressNote->patient->id,
                            'name' => $progressNote->patient->first_name . ' ' . $progressNote->patient->last_name
                        ],
                        'nurse' => [
                            'id' => $progressNote->nurse->id,
                            'name' => $progressNote->nurse->first_name . ' ' . $progressNote->nurse->last_name
                        ],
                        'visit_date' => $progressNote->visit_date,
                        'visit_time' => $progressNote->visit_time,
                        'general_condition' => $progressNote->general_condition,
                        'pain_level' => $progressNote->pain_level,
                        'created_at' => $progressNote->created_at->toIso8601String()
                    ]
                ], 201);

            } catch (\Exception $e) {
                \Log::error('Error creating progress note: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create progress note. Please try again.',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
        }

        /**
         * Get schedules for the authenticated nurse
         * Used for the nurse's schedule screen
         */
        public function nurseSchedules(Request $request): JsonResponse
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

                $query = Schedule::where('nurse_id', $nurse->id)
                    ->with([
                        'carePlan.patient:id,first_name,last_name,date_of_birth,avatar',
                        'carePlan:id,patient_id,title,care_type,priority'
                    ])
                    ->orderBy('schedule_date', 'desc')
                    ->orderBy('start_time', 'desc');

                // Apply filters
                // Status filter
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

                // Shift type filter
                if ($request->has('shift_type') && $request->shift_type !== 'all') {
                    $shiftTypeMap = [
                        'Morning Shifts' => 'morning',
                        'Afternoon Shifts' => 'afternoon',
                        'Night Shifts' => 'night',
                        'Evening Shifts' => 'evening'
                    ];
                    
                    if (isset($shiftTypeMap[$request->shift_type])) {
                        $query->where('shift_type', $shiftTypeMap[$request->shift_type]);
                    }
                }

                // Date range filter - IMPORTANT: Filter by specific date
                if ($request->has('start_date') && $request->has('end_date')) {
                    // If start_date and end_date are the same, filter for that specific day
                    if ($request->start_date === $request->end_date) {
                        $query->whereDate('schedule_date', $request->start_date);
                    } else {
                        // Otherwise, filter by date range
                        $query->whereBetween('schedule_date', [$request->start_date, $request->end_date]);
                    }
                } elseif ($request->has('start_date')) {
                    $query->whereDate('schedule_date', '>=', $request->start_date);
                } elseif ($request->has('end_date')) {
                    $query->whereDate('schedule_date', '<=', $request->end_date);
                }

                // Search filter
                if ($request->has('search') && !empty($request->search)) {
                    $search = $request->search;
                    $query->where(function($q) use ($search) {
                        $q->where('location', 'like', "%{$search}%")
                        ->orWhere('shift_notes', 'like', "%{$search}%")
                        ->orWhereHas('carePlan.patient', function($pq) use ($search) {
                            $pq->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    });
                }

                $schedules = $query->get();

                // Transform data for mobile app
                $transformedSchedules = $schedules->map(function($schedule) {
                    $patient = $schedule->carePlan?->patient;
                    $carePlan = $schedule->carePlan;

                    // Calculate patient age
                    $age = null;
                    if ($patient && $patient->date_of_birth) {
                        $age = \Carbon\Carbon::parse($patient->date_of_birth)->age;
                    }

                    // Get care type label
                    $careTypes = CarePlan::getCareTypes();
                    $careTypeLabel = $careTypes[$carePlan?->care_type ?? 'general_care'] 
                        ?? ucfirst(str_replace('_', ' ', $carePlan?->care_type ?? 'General Care'));

                    // Map shift type
                    $shiftTypeMap = [
                        'morning' => 'Morning',
                        'afternoon' => 'Afternoon',
                        'evening' => 'Evening',
                        'night' => 'Night'
                    ];
                    $shiftType = $shiftTypeMap[$schedule->shift_type] ?? 'Morning';

                    // Determine if completed
                    $isCompleted = $schedule->status === 'completed';

                    return [
                        'id' => $schedule->id,
                        'patientName' => $patient 
                            ? $patient->first_name . ' ' . $patient->last_name 
                            : 'Unknown Patient',
                        'patientAge' => $age,
                        'date' => $schedule->schedule_date->toIso8601String(),
                        'startTime' => \Carbon\Carbon::parse($schedule->start_time)->format('h:i A'),
                        'endTime' => \Carbon\Carbon::parse($schedule->end_time)->format('h:i A'),
                        'shiftType' => $shiftType,
                        'location' => $schedule->location ?? 'Location not specified',
                        'careType' => $careTypeLabel,
                        'status' => $schedule->status,
                        'notes' => $schedule->shift_notes ?? '',
                        'isCompleted' => $isCompleted,
                        'priority' => $carePlan?->priority ?? 'medium',
                        'carePlanId' => $carePlan?->id,
                        'carePlanTitle' => $carePlan?->title ?? 'Care Plan',
                        'actualStartTime' => $schedule->actual_start_time 
                            ? $schedule->actual_start_time->toIso8601String() 
                            : null,
                        'actualEndTime' => $schedule->actual_end_time 
                            ? $schedule->actual_end_time->toIso8601String() 
                            : null,
                        'confirmedAt' => $schedule->nurse_confirmed_at 
                            ? $schedule->nurse_confirmed_at->toIso8601String() 
                            : null,
                    ];
                });

                // Get counts for different statuses
                // Note: These counts are for the FILTERED schedules (by date), not overall
                $filteredSchedulesForCounts = $transformedSchedules;
                
                $upcomingCount = $filteredSchedulesForCounts->filter(function($schedule) {
                    return !$schedule['isCompleted'] && 
                        in_array($schedule['status'], ['scheduled', 'confirmed', 'in_progress']);
                })->count();

                $completedCount = $filteredSchedulesForCounts->filter(function($schedule) {
                    return $schedule['isCompleted'];
                })->count();

                $allCount = $filteredSchedulesForCounts->count();

                return response()->json([
                    'success' => true,
                    'data' => $transformedSchedules,
                    'counts' => [
                        'upcoming' => $upcomingCount,
                        'completed' => $completedCount,
                        'all' => $allCount
                    ],
                    'message' => $transformedSchedules->isEmpty() 
                        ? 'No schedules found.' 
                        : null
                ]);

            } catch (\Exception $e) {
                \Log::error('Error fetching nurse schedules: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch schedules. Please try again.',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
        }

        /**
         * Get care plans assigned to the authenticated nurse
         * Used for the nurse's care plans screen
         */
        public function nurseCarePlans(Request $request): JsonResponse
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

                // Get all care plans where this nurse is assigned (primary or secondary)
                $carePlansQuery = CarePlan::where(function($query) use ($nurse) {
                        $query->where('primary_nurse_id', $nurse->id)
                            ->orWhere('secondary_nurse_id', $nurse->id);
                    })
                    ->with([
                        'patient:id,first_name,last_name,date_of_birth,avatar',
                        'doctor:id,first_name,last_name,specialization',
                        'primaryNurse:id,first_name,last_name',
                        'secondaryNurse:id,first_name,last_name'
                    ]);

                // Apply search filter
                if ($request->has('search') && !empty($request->search)) {
                    $search = $request->search;
                    $carePlansQuery->where(function($query) use ($search) {
                        $query->where('title', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhereHas('patient', function($q) use ($search) {
                                $q->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                            });
                    });
                }

                // Apply status filter
                if ($request->has('status') && $request->status !== 'all') {
                    $carePlansQuery->where('status', $request->status);
                }

                $carePlans = $carePlansQuery->orderBy('created_at', 'desc')->get();

                // Transform the data for the mobile app
                $transformedPlans = $carePlans->map(function($carePlan) use ($nurse) {
                    $patient = $carePlan->patient;
                    $doctor = $carePlan->doctor;
                    
                    // Calculate age
                    $age = $patient && $patient->date_of_birth ? 
                        \Carbon\Carbon::parse($patient->date_of_birth)->age : 
                        null;

                    // Get care type label
                    $careTypes = CarePlan::getCareTypes();
                    $careTypeLabel = $careTypes[$carePlan->care_type] ?? ucfirst(str_replace('_', ' ', $carePlan->care_type));

                    // Get priority
                    $priorityMap = [
                        'high' => 'High',
                        'medium' => 'Medium',
                        'low' => 'Low'
                    ];
                    $priority = $priorityMap[$carePlan->priority] ?? 'Medium';

                    // Get status
                    $statusMap = [
                        'draft' => 'Draft',
                        'pending_approval' => 'Pending Approval',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled'
                    ];
                    $status = $statusMap[$carePlan->status] ?? ucfirst($carePlan->status);

                return [
                    'id' => $carePlan->id,                
                    'patient' => $patient ? $patient->first_name . ' ' . $patient->last_name : 'Unknown Patient',
                    'patient_id' => $patient ? $patient->id : null,
                    'care_plan' => $carePlan->title,
                    'description' => $carePlan->description ?? '',
                    'doctor' => $doctor ? $doctor->first_name . ' ' . $doctor->last_name : 'Not assigned',
                    'doctor_id' => $doctor ? $doctor->id : null,
                    'doctor_specialty' => $doctor->specialization ?? 'General',
                    'primary_nurse' => $carePlan->primaryNurse ?
                        $carePlan->primaryNurse->first_name . ' ' . $carePlan->primaryNurse->last_name :
                        'Not assigned',
                    'nurse_experience' => $carePlan->primaryNurse && $carePlan->primaryNurse->years_experience ?
                        $carePlan->primaryNurse->years_experience . 'y exp' :
                        'N/A',
                    'care_type' => $careTypeLabel,
                    'status' => $status,
                    'priority' => $priority,
                    'frequency' => $carePlan->frequency ?? 'Daily',
                    'progress' => $carePlan->completion_percentage / 100.0,
                    'estimated_hours' => $carePlan->estimated_hours_per_day ?? 4,
                    'start_date' => $carePlan->start_date ?
                        \Carbon\Carbon::parse($carePlan->start_date)->format('Y-m-d') :
                        null,
                    'end_date' => $carePlan->end_date ?
                        \Carbon\Carbon::parse($carePlan->end_date)->format('Y-m-d') :
                        null,
                    'care_tasks' => $carePlan->care_tasks ?? [],
                    'medications' => $carePlan->medications ?? [],
                    'special_instructions' => $carePlan->special_instructions ?? [],
                ];
                })->values();

                return response()->json([
                    'success' => true,
                    'data' => $transformedPlans,
                    'total' => $transformedPlans->count(),
                    'message' => $transformedPlans->isEmpty() ? 'No care plans assigned yet.' : null
                ]);

            } catch (\Exception $e) {
                \Log::error('Error fetching nurse care plans: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch care plans. Please try again.',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
        }

        /**
         * Helper method to count completed tasks
         */
        private function countCompletedTasks($carePlan): int
        {
            // This is a simplified calculation
            // You might want to track this more accurately in your database
            $completionPercentage = $carePlan->completion_percentage ?? 0;
            $totalTasks = is_array($carePlan->care_tasks) ? count($carePlan->care_tasks) : 0;
            
            if ($totalTasks === 0) return 0;
            
            return (int) round(($completionPercentage / 100) * $totalTasks);
        }

        /**
         * Create a new care plan (for nurses to submit)
         * POST /api/nurse/care-plans
         */
        public function createCarePlan(Request $request): JsonResponse
        {
            try {

                $nurse = auth()->user();
                
                // Verify the user is a nurse
                if ($nurse->role !== 'nurse') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only nurses can create care plans through this endpoint.'
                    ], 403);
                }

                $validated = $request->validate([
                    'patient_id' => 'required|exists:users,id',
                    'doctor_id' => 'required|exists:users,id',
                    'title' => 'required|string|max:255',
                    'description' => 'required|string',
                    'care_type' => 'required|string|in:general_care,elderly_care,post_surgery_care,pediatric_care,chronic_disease_management,palliative_care,rehabilitation_care',
                    'priority' => 'required|string|in:low,medium,high',
                    'start_date' => 'required|date|after_or_equal:today',
                    'end_date' => 'nullable|date|after:start_date',
                    'frequency' => 'required|string|in:daily,weekly,bi-weekly,monthly,as_needed',
                    'care_tasks' => 'required|array|min:1',
                    'care_tasks.*' => 'required|string',
                ]);

                // Verify patient role
                $patient = User::findOrFail($validated['patient_id']);
                if ($patient->role !== 'patient') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected user is not a patient.',
                    ], 422);
                }

                // Verify doctor role
                $doctor = User::findOrFail($validated['doctor_id']);
                if (!in_array($doctor->role, ['doctor', 'admin', 'superadmin'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected user cannot be assigned as doctor.',
                    ], 422);
                }

                // Map care type from display format to database format
                $careTypeMap = [
                    'General Care' => 'general_care',
                    'Elderly Care' => 'elderly_care',
                    'Post-Surgery Care' => 'post_surgery_care',
                    'Pediatric Care' => 'pediatric_care',
                    'Chronic Disease Management' => 'chronic_disease_management',
                    'Palliative Care' => 'palliative_care',
                    'Rehabilitation Care' => 'rehabilitation_care',
                ];

                // Map frequency
                $frequencyMap = [
                    'Daily' => 'daily',
                    'Weekly' => 'weekly',
                    'Bi-weekly' => 'bi-weekly',
                    'Monthly' => 'monthly',
                    'As Needed' => 'as_needed',
                ];

                // Map priority
                $priorityMap = [
                    'Low' => 'low',
                    'Medium' => 'medium',
                    'High' => 'high',
                ];

                $validated['created_by'] = $nurse->id;
                $validated['status'] = 'draft'; // Nurses create drafts that need approval
                $validated['primary_nurse_id'] = $nurse->id; // Auto-assign creating nurse
                $validated['nurse_assigned_at'] = now();
                
                // Convert mapped values
                $validated['care_type'] = $careTypeMap[$validated['care_type']] ?? $validated['care_type'];
                $validated['frequency'] = $frequencyMap[$validated['frequency']] ?? $validated['frequency'];
                $validated['priority'] = $priorityMap[$validated['priority']] ?? $validated['priority'];

                DB::beginTransaction();

                try {
                    $carePlan = CarePlan::create($validated);
                    $carePlan->load(['patient', 'doctor', 'primaryNurse', 'createdBy']);

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Care plan created successfully and submitted for approval.',
                        'data' => $carePlan
                    ], 201);
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            } catch (\Illuminate\Validation\ValidationException $e) {
                        \Log::info("Failed Creating Care Plan");
                        \Log::info($e->errors());

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors()
                ], 422);
            } catch (\Exception $e) {
                \Log::error('Error creating care plan: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create care plan. Please try again.',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
        }

        public function updateCarePlan(Request $request, $id): JsonResponse
        {
            try {
                $nurse = auth()->user();
                
                // Verify the user is a nurse
                if ($nurse->role !== 'nurse') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only nurses can update care plans through this endpoint.'
                    ], 403);
                }

                // Find the care plan
                $carePlan = CarePlan::findOrFail($id);

                // Verify nurse has permission to edit this care plan
                if ($carePlan->primary_nurse_id !== $nurse->id && $carePlan->created_by !== $nurse->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to edit this care plan.'
                    ], 403);
                }

                $validated = $request->validate([
                    'patient_id' => 'required|exists:users,id',
                    'doctor_id' => 'required|exists:users,id',
                    'title' => 'required|string|max:255',
                    'description' => 'required|string',
                    'care_type' => 'required|string|in:general_care,elderly_care,post_surgery_care,pediatric_care,chronic_disease_management,palliative_care,rehabilitation_care',
                    'priority' => 'required|string|in:low,medium,high',
                    'start_date' => 'required|date',
                    'end_date' => 'nullable|date|after:start_date',
                    'frequency' => 'required|string|in:daily,weekly,bi-weekly,monthly,as_needed',
                    'care_tasks' => 'required|array|min:1',
                    'care_tasks.*' => 'required|string',
                ]);

                // Verify patient role
                $patient = User::findOrFail($validated['patient_id']);
                if ($patient->role !== 'patient') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected user is not a patient.',
                    ], 422);
                }

                // Verify doctor role
                $doctor = User::findOrFail($validated['doctor_id']);
                if (!in_array($doctor->role, ['doctor', 'admin', 'superadmin'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected user cannot be assigned as doctor.',
                    ], 422);
                }

                // Map care type from display format to database format
                $careTypeMap = [
                    'General Care' => 'general_care',
                    'Elderly Care' => 'elderly_care',
                    'Post-Surgery Care' => 'post_surgery_care',
                    'Pediatric Care' => 'pediatric_care',
                    'Chronic Disease Management' => 'chronic_disease_management',
                    'Palliative Care' => 'palliative_care',
                    'Rehabilitation Care' => 'rehabilitation_care',
                ];

                // Map frequency
                $frequencyMap = [
                    'Daily' => 'daily',
                    'Weekly' => 'weekly',
                    'Bi-weekly' => 'bi-weekly',
                    'Monthly' => 'monthly',
                    'As Needed' => 'as_needed',
                ];

                // Map priority
                $priorityMap = [
                    'Low' => 'low',
                    'Medium' => 'medium',
                    'High' => 'high',
                ];

                // Convert mapped values
                $validated['care_type'] = $careTypeMap[$validated['care_type']] ?? $validated['care_type'];
                $validated['frequency'] = $frequencyMap[$validated['frequency']] ?? $validated['frequency'];
                $validated['priority'] = $priorityMap[$validated['priority']] ?? $validated['priority'];

                // Track who updated it
                $validated['updated_by'] = $nurse->id;

                DB::beginTransaction();

                try {
                    $carePlan->update($validated);
                    $carePlan->load(['patient', 'doctor', 'primaryNurse', 'createdBy']);

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Care plan updated successfully.',
                        'data' => $carePlan
                    ], 200);
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            } catch (\Illuminate\Validation\ValidationException $e) {
                \Log::info("Failed Updating Care Plan");
                \Log::info($e->errors());

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors()
                ], 422);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care plan not found.',
                ], 404);
            } catch (\Exception $e) {
                \Log::error('Error updating care plan: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update care plan. Please try again.',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
        }
        
        /**
     * Clock in for a schedule
     * POST /api/nurse/schedules/{scheduleId}/clock-in
     */
    public function clockInSchedule(Request $request, $scheduleId): JsonResponse
    {
        try {
            $nurse = auth()->user();
            
            if ($nurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can clock in.'
                ], 403);
            }

            $schedule = Schedule::with(['carePlan.patient'])->find($scheduleId);

            if (!$schedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Schedule not found.'
                ], 404);
            }

            if ($schedule->nurse_id !== $nurse->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to this schedule.'
                ], 403);
            }

            $activeSession = \App\Models\TimeTracking::where('nurse_id', $nurse->id)
                ->whereNull('end_time')
                ->whereIn('status', ['active', 'paused'])
                ->first();

            if ($activeSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have an active time tracking session. Please clock out first.',
                    'active_session' => [
                        'id' => $activeSession->id,
                        'schedule_id' => $activeSession->schedule_id,
                        'start_time' => $activeSession->start_time,
                        'status' => $activeSession->status
                    ]
                ], 422);
            }

            $validated = $request->validate([
                'location' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'device_info' => 'nullable|string|max:255',
            ]);

            DB::beginTransaction();

            try {
                $timeTracking = \App\Models\TimeTracking::create([
                    'nurse_id' => $nurse->id,
                    'schedule_id' => $scheduleId,
                    'patient_id' => $schedule->carePlan?->patient_id,
                    'care_plan_id' => $schedule->care_plan_id,
                    'session_type' => 'scheduled_shift',
                    'start_time' => now(),
                    'clock_in_location' => $validated['location'] ?? null,
                    'clock_in_latitude' => $validated['latitude'] ?? null,
                    'clock_in_longitude' => $validated['longitude'] ?? null,
                    'clock_in_ip' => $request->ip(),
                    'device_info' => $validated['device_info'] ?? $request->header('User-Agent'),
                    'status' => 'active',
                    'total_duration_minutes' => 0,
                    'total_pause_duration_minutes' => 0,
                    'break_count' => 0,
                    'total_break_minutes' => 0,
                    'requires_approval' => 0
                ]);

                $schedule->update([
                    'status' => 'confirmed',
                    'actual_start_time' => $timeTracking->start_time
                ]);

                DB::commit();

                $timeTracking->load([
                    'nurse:id,first_name,last_name',
                    'patient:id,first_name,last_name',
                    'schedule.carePlan:id,title,care_type',
                    'carePlan:id,title,care_type'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Clocked in successfully.',
                    'data' => [
                        'id' => $timeTracking->id,
                        'schedule_id' => $timeTracking->schedule_id,
                        'patient_name' => $timeTracking->patient 
                            ? $timeTracking->patient->first_name . ' ' . $timeTracking->patient->last_name 
                            : null,
                        'care_plan_title' => $timeTracking->carePlan?->title ?? $timeTracking->schedule?->carePlan?->title,
                        'start_time' => $timeTracking->start_time->toIso8601String(),
                        'status' => $timeTracking->status,
                        'elapsed_seconds' => 0,
                        'location' => $timeTracking->clock_in_location,
                        'latitude' => $timeTracking->clock_in_latitude,
                        'longitude' => $timeTracking->clock_in_longitude,
                        'session_type' => $timeTracking->session_type
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error clocking in for schedule: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to clock in. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Clock out from active session
     * POST /api/nurse/time-tracking/clock-out
     */
    public function clockOut(Request $request): JsonResponse
    {
        try {
            $nurse = auth()->user();
            
            if ($nurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can clock out.'
                ], 403);
            }

            $activeSession = \App\Models\TimeTracking::where('nurse_id', $nurse->id)
                ->whereNull('end_time')
                ->where('status', 'active')
                ->first();

            if (!$activeSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active session found to clock out.'
                ], 422);
            }

            $validated = $request->validate([
                'location' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'work_notes' => 'nullable|string|max:1000',
                'activities_performed' => 'nullable|array',
                'activities_performed.*' => 'string'
            ]);

            DB::beginTransaction();

            try {
                $endTime = now();
                $startTime = $activeSession->start_time;
                
                $totalDurationMinutes = $startTime->diffInMinutes($endTime);
                $pauseDurationMinutes = $activeSession->total_pause_duration_minutes ?? 0;
                $workingDurationMinutes = $totalDurationMinutes - $pauseDurationMinutes;

                $activeSession->update([
                    'end_time' => $endTime,
                    'clock_out_location' => $validated['location'] ?? null,
                    'clock_out_latitude' => $validated['latitude'] ?? null,
                    'clock_out_longitude' => $validated['longitude'] ?? null,
                    'clock_out_ip' => $request->ip(),
                    'work_notes' => $validated['work_notes'] ?? null,
                    'activities_performed' => $validated['activities_performed'] ?? null,
                    'total_duration_minutes' => $totalDurationMinutes,
                    'status' => 'completed'
                ]);

                if ($activeSession->schedule) {
                    $activeSession->schedule->update([
                        'status' => 'completed',
                        'actual_end_time' => $endTime
                    ]);
                }

                DB::commit();

                $activeSession->load([
                    'nurse:id,first_name,last_name',
                    'patient:id,first_name,last_name',
                    'schedule.carePlan:id,title',
                    'carePlan:id,title'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Clocked out successfully.',
                    'data' => [
                        'id' => $activeSession->id,
                        'schedule_id' => $activeSession->schedule_id,
                        'patient_name' => $activeSession->patient 
                            ? $activeSession->patient->first_name . ' ' . $activeSession->patient->last_name 
                            : null,
                        'start_time' => $activeSession->start_time->toIso8601String(),
                        'end_time' => $activeSession->end_time->toIso8601String(),
                        'total_duration_minutes' => $activeSession->total_duration_minutes,
                        'working_duration_minutes' => $workingDurationMinutes,
                        'pause_duration_minutes' => $pauseDurationMinutes,
                        'formatted_duration' => $this->formatDuration($workingDurationMinutes),
                        'status' => $activeSession->status,
                        'work_notes' => $activeSession->work_notes
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error clocking out: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to clock out. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get active time tracking session
     * GET /api/nurse/time-tracking/active
     */
    public function getActiveSession(): JsonResponse
    {
        try {
            $nurse = auth()->user();
            
            if ($nurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can access this endpoint.'
                ], 403);
            }

            $activeSession = \App\Models\TimeTracking::where('nurse_id', $nurse->id)
                ->whereNull('end_time')
                ->whereIn('status', ['active', 'paused'])
                ->with([
                    'patient:id,first_name,last_name,avatar',
                    'schedule:id,care_plan_id,schedule_date,start_time,end_time,location',
                    'schedule.carePlan:id,title,care_type,priority',
                    'carePlan:id,title,care_type,priority'
                ])
                ->first();

            if (!$activeSession) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'No active session found.'
                ]);
            }

            $startTime = $activeSession->start_time;
            $currentTime = now();
            $elapsedSeconds = $startTime->diffInSeconds($currentTime);
            
            if ($activeSession->status === 'paused' && $activeSession->paused_at) {
                $pauseSeconds = Carbon::parse($activeSession->paused_at)->diffInSeconds($currentTime);
                $elapsedSeconds -= $pauseSeconds;
            }

            $carePlan = $activeSession->carePlan ?? $activeSession->schedule?->carePlan;

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $activeSession->id,
                    'schedule_id' => $activeSession->schedule_id,
                    'patient' => $activeSession->patient ? [
                        'id' => $activeSession->patient->id,
                        'name' => $activeSession->patient->first_name . ' ' . $activeSession->patient->last_name,
                        'avatar' => $activeSession->patient->avatar
                    ] : null,
                    'care_plan' => $carePlan ? [
                        'id' => $carePlan->id,
                        'title' => $carePlan->title,
                        'care_type' => $carePlan->care_type,
                        'priority' => $carePlan->priority
                    ] : null,
                    'schedule' => $activeSession->schedule ? [
                        'id' => $activeSession->schedule->id,
                        'date' => $activeSession->schedule->schedule_date,
                        'start_time' => $activeSession->schedule->start_time,
                        'end_time' => $activeSession->schedule->end_time,
                        'location' => $activeSession->schedule->location
                    ] : null,
                    'start_time' => $activeSession->start_time->toIso8601String(),
                    'elapsed_seconds' => max(0, $elapsedSeconds),
                    'formatted_duration' => $this->formatDuration((int)($elapsedSeconds / 60)),
                    'status' => $activeSession->status,
                    'session_type' => $activeSession->session_type,
                    'location' => $activeSession->clock_in_location,
                    'break_count' => $activeSession->break_count ?? 0,
                    'total_break_minutes' => $activeSession->total_break_minutes ?? 0,
                    'total_pause_duration_minutes' => $activeSession->total_pause_duration_minutes ?? 0,
                    'pause_reason' => $activeSession->pause_reason,
                    'activities_performed' => $activeSession->activities_performed ?? []
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching active session: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active session. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Pause active time tracking session
     * POST /api/nurse/time-tracking/pause
     */
    public function pauseSession(Request $request): JsonResponse
    {
        try {
            $nurse = auth()->user();
            
            if ($nurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can pause sessions.'
                ], 403);
            }

            $validated = $request->validate([
                'reason' => 'nullable|string|max:255'
            ]);

            $activeSession = \App\Models\TimeTracking::where('nurse_id', $nurse->id)
                ->whereNull('end_time')
                ->where('status', 'active')
                ->first();

            if (!$activeSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active session found to pause.'
                ], 422);
            }

            $activeSession->update([
                'status' => 'paused',
                'paused_at' => now(),
                'pause_reason' => $validated['reason'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Session paused successfully.',
                'data' => [
                    'id' => $activeSession->id,
                    'status' => $activeSession->status,
                    'paused_at' => $activeSession->paused_at->toIso8601String(),
                    'pause_reason' => $activeSession->pause_reason
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error pausing session: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to pause session.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Resume paused time tracking session
     * POST /api/nurse/time-tracking/resume
     */
    public function resumeSession(): JsonResponse
    {
        try {
            $nurse = auth()->user();
            
            if ($nurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can resume sessions.'
                ], 403);
            }

            $pausedSession = \App\Models\TimeTracking::where('nurse_id', $nurse->id)
                ->whereNull('end_time')
                ->where('status', 'paused')
                ->first();

            if (!$pausedSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'No paused session found to resume.'
                ], 422);
            }

            if ($pausedSession->paused_at) {
                $pauseDuration = Carbon::parse($pausedSession->paused_at)->diffInMinutes(now());
                $totalPauseDuration = ($pausedSession->total_pause_duration_minutes ?? 0) + $pauseDuration;
                
                $pausedSession->update([
                    'status' => 'active',
                    'total_pause_duration_minutes' => $totalPauseDuration,
                    'paused_at' => null,
                    'pause_reason' => null
                ]);
            } else {
                $pausedSession->update([
                    'status' => 'active',
                    'paused_at' => null,
                    'pause_reason' => null
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Session resumed successfully.',
                'data' => [
                    'id' => $pausedSession->id,
                    'status' => $pausedSession->status,
                    'total_pause_duration_minutes' => $pausedSession->total_pause_duration_minutes
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error resuming session: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to resume session.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Helper method to format duration in minutes to HH:MM:SS
     */
    private function formatDuration(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d:00', $hours, $mins);
    }

    /**
     * Create a new medical assessment (Nurse Mobile)
     * POST /api/nurse/medical-assessments
     * 
     * Add this method to your existing PatientController class
     */
    public function createMedicalAssessment(Request $request): JsonResponse
    {
        try {
            $nurse = auth()->user();
            
            // Verify the user is a nurse
            if ($nurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can create medical assessments.'
                ], 403);
            }

            // Validate the request
            $validator = Validator::make(
                $request->all(),
                MedicalAssessment::validationRules(),
                MedicalAssessment::validationMessages()
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate nurse
            $nurseUser = User::find($request->nurse_id);
            if (!$nurseUser || !in_array($nurseUser->role, ['nurse', 'admin', 'superadmin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid nurse selected or insufficient permissions.'
                ], 422);
            }

            // Nurses can only create assessments for themselves
            if ($nurse->role === 'nurse' && $nurse->id !== $request->nurse_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only create assessments for yourself.',
                ], 403);
            }

            DB::beginTransaction();

            try {
                $patientId = $request->patient_id;
                $isPatientNew = $request->is_new_patient;

                // Create new patient if patient_id is not provided
                if ($isPatientNew) {
                    $patient = $this->createPatient($request);
                    $patientId = $patient->id;
                } else {
                    // Validate existing patient
                    $patient = User::find($patientId);
                    if (!$patient || $patient->role !== 'patient') {
                        throw new \Exception('Invalid patient selected.');
                    }
                }

                // Create the medical assessment
                $assessmentData = $request->only([
                    'physical_address',
                    'occupation',
                    'religion',
                    'emergency_contact_1_name',
                    'emergency_contact_1_relationship',
                    'emergency_contact_1_phone',
                    'emergency_contact_2_name',
                    'emergency_contact_2_relationship',
                    'emergency_contact_2_phone',
                    'presenting_condition',
                    'past_medical_history',
                    'allergies',
                    'current_medications',
                    'special_needs',
                    'general_condition',
                    'hydration_status',
                    'nutrition_status',
                    'mobility_status',
                    'has_wounds',
                    'wound_description',
                    'pain_level',
                    'initial_vitals',
                    'initial_nursing_impression'
                ]);

                $assessmentData['patient_id'] = $patientId;
                $assessmentData['nurse_id'] = $request->nurse_id;
                $assessmentData['assessment_status'] = 'completed';

                $assessment = MedicalAssessment::create($assessmentData);

                // Load relationships for response
                $assessment->load(['patient', 'nurse']);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Medical assessment created successfully.',
                    'data' => $assessment
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error creating medical assessment: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create medical assessment.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Helper method to create a new patient user (already exists in MedicalAssessmentController)
     * Make sure this method is available in your PatientController
     */
    private function createPatient(Request $request): User
    {
        // Generate email and password for the patient
        $firstName = $request->patient_first_name;
        $lastName = $request->patient_last_name;
        $email = $this->generatePatientEmail($firstName, $lastName);
        $temporaryPassword = Str::random(12);

        $patientData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => Hash::make($temporaryPassword),
            'phone' => $request->patient_phone,
            'role' => 'patient',
            'date_of_birth' => $request->patient_date_of_birth,
            'gender' => $request->patient_gender,
            'ghana_card_number' => $request->patient_ghana_card,
            'is_active' => true,
            'is_verified' => true,
            'verification_status' => 'verified',
            'verified_by' => $request->nurse_id,
            'verified_at' => now(),
        ];

        $patient = User::create($patientData);

        try {
            // Pass the User model instance, not the array
            Mail::to($email)->send(new UserInvitationMail($patient, $temporaryPassword));
        } catch (\Exception $e) {
            \Log::error('Failed to send invitation email: ' . $e->getMessage());
        }

        return $patient;
    }

    /**
     * Generate a unique email for the patient (already exists in MedicalAssessmentController)
     * Make sure this method is available in your PatientController
     */
    private function generatePatientEmail(string $firstName, string $lastName): string
    {
        $baseEmail = strtolower($firstName . '.' . $lastName);
        $domain = '@patient.judyhomecare.com'; // Use your domain
        
        $email = $baseEmail . $domain;
        $counter = 1;
        
        // Ensure email is unique
        while (User::where('email', $email)->exists()) {
            $email = $baseEmail . $counter . $domain;
            $counter++;
        }
        
        return $email;
    }


     /**
     * Get all incident reports with filters and pagination
     */
    public function getIncidents(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 20);
            
            $query = IncidentReport::with(['patient', 'reporter'])
                ->orderBy('incident_date', 'desc')
                ->orderBy('incident_time', 'desc');
            
            // Apply filters
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            
            if ($request->has('patient_id')) {
                $query->where('patient_id', $request->patient_id);
            }
            
            // Paginate
            $incidents = $query->paginate($perPage);
            
            // Transform data
            $data = $incidents->map(function ($incident) {
                return $incident->toApiArray();
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Incident reports retrieved successfully',
                'data' => $data,
                'total' => $incidents->total(),
                'current_page' => $incidents->currentPage(),
                'last_page' => $incidents->lastPage(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve incident reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new incident report
     */
    public function createIncident(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                // Section 1: General Information
                'report_date' => 'required|date',
                'incident_date' => 'required|date|before_or_equal:today',
                'incident_time' => 'required|date_format:H:i',
                'incident_location' => 'nullable|string|max:255',
                'incident_type' => 'required|in:fall,medication_error,equipment_failure,injury,other',
                'incident_type_other' => 'required_if:incident_type,other|nullable|string|max:255',
                
                // Section 2: Person(s) Involved
                'patient_id' => 'required|exists:users,id',
                'patient_age' => 'nullable|integer|min:0|max:150',
                'patient_sex' => 'nullable|in:M,F',
                'client_id_case_no' => 'nullable|string|max:255',
                'staff_family_involved' => 'nullable|string|max:255',
                'staff_family_role' => 'nullable|in:nurse,family,other',
                'staff_family_role_other' => 'required_if:staff_family_role,other|nullable|string|max:255',
                
                // Section 3: Description
                'incident_description' => 'required|string|max:2000',
                
                // Section 4: Immediate Actions
                'first_aid_provided' => 'boolean',
                'first_aid_description' => 'required_if:first_aid_provided,true|nullable|string|max:1000',
                'care_provider_name' => 'nullable|string|max:255',
                'transferred_to_hospital' => 'boolean',
                'hospital_transfer_details' => 'required_if:transferred_to_hospital,true|nullable|string|max:1000',
                
                // Section 5: Witnesses
                'witness_names' => 'nullable|string|max:1000',
                'witness_contacts' => 'nullable|string|max:1000',
                
                // Section 6: Follow-Up
                'reported_to_supervisor' => 'nullable|string|max:255',
                'corrective_preventive_actions' => 'nullable|string|max:1000',
                
                // Additional tracking
                'severity' => 'nullable|in:low,medium,high,critical',
                'follow_up_required' => 'boolean',
                'follow_up_date' => 'nullable|date|after:today',
            ]);

            // Validate patient exists and is actually a patient
            $patient = User::where('id', $request->patient_id)
                          ->where('role', 'patient')
                          ->first();
            
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid patient selected'
                ], 422);
            }

            // Prepare incident data
            $incidentData = [
                'report_date' => $request->report_date,
                'incident_date' => $request->incident_date,
                'incident_time' => $request->incident_time,
                'incident_location' => $request->incident_location,
                'incident_type' => $request->incident_type,
                'incident_type_other' => $request->incident_type_other,
                'patient_id' => $request->patient_id,
                'patient_age' => $request->patient_age ?? $patient->age ?? null,
                'patient_sex' => $request->patient_sex ?? strtoupper(substr($patient->gender ?? 'M', 0, 1)),
                'client_id_case_no' => $request->client_id_case_no,
                'staff_family_involved' => $request->staff_family_involved,
                'staff_family_role' => $request->staff_family_role,
                'staff_family_role_other' => $request->staff_family_role_other,
                'incident_description' => $request->incident_description,
                'first_aid_provided' => $request->first_aid_provided ?? false,
                'first_aid_description' => $request->first_aid_description,
                'care_provider_name' => $request->care_provider_name,
                'transferred_to_hospital' => $request->transferred_to_hospital ?? false,
                'hospital_transfer_details' => $request->hospital_transfer_details,
                'witness_names' => $request->witness_names,
                'witness_contacts' => $request->witness_contacts,
                'reported_to_supervisor' => $request->reported_to_supervisor,
                'corrective_preventive_actions' => $request->corrective_preventive_actions,
                'reported_by' => auth()->id(),
                'reported_at' => now(),
                'status' => 'pending',
                'severity' => $request->severity ?? 'medium',
                'follow_up_required' => $request->follow_up_required ?? false,
                'follow_up_date' => $request->follow_up_date,
            ];

            // Create the incident report
            $incident = IncidentReport::create($incidentData);
            
            // Load fresh incident with relationships
            $newIncident = IncidentReport::with(['reporter', 'patient'])
                ->find($incident->id);
            
            return response()->json([
                'success' => true,
                'message' => 'Incident report created successfully',
                'data' => $newIncident->toApiArray()
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create incident report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get patients for incident dropdown
     */
    public function getPatientsForIncident(): JsonResponse
    {
        try {
            $patients = User::where('role', 'patient')
                ->where('is_active', true)
                ->select('id', 'first_name', 'last_name', 'date_of_birth', 'gender')
                ->orderBy('first_name')
                ->get()
                ->map(function ($patient) {
                    $age = $patient->date_of_birth 
                        ? \Carbon\Carbon::parse($patient->date_of_birth)->age 
                        : null;
                    
                    return [
                        'id' => $patient->id,
                        'name' => $patient->first_name . ' ' . $patient->last_name,
                        'age' => $age,
                        'gender' => $patient->gender,
                    ];
                });
            
            return response()->json([
                'success' => true,
                'message' => 'Patients retrieved successfully',
                'data' => $patients
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve patients',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get transport requests for the authenticated nurse
     * GET /api/nurse/transport-requests
     */
    public function nurseTransportRequests(Request $request): JsonResponse
    {
        try {
            $nurse = auth()->user();
            
            if ($nurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can access this endpoint.'
                ], 403);
            }

            $query = TransportRequest::with(['patient', 'requestedBy', 'driver.currentVehicle'])
                ->where('requested_by_id', $nurse->id);

            // Apply filters
            if ($request->filled('status')) {
                $query->byStatus($request->status);
            }

            if ($request->filled('priority')) {
                $query->byPriority($request->priority);
            }

            if ($request->filled('type')) {
                $query->byType($request->type);
            }

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Sort by scheduled time
            $query->orderBy('scheduled_time', 'desc');

            $transports = $query->paginate(20);

            // Transform data for mobile
            $transformedData = $transports->map(function($transport) use ($nurse) {
                $driver = $transport->driver;
                $vehicle = $driver ? $driver->currentVehicle : null;
                
                return [
                    'id' => $transport->id,
                    'patient_id' => $transport->patient_id,
                    'patient_name' => $transport->patient ? 
                        $transport->patient->first_name . ' ' . $transport->patient->last_name : 
                        'Unknown Patient',
                    'transport_type' => $transport->transport_type,
                    'priority' => $transport->priority,
                    'status' => $transport->status,
                    'scheduled_time' => $transport->scheduled_time ? 
                        $transport->scheduled_time->toIso8601String() : null,
                    'pickup_location' => $transport->pickup_location,
                    'pickup_address' => $transport->pickup_address,
                    'pickup_latitude' => $transport->pickup_latitude,
                    'pickup_longitude' => $transport->pickup_longitude,
                    'destination_location' => $transport->destination_location,
                    'destination_address' => $transport->destination_address,
                    'destination_latitude' => $transport->destination_latitude,
                    'destination_longitude' => $transport->destination_longitude,
                    'distance_km' => $transport->distance_km,
                    'estimated_duration_minutes' => $transport->estimated_duration_minutes,
                    'reason' => $transport->reason,
                    'special_requirements' => $transport->special_requirements,
                    'contact_person' => $transport->contact_person,
                    'driver' => $driver ? [
                        'id' => $driver->id,
                        'name' => $driver->full_name,
                        'phone' => $driver->phone,
                        'vehicle_type' => $vehicle ? $vehicle->vehicle_type : null,
                        'vehicle_number' => $vehicle ? $vehicle->registration_number : null,
                        'vehicle_model' => $vehicle ? ($vehicle->make . ' ' . $vehicle->model) : null,
                        'vehicle_color' => $vehicle ? $vehicle->vehicle_color : null,
                        'average_rating' => $driver->average_rating,
                        'current_latitude' => null, // Will be implemented with real-time tracking
                        'current_longitude' => null, // Will be implemented with real-time tracking
                    ] : null,
                    'estimated_cost' => $transport->estimated_cost,
                    'actual_cost' => $transport->actual_cost,
                    'rating' => $transport->rating,
                    'feedback' => $transport->feedback,
                    'created_at' => $transport->created_at->toIso8601String(),
                    'updated_at' => $transport->updated_at->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'pagination' => [
                    'current_page' => $transports->currentPage(),
                    'last_page' => $transports->lastPage(),
                    'per_page' => $transports->perPage(),
                    'total' => $transports->total()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching nurse transport requests: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transport requests.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


/**
 * Create a new transport request
 * POST /api/nurse/transport-requests
 * 
 * UPDATED: Now accepts and assigns driver_id from the request
 */
public function createTransportRequest(Request $request): JsonResponse
{
    \Log::info("Create Transport");
    \Log::info($request->all());
    
    try {
        $nurse = auth()->user();
        
        if ($nurse->role !== 'nurse') {
            return response()->json([
                'success' => false,
                'message' => 'Only nurses can create transport requests.'
            ], 403);
        }

        $validated = $request->validate([
            'patient_id' => 'required|exists:users,id',
            'driver_id' => 'nullable|exists:drivers,id', // ADDED: Accept driver_id
            'transport_type' => 'required|in:ambulance,regular',
            'priority' => 'required|in:emergency,urgent,routine',
            'scheduled_time' => 'nullable|date|after:now',
            'pickup_location' => 'required|string|max:255',
            'pickup_address' => 'nullable|string',
            'pickup_latitude' => 'nullable|numeric|between:-90,90',
            'pickup_longitude' => 'nullable|numeric|between:-180,180',
            'destination_location' => 'required|string|max:255',
            'destination_address' => 'required|string',
            'destination_latitude' => 'nullable|numeric|between:-90,90',
            'destination_longitude' => 'nullable|numeric|between:-180,180',
            'reason' => 'nullable|string',
            'special_requirements' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
        ]);

        // Verify patient exists and is actually a patient
        $patient = User::where('id', $request->patient_id)
            ->where('role', 'patient')
            ->first();
        
        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid patient selected'
            ], 422);
        }

        // ADDED: Verify driver if provided
        if ($request->filled('driver_id')) {
            $driver = Driver::find($request->driver_id);
            
            if (!$driver) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid driver selected'
                ], 422);
            }

            // UPDATED: Check if driver has any active transport requests
            $hasActiveTransport = TransportRequest::where('driver_id', $driver->id)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->exists();

            if ($hasActiveTransport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected driver is currently on another trip. Please select a different driver.'
                ], 422);
            }

            // Check if driver has appropriate vehicle for transport type
            $hasAppropriateVehicle = $driver->currentVehicle && 
                                    $driver->currentVehicle->vehicle_type === $validated['transport_type'] &&
                                    $driver->currentVehicle->is_active &&
                                    $driver->currentVehicle->is_available;

            if (!$hasAppropriateVehicle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected driver does not have an appropriate vehicle for this transport type'
                ], 422);
            }
        }

        DB::beginTransaction();

        try {
            // Create the transport request
            $transportRequest = TransportRequest::create([
                'patient_id' => $validated['patient_id'],
                'requested_by_id' => $nurse->id,
                'driver_id' => $validated['driver_id'] ?? null, // ADDED: Assign driver if provided
                'transport_type' => $validated['transport_type'],
                'priority' => $validated['priority'],
                'scheduled_time' => $validated['scheduled_time'],
                'pickup_location' => $validated['pickup_location'],
                'pickup_address' => $validated['pickup_address'],
                'pickup_latitude' => $validated['pickup_latitude'] ?? null,
                'pickup_longitude' => $validated['pickup_longitude'] ?? null,
                'destination_location' => $validated['destination_location'],
                'destination_address' => $validated['destination_address'],
                'destination_latitude' => $validated['destination_latitude'] ?? null,
                'destination_longitude' => $validated['destination_longitude'] ?? null,
                'reason' => $validated['reason'],
                'special_requirements' => $validated['special_requirements'] ?? null,
                'contact_person' => $validated['contact_person'] ?? null,
                'status' => $validated['driver_id'] ? 'assigned' : 'requested' // UPDATED: Set to 'assigned' if driver was provided
            ]);

            // Calculate estimated cost
            $estimatedCost = $transportRequest->calculateEstimatedCost();
            $transportRequest->update(['estimated_cost' => $estimatedCost]);

            // UPDATED: Only auto-assign for emergency requests WITHOUT a driver
            if ($validated['priority'] === 'emergency' && !$validated['driver_id']) {
                $this->autoAssignDriver($transportRequest);
            }

            // REMOVED: Driver availability update - availability is now determined by active transport requests
            // The driver's status is checked dynamically by querying transport_requests table

            DB::commit();

            $transportRequest->load(['patient', 'requestedBy', 'driver.currentVehicle']);

            // UPDATED: Better response with driver info
            $response = [
                'success' => true,
                'message' => $validated['driver_id'] 
                    ? 'Transport request created and driver assigned successfully!' 
                    : 'Transport request created successfully!',
                'data' => [
                    'id' => $transportRequest->id,
                    'patient_name' => $transportRequest->patient->first_name . ' ' . $transportRequest->patient->last_name,
                    'transport_type' => $transportRequest->transport_type,
                    'priority' => $transportRequest->priority,
                    'status' => $transportRequest->status,
                    'scheduled_time' => $transportRequest->scheduled_time?->toIso8601String(),
                    'pickup_location' => $transportRequest->pickup_location,
                    'destination_location' => $transportRequest->destination_location,
                    'estimated_cost' => $transportRequest->estimated_cost,
                ]
            ];

            // Add driver info if assigned
            if ($transportRequest->driver) {
                $vehicle = $transportRequest->driver->currentVehicle;
                $response['data']['driver'] = [
                    'id' => $transportRequest->driver->id,
                    'name' => $transportRequest->driver->full_name,
                    'phone' => $transportRequest->driver->phone,
                    'vehicle_type' => $vehicle ? $vehicle->vehicle_type : null,
                    'vehicle_number' => $vehicle ? $vehicle->registration_number : null,
                    'vehicle_model' => $vehicle ? ($vehicle->make . ' ' . $vehicle->model) : null,
                    'average_rating' => $transportRequest->driver->average_rating,
                ];
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        \Log::error('Error creating transport request: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        return response()->json([
            'success' => false,
            'message' => 'Failed to create transport request.',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}


    /**
     * Get available drivers for transport
     * GET /api/nurse/transport-requests/available-drivers
     */
    public function getAvailableDrivers(Request $request): JsonResponse
    {
        try {
            $nurse = auth()->user();
            
            if ($nurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can access this endpoint.'
                ], 403);
            }

            $transportType = $request->query('transport_type', 'regular');
            
            // Get available drivers with their assigned vehicles
            $drivers = Driver::available()
                ->with(['currentVehicle', 'activeVehicleAssignment.vehicle'])
                ->whereHas('currentVehicle', function($query) use ($transportType) {
                    // Qualify column names with table names to avoid ambiguity
                    $query->where('vehicles.vehicle_type', $transportType)
                        ->where('vehicles.is_active', true)
                        ->where('vehicles.is_available', true);
                })
                // Ensure driver doesn't have active transports
                ->whereDoesntHave('transportRequests', function($query) {
                    $query->whereIn('status', ['assigned', 'in_progress']);
                })
                ->orderBy('average_rating', 'desc')
                ->get()
                ->map(function($driver) {
                    $vehicle = $driver->currentVehicle;
                    
                    return [
                        'id' => $driver->id,
                        'name' => $driver->full_name,
                        'phone' => $driver->phone,
                        'vehicle_type' => $vehicle ? $vehicle->vehicle_type : null,
                        'vehicle_number' => $vehicle ? $vehicle->registration_number : null,
                        'vehicle_model' => $vehicle ? ($vehicle->make . ' ' . $vehicle->model) : null,
                        'vehicle_color' => $vehicle ? $vehicle->vehicle_color : null,
                        'average_rating' => $driver->average_rating ? round($driver->average_rating, 1) : null,
                        'total_trips' => $driver->total_trips ?? 0,
                        'current_latitude' => null,
                        'current_longitude' => null,
                        'is_available' => true,
                        'status' => 'available',
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $drivers,
                'total' => $drivers->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching available drivers: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available drivers.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Auto-assign driver for emergency requests
     */
    private function autoAssignDriver(TransportRequest $transportRequest)
    {
        $availableDriver = Driver::available()
            ->where('vehicle_type', $transportRequest->transport_type)
            ->orderBy('average_rating', 'desc')
            ->first();

        if ($availableDriver) {
            $transportRequest->assignDriver($availableDriver->id);
        }
    }
}