<?php
namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CarePlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CarePlanController extends Controller
{


        /**
     * Get care plans based on user role
     * GET /api/mobile/care-plans
     * 
     *Now supports both nurses and patients
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // ✅ Allow both nurses and patients to access this endpoint
            if (!in_array($user->role, ['nurse', 'patient'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 403);
            }

            // Get pagination parameters
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);

            // Build query based on user role
            $carePlansQuery = $this->buildCarePlanQuery($user);

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

            // Apply priority filter (if provided)
            if ($request->has('priority') && !empty($request->priority)) {
                $carePlansQuery->where('priority', $request->priority);
            }

            // Order by created_at and paginate
            $carePlans = $carePlansQuery->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // Transform the data for the mobile app
            $transformedPlans = $carePlans->getCollection()->map(function($carePlan) use ($user) {
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
                    'completed_tasks' => $carePlan->completed_tasks ?? [],
                    'medications' => $carePlan->medications ?? [],
                    'special_instructions' => $carePlan->special_instructions ?? [],
                ];
            });

            // Update the collection with transformed data
            $carePlans->setCollection($transformedPlans);

            // Return paginated response with proper structure
            return response()->json([
                'success' => true,
                'data' => $carePlans->items(),
                'current_page' => $carePlans->currentPage(),
                'last_page' => $carePlans->lastPage(),
                'per_page' => $carePlans->perPage(),
                'total' => $carePlans->total(),
                'from' => $carePlans->firstItem(),
                'to' => $carePlans->lastItem(),
                'message' => $transformedPlans->isEmpty() ? 
                    ($user->role === 'patient' ? 'No care plans assigned yet.' : 'No care plans assigned yet.') : 
                    null
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching care plans: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch care plans. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


    /**
     * Create a new care plan
     * POST /api/mobile/care-plans
     */
    public function store(Request $request): JsonResponse
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
                    'doctor_id' => 'nullable|exists:users,id',
                    'title' => 'required|string|max:255',
                    'description' => 'required|string',
                    'care_type' => 'required|string|in:general_care,elderly_care,post_surgery_care,pediatric_care,chronic_disease_management,palliative_care,rehabilitation_care',
                    'priority' => 'required|string|in:low,medium,high',
                    'start_date' => 'required|date|after_or_equal:today',
                    'end_date' => 'nullable|date|after:start_date',
                    'frequency' => 'required|string|in:once_daily,weekly,twice_weekly,monthly,as_needed',  // UPDATED: Changed to match what frontend sends
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

                // FIXED: Only verify doctor role if doctor_id is provided
                if (!empty($validated['doctor_id'])) {
                    $doctor = User::findOrFail($validated['doctor_id']);
                    if (!in_array($doctor->role, ['doctor', 'admin', 'superadmin'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Selected user cannot be assigned as doctor.',
                        ], 422);
                    }
                }

                // Map care type from display format to database format
                $careTypeMap = [
                    'general_care' => 'general_care',
                    'elderly_care' => 'elderly_care',
                    'post_surgery_care' => 'post_surgery_care',
                    'pediatric_care' => 'pediatric_care',
                    'chronic_disease_management' => 'chronic_disease_management',
                    'palliative_care' => 'palliative_care',
                    'rehabilitation_care' => 'rehabilitation_care',
                ];

                // Map frequency - UPDATED to handle the correct format from frontend
                $frequencyMap = [
                    'once_daily' => 'once_daily',
                    'daily' => 'once_daily',  // Fallback
                    'weekly' => 'weekly',
                    'twice_weekly' => 'twice_weekly',
                    'bi-weekly' => 'twice_weekly',  // Fallback
                    'monthly' => 'monthly',
                    'as_needed' => 'as_needed',
                ];

                // Map priority
                $priorityMap = [
                    'low' => 'low',
                    'medium' => 'medium',
                    'high' => 'high',
                ];

                $validated['created_by'] = $nurse->id;
                $validated['status'] = 'draft'; // Nurses create drafts that need approval
                $validated['primary_nurse_id'] = $nurse->id; // Auto-assign creating nurse
                $validated['nurse_assigned_at'] = now();
                
                // Convert mapped values (now expecting lowercase snake_case from frontend)
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


            public function update(Request $request, $id): JsonResponse
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

                $carePlan = CarePlan::findOrFail($id);

                // Verify nurse has permission to edit (is primary or secondary nurse)
                if ($carePlan->primary_nurse_id !== $nurse->id && 
                    $carePlan->secondary_nurse_id !== $nurse->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to update this care plan.'
                    ], 403);
                }

                $validated = $request->validate([
                    'patient_id' => 'required|exists:users,id',
                    'doctor_id' => 'nullable|exists:users,id',
                    'title' => 'required|string|max:255',
                    'description' => 'required|string',
                    'care_type' => 'required|string|in:general_care,elderly_care,post_surgery_care,pediatric_care,chronic_disease_management,palliative_care,rehabilitation_care',
                    'priority' => 'required|string|in:low,medium,high',
                    'start_date' => 'required|date',
                    'end_date' => 'nullable|date|after:start_date',
                    'frequency' => 'required|string|in:once_daily,weekly,twice_weekly,monthly,as_needed',  // UPDATED
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

                // FIXED: Only verify doctor role if doctor_id is provided
                if (!empty($validated['doctor_id'])) {
                    $doctor = User::findOrFail($validated['doctor_id']);
                    if (!in_array($doctor->role, ['doctor', 'admin', 'superadmin'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Selected user cannot be assigned as doctor.',
                        ], 422);
                    }
                }

                // Same mapping as create
                $careTypeMap = [
                    'general_care' => 'general_care',
                    'elderly_care' => 'elderly_care',
                    'post_surgery_care' => 'post_surgery_care',
                    'pediatric_care' => 'pediatric_care',
                    'chronic_disease_management' => 'chronic_disease_management',
                    'palliative_care' => 'palliative_care',
                    'rehabilitation_care' => 'rehabilitation_care',
                ];

                $frequencyMap = [
                    'once_daily' => 'once_daily',
                    'daily' => 'once_daily',
                    'weekly' => 'weekly',
                    'twice_weekly' => 'twice_weekly',
                    'bi-weekly' => 'twice_weekly',
                    'monthly' => 'monthly',
                    'as_needed' => 'as_needed',
                ];

                $priorityMap = [
                    'low' => 'low',
                    'medium' => 'medium',
                    'high' => 'high',
                ];

                // Convert mapped values
                $validated['care_type'] = $careTypeMap[$validated['care_type']] ?? $validated['care_type'];
                $validated['frequency'] = $frequencyMap[$validated['frequency']] ?? $validated['frequency'];
                $validated['priority'] = $priorityMap[$validated['priority']] ?? $validated['priority'];

                DB::beginTransaction();

                try {
                    $carePlan->update($validated);
                    $carePlan->load(['patient', 'doctor', 'primaryNurse', 'secondaryNurse']);

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
     * Toggle care task completion
     * POST /api/mobile/care-plans/{id}/tasks/toggle
     */
    public function toggleTask(Request $request, $id): JsonResponse
    {
        try {
            $user = auth()->user();

            if ($user->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can update care tasks.'
                ], 403);
            }

            $validated = $request->validate([
                'task_index' => 'required|integer|min:0',
                'is_completed' => 'required|boolean'
            ]);

            $carePlan = CarePlan::where('id', $id)
                ->where(function($query) use ($user) {
                    $query->where('primary_nurse_id', $user->id)
                        ->orWhere('secondary_nurse_id', $user->id);
                })
                ->first();

            if (!$carePlan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care plan not found or unauthorized.'
                ], 404);
            }

            $careTasks = $carePlan->care_tasks ?? [];
            if (!isset($careTasks[$validated['task_index']])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid task index.'
                ], 422);
            }

            DB::beginTransaction();

            try {
                $completedTasks = $carePlan->completed_tasks ?? [];
                $taskIndex = $validated['task_index'];

                if ($validated['is_completed']) {
                    if (!in_array($taskIndex, $completedTasks)) {
                        $completedTasks[] = $taskIndex;
                    }
                } else {
                    $completedTasks = array_values(
                        array_filter($completedTasks, fn($idx) => $idx !== $taskIndex)
                    );
                }

                $totalTasks = count($careTasks);
                $completedCount = count($completedTasks);
                $completionPercentage = $totalTasks > 0 
                    ? round(($completedCount / $totalTasks) * 100) 
                    : 0;

                $carePlan->update([
                    'completed_tasks' => $completedTasks,
                    'completion_percentage' => $completionPercentage
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Task updated successfully.',
                    'data' => [
                        'completed_tasks' => $completedTasks,
                        'completion_percentage' => $completionPercentage,
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error toggling task: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Build care plan query based on user role
     */
    private function buildCarePlanQuery($user)
    {
        $query = CarePlan::with(['patient', 'doctor', 'primaryNurse', 'secondaryNurse']);

        if ($user->role === 'nurse') {
            $query->where(function($q) use ($user) {
                $q->where('primary_nurse_id', $user->id)
                  ->orWhere('secondary_nurse_id', $user->id);
            });
        } elseif ($user->role === 'patient') {
            $query->where('patient_id', $user->id);
        }

        return $query;
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('priority') && !empty($request->priority)) {
            $query->where('priority', $request->priority);
        }
    }

    /**
     * Transform care plan for API response
     */
    private function transformCarePlan($carePlan, $userRole): array
    {
        $careTypes = CarePlan::getCareTypes();
        
        return [
            'id' => $carePlan->id,
            'title' => $carePlan->title,
            'description' => $carePlan->description ?? '',
            'care_type' => $careTypes[$carePlan->care_type] ?? ucfirst($carePlan->care_type),
            'status' => ucfirst($carePlan->status),
            'priority' => ucfirst($carePlan->priority),
            'start_date' => $carePlan->start_date,
            'end_date' => $carePlan->end_date,
            'progress' => $carePlan->completion_percentage / 100.0,
            'care_tasks' => $carePlan->care_tasks ?? [],
            'completed_tasks' => $carePlan->completed_tasks ?? [],
        ];
    }


    /**
     * Get all doctors for care plan assignment
     * GET /api/mobile/care-plans/doctors
     */
    public function getDoctors(Request $request): JsonResponse
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

            // Get all users with doctor role (and optionally admin/superadmin who can act as doctors)
            $doctorsQuery = User::whereIn('role', ['doctor'])
                ->select('id', 'first_name', 'last_name', 'specialization', 'email')
                ->orderBy('first_name', 'asc')
                ->orderBy('last_name', 'asc');

            // Optional search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $doctorsQuery->where(function($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('specialization', 'like', "%{$search}%");
                });
            }

            $doctors = $doctorsQuery->get();

            // Transform the data
            $transformedDoctors = $doctors->map(function($doctor) {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->first_name . ' ' . $doctor->last_name,
                    'first_name' => $doctor->first_name,
                    'last_name' => $doctor->last_name,
                    'specialization' => $doctor->specialization ?? 'General',
                    'email' => $doctor->email,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedDoctors,
                'total' => $transformedDoctors->count(),
                'message' => $transformedDoctors->isEmpty() ? 'No doctors found.' : null
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching doctors: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch doctors. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get all patients assigned to the nurse for care plan assignment
     * GET /api/mobile/care-plans/patients
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

            // Get unique patient IDs from care plans where this nurse is assigned
            $patientIds = CarePlan::where(function($query) use ($nurse) {
                    $query->where('primary_nurse_id', $nurse->id)
                        ->orWhere('secondary_nurse_id', $nurse->id);
                })
                ->distinct()
                ->pluck('patient_id')
                ->filter() // Remove any null values
                ->unique()
                ->toArray();

            if (empty($patientIds)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                    'message' => 'No patients assigned to you yet.'
                ]);
            }

            // Get patients who are in the nurse's care plans
            $patientsQuery = User::where('role', 'patient')
                ->whereIn('id', $patientIds)
                ->select('id', 'first_name', 'last_name', 'date_of_birth', 'email', 'avatar')
                ->orderBy('first_name', 'asc')
                ->orderBy('last_name', 'asc');

            // Optional search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $patientsQuery->where(function($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            }

            $patients = $patientsQuery->get();

            // Transform the data
            $transformedPatients = $patients->map(function($patient) {
                $age = $patient->date_of_birth ? 
                    \Carbon\Carbon::parse($patient->date_of_birth)->age : 
                    null;

                return [
                    'id' => $patient->id,
                    'name' => $patient->first_name . ' ' . $patient->last_name,
                    'first_name' => $patient->first_name,
                    'last_name' => $patient->last_name,
                    'age' => $age,
                    'email' => $patient->email,
                    'avatar' => $patient->avatar,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedPatients,
                'total' => $transformedPatients->count(),
                'message' => $transformedPatients->isEmpty() ? 'No patients found.' : null
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching patients: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch patients. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get a specific care plan by ID
     * GET /api/mobile/care-plans/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Build query based on user role
            $carePlanQuery = CarePlan::with([
                'patient:id,first_name,last_name,date_of_birth,avatar',
                'doctor:id,first_name,last_name,specialization',
                'primaryNurse:id,first_name,last_name',
                'secondaryNurse:id,first_name,last_name'
            ]);

            // Apply role-based filters
            if ($user->role === 'nurse') {
                $carePlanQuery->where(function($query) use ($user) {
                    $query->where('primary_nurse_id', $user->id)
                        ->orWhere('secondary_nurse_id', $user->id);
                });
            } elseif ($user->role === 'patient') {
                $carePlanQuery->where('patient_id', $user->id);
            } elseif (!in_array($user->role, ['doctor', 'admin', 'superadmin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 403);
            }

            $carePlan = $carePlanQuery->findOrFail($id);

            // Transform the data
            $patient = $carePlan->patient;
            $doctor = $carePlan->doctor;
            
            $age = $patient && $patient->date_of_birth ? 
                \Carbon\Carbon::parse($patient->date_of_birth)->age : 
                null;

            $careTypes = CarePlan::getCareTypes();
            $careTypeLabel = $careTypes[$carePlan->care_type] ?? ucfirst(str_replace('_', ' ', $carePlan->care_type));

            $priorityMap = [
                'high' => 'High',
                'medium' => 'Medium',
                'low' => 'Low'
            ];
            $priority = $priorityMap[$carePlan->priority] ?? 'Medium';

            $statusMap = [
                'draft' => 'Draft',
                'pending_approval' => 'Pending Approval',
                'active' => 'Active',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled'
            ];
            $status = $statusMap[$carePlan->status] ?? ucfirst($carePlan->status);

            $transformedCarePlan = [
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
                'completed_tasks' => $carePlan->completed_tasks ?? [],
                'medications' => $carePlan->medications ?? [],
                'special_instructions' => $carePlan->special_instructions ?? [],
                'created_at' => $carePlan->created_at->toIso8601String(),
                'updated_at' => $carePlan->updated_at->toIso8601String(),
            ];

            return response()->json([
                'success' => true,
                'data' => $transformedCarePlan
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Care plan not found.'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error fetching care plan detail: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch care plan. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Delete a care plan
     * DELETE /api/mobile/care-plans/{id}
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Only nurses, doctors, and admins can delete care plans
            if (!in_array($user->role, ['nurse', 'doctor', 'admin', 'superadmin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete care plans.'
                ], 403);
            }

            $carePlan = CarePlan::findOrFail($id);

            // Additional permission check for nurses
            if ($user->role === 'nurse') {
                if ($carePlan->primary_nurse_id !== $user->id && 
                    $carePlan->secondary_nurse_id !== $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to delete this care plan.'
                    ], 403);
                }
            }

            DB::beginTransaction();

            try {
                $carePlan->delete();
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Care plan deleted successfully.'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Care plan not found.'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error deleting care plan: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete care plan. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}