<?php

namespace App\Http\Controllers;

use App\Models\CarePlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CarePlanController extends Controller
{


       public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $query = CarePlan::with([
            'patient', 
            'doctor', 
            'primaryNurse',
            'secondaryNurse',
            'createdBy', 
            'approvedBy'
        ]);

        // Apply role-based filtering
        if ($user->role === 'doctor') {
            $query->where('doctor_id', $user->id);
        } elseif ($user->role === 'nurse') {
            $query->where(function($q) use ($user) {
                $q->where('primary_nurse_id', $user->id)
                  ->orWhere('secondary_nurse_id', $user->id);
            });
        } elseif ($user->role === 'patient') {
            $query->where('patient_id', $user->id);
        }

        $query->latest();

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by care type
        if ($request->has('care_type') && $request->care_type !== 'all') {
            $query->where('care_type', $request->care_type);
        }

        // Filter by priority
        if ($request->has('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('assignment_notes', 'like', "%{$search}%")
                  ->orWhereHas('patient', function($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('doctor', function($dq) use ($search) {
                      $dq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('primaryNurse', function($nq) use ($search) {
                      $nq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $carePlans = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $carePlans,
            'filters' => [
                'care_types' => CarePlan::getCareTypes(),
                'statuses' => CarePlan::getStatuses(),
                'priorities' => CarePlan::getPriorities(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $validated = $request->validate([
                'patient_id' => 'required|exists:users,id',
                'doctor_id' => 'sometimes|exists:users,id',
                'primary_nurse_id' => 'nullable|exists:users,id',
                'secondary_nurse_id' => 'nullable|exists:users,id|different:primary_nurse_id',
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'care_type' => ['required', Rule::in(array_keys(CarePlan::getCareTypes()))],
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'nullable|date|after:start_date',
                'frequency' => ['required', Rule::in(array_keys(CarePlan::getFrequencies()))],
                'custom_frequency_details' => 'nullable|string',
                'care_tasks' => 'required|array|min:1',
                'care_tasks.*' => 'required|string',
                'priority' => ['required', Rule::in(array_keys(CarePlan::getPriorities()))],
                'assignment_notes' => 'nullable|string|max:1000',
            ]);

            // For doctors, automatically set doctor_id to themselves
            if ($user->role === 'doctor') {
                $validated['doctor_id'] = $user->id;
            } elseif (!isset($validated['doctor_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor ID is required.',
                ], 422);
            }

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

            // Verify nurses if assigned
            if (isset($validated['primary_nurse_id'])) {
                $primaryNurse = User::findOrFail($validated['primary_nurse_id']);
                if ($primaryNurse->role !== 'nurse') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Primary nurse must have nurse role.',
                    ], 422);
                }
            }

            if (isset($validated['secondary_nurse_id'])) {
                $secondaryNurse = User::findOrFail($validated['secondary_nurse_id']);
                if ($secondaryNurse->role !== 'nurse') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Secondary nurse must have nurse role.',
                    ], 422);
                }
            }

            $validated['created_by'] = $user->id;
            
            // Set status based on user role
            if (in_array($user->role, ['admin', 'superadmin', 'doctor'])) {
                $validated['status'] = 'pending_approval';
            } else {
                $validated['status'] = 'draft';
            }

            DB::beginTransaction();

            try {
                $carePlan = CarePlan::create($validated);
                $carePlan->load(['patient', 'doctor', 'primaryNurse', 'secondaryNurse', 'createdBy']);

                DB::commit();

                $statusMessage = $validated['status'] === 'pending_approval' 
                    ? 'Care plan created and submitted for approval successfully.' 
                    : 'Care plan created successfully.';

                return response()->json([
                    'success' => true,
                    'message' => $statusMessage,
                    'data' => $carePlan
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
            Log::error('Error creating care plan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create care plan. Please try again.'
            ], 500);
        }
    }


    public function show(CarePlan $carePlan): JsonResponse
    {
        $user = auth()->user();

        // Check permissions
        if (!$this->canAccessCarePlan($user, $carePlan)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this care plan.',
            ], 403);
        }

        $carePlan->load([
            'patient', 
            'doctor', 
            'primaryNurse',
            'secondaryNurse',
            'createdBy', 
            'approvedBy',
            'schedules.nurse'
        ]);

        return response()->json([
            'success' => true,
            'data' => $carePlan
        ]);
    }

    private function canAccessCarePlan($user, $carePlan): bool
    {
        switch ($user->role) {
            case 'doctor':
                return $carePlan->doctor_id === $user->id;
            case 'nurse':
                return $carePlan->primary_nurse_id === $user->id || $carePlan->secondary_nurse_id === $user->id;
            case 'patient':
                return $carePlan->patient_id === $user->id;
            case 'admin':
            case 'superadmin':
                return true;
            default:
                return false;
        }
    }

     /**
     * Check if user can edit a care plan
     */
    private function canEditCarePlan($user, $carePlan): bool
    {
        switch ($user->role) {
            case 'doctor':
                return $carePlan->doctor_id === $user->id;
            case 'admin':
            case 'superadmin':
                return true;
            default:
                return false;
        }
    }

    

    public function update(Request $request, CarePlan $carePlan): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$this->canEditCarePlan($user, $carePlan)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to edit this care plan.',
                ], 403);
            }

            if (!in_array($carePlan->status, ['draft', 'pending_approval'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot modify care plan in current status.',
                ], 422);
            }

            $validated = $request->validate([
                'patient_id' => 'sometimes|exists:users,id',
                'doctor_id' => 'sometimes|exists:users,id',
                'primary_nurse_id' => 'nullable|exists:users,id',
                'secondary_nurse_id' => 'nullable|exists:users,id|different:primary_nurse_id',
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'care_type' => ['sometimes', Rule::in(array_keys(CarePlan::getCareTypes()))],
                'start_date' => 'sometimes|date',
                'end_date' => 'nullable|date|after:start_date',
                'frequency' => ['sometimes', Rule::in(array_keys(CarePlan::getFrequencies()))],
                'custom_frequency_details' => 'nullable|string',
                'care_tasks' => 'sometimes|array|min:1',
                'priority' => ['sometimes', Rule::in(array_keys(CarePlan::getPriorities()))],
                'assignment_notes' => 'nullable|string|max:1000',
            ]);

            // Verify nurses if being updated
            if (isset($validated['primary_nurse_id'])) {
                $primaryNurse = User::findOrFail($validated['primary_nurse_id']);
                if ($primaryNurse->role !== 'nurse') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Primary nurse must have nurse role.',
                    ], 422);
                }
            }

            if (isset($validated['secondary_nurse_id'])) {
                $secondaryNurse = User::findOrFail($validated['secondary_nurse_id']);
                if ($secondaryNurse->role !== 'nurse') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Secondary nurse must have nurse role.',
                    ], 422);
                }
            }

            $carePlan->update($validated);
            $carePlan->load(['patient', 'doctor', 'primaryNurse', 'secondaryNurse', 'createdBy', 'approvedBy']);

            return response()->json([
                'success' => true,
                'message' => 'Care plan updated successfully.',
                'data' => $carePlan
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating care plan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update care plan. Please try again.'
            ], 500);
        }
    }

    public function delete(CarePlan $carePlan): JsonResponse
    {
        try {
            $user = auth()->user();

            // Check permissions
            if (!$this->canEditCarePlan($user, $carePlan)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to delete this care plan.',
                ], 403);
            }

            // Only allow deletion if no active nurses assigned or plan is still in draft
            if ($carePlan->status === 'active' && $carePlan->has_nurse_assigned) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete active care plan with assigned nurses.',
                ], 422);
            }

            $carePlan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Care plan deleted successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting care plan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete care plan. Please try again.'
            ], 500);
        }
    }


    public function assignNurse(Request $request, CarePlan $carePlan): JsonResponse
    {
        try {
            $validated = $request->validate([
                'primary_nurse_id' => 'required|exists:users,id',
                'secondary_nurse_id' => 'nullable|exists:users,id|different:primary_nurse_id',
                'assignment_notes' => 'nullable|string|max:1000',
                'assignment_type' => ['nullable', Rule::in(array_keys(CarePlan::getAssignmentTypes()))],
            ]);

            $primaryNurse = User::findOrFail($validated['primary_nurse_id']);
            $secondaryNurse = isset($validated['secondary_nurse_id']) ? 
                User::findOrFail($validated['secondary_nurse_id']) : null;

            if (!$carePlan->assignNurse($primaryNurse, $secondaryNurse, $validated['assignment_notes'] ?? null)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to assign nurse to care plan.',
                ], 422);
            }

            if (isset($validated['assignment_type'])) {
                $carePlan->assignment_type = $validated['assignment_type'];
            }
            $carePlan->save();

            $carePlan->load(['primaryNurse', 'secondaryNurse']);

            return response()->json([
                'success' => true,
                'message' => 'Nurse assigned successfully.',
                'data' => $carePlan
            ]);
        } catch (\Exception $e) {
            Log::error('Error assigning nurse: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign nurse. Please try again.'
            ], 500);
        }
    }

    public function reassignNurse(Request $request, CarePlan $carePlan): JsonResponse
    {
        try {
            $validated = $request->validate([
                'new_primary_nurse_id' => 'required|exists:users,id',
                'new_secondary_nurse_id' => 'nullable|exists:users,id|different:new_primary_nurse_id',
                'reassignment_reason' => 'required|string|max:1000'
            ]);

            $newPrimaryNurse = User::findOrFail($validated['new_primary_nurse_id']);
            $newSecondaryNurse = isset($validated['new_secondary_nurse_id']) ? 
                User::findOrFail($validated['new_secondary_nurse_id']) : null;

            if (!$carePlan->reassignNurse($newPrimaryNurse, $newSecondaryNurse, $validated['reassignment_reason'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reassign nurse.',
                ], 422);
            }

            $carePlan->load(['primaryNurse', 'secondaryNurse']);

            return response()->json([
                'success' => true,
                'message' => 'Nurse reassigned successfully.',
                'data' => $carePlan
            ]);
        } catch (\Exception $e) {
            Log::error('Error reassigning nurse: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reassign nurse. Please try again.'
            ], 500);
        }
    }

    public function acceptByNurse(Request $request, CarePlan $carePlan): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nurse_response_notes' => 'nullable|string|max:1000'
            ]);

            $nurse = auth()->user();

            if (!$carePlan->acceptByNurse($nurse, $validated['nurse_response_notes'] ?? null)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to this care plan.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Care plan accepted successfully.',
                'data' => $carePlan
            ]);
        } catch (\Exception $e) {
            Log::error('Error accepting care plan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept care plan. Please try again.'
            ], 500);
        }
    }

    public function findSuitableNurses(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'care_plan_id' => 'required|exists:care_plans,id'
            ]);

            $carePlan = CarePlan::findOrFail($validated['care_plan_id']);

            $nurses = User::where('role', 'nurse')
                ->where('is_active', true)
                ->where('is_verified', true)
                ->when(isset($carePlan->min_years_experience), function ($query) use ($carePlan) {
                    return $query->where('years_experience', '>=', $carePlan->min_years_experience);
                })
                ->get();

            $suitableNurses = $nurses->map(function($nurse) use ($carePlan) {
                $currentPlans = CarePlan::where('primary_nurse_id', $nurse->id)
                    ->whereIn('status', ['active'])
                    ->count();
                
                return [
                    'nurse' => $nurse->only(['id', 'first_name', 'last_name', 'email', 'specialization', 'years_experience']),
                    'current_assignments' => $currentPlans,
                    'qualifications_met' => $carePlan->matchesNurseQualifications($nurse),
                ];
            })
            ->filter(function($nurseData) {
                return $nurseData['qualifications_met'];
            })
            ->sortBy('current_assignments')
            ->values();

            return response()->json([
                'success' => true,
                'data' => $suitableNurses,
                'total_found' => $suitableNurses->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error finding suitable nurses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to find suitable nurses. Please try again.'
            ], 500);
        }
    }

    public function submitForApproval(CarePlan $carePlan): JsonResponse
    {
        if ($carePlan->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft care plans can be submitted for approval.',
            ], 422);
        }

        $carePlan->update(['status' => 'pending_approval']);

        return response()->json([
            'success' => true,
            'message' => 'Care plan submitted for approval.',
            'data' => $carePlan
        ]);
    }

    /**
     * Approve and activate a care plan in one step
     */
    public function approve(Request $request, CarePlan $carePlan): JsonResponse
    {
        $validated = $request->validate([
            'approval_notes' => 'nullable|string|max:1000'
        ]);

        if ($carePlan->status !== 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending care plans can be approved.',
            ], 422);
        }

        // Approve the plan
        $carePlan->approve(auth()->user(), $validated['approval_notes'] ?? null);
        
        // Automatically activate after approval
        $carePlan->activate();

        return response()->json([
            'success' => true,
            'message' => 'Care plan approved and activated successfully.',
            'data' => $carePlan->load(['approvedBy'])
        ]);
    }

    public function complete(Request $request, CarePlan $carePlan): JsonResponse
    {
        $validated = $request->validate([
            'progress_notes' => 'nullable|string|max:2000'
        ]);

        $carePlan->complete($validated['progress_notes'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Care plan completed successfully.',
            'data' => $carePlan
        ]);
    }

    public function updateProgress(Request $request, CarePlan $carePlan): JsonResponse
    {
        $validated = $request->validate([
            'completion_percentage' => 'required|integer|min:0|max:100',
            'progress_notes' => 'nullable|string|max:2000'
        ]);

        $carePlan->updateProgress(
            $validated['completion_percentage'], 
            $validated['progress_notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Progress updated successfully.',
            'data' => $carePlan
        ]);
    }

    public function getStatistics(): JsonResponse
    {
        $user = auth()->user();
        
        $baseQuery = CarePlan::query();
        
        if ($user->role === 'doctor') {
            $baseQuery->where('doctor_id', $user->id);
        } elseif ($user->role === 'nurse') {
            $baseQuery->where(function($q) use ($user) {
                $q->where('primary_nurse_id', $user->id)
                  ->orWhere('secondary_nurse_id', $user->id);
            });
        } elseif ($user->role === 'patient') {
            $baseQuery->where('patient_id', $user->id);
        }

        $stats = [
            'total_plans' => (clone $baseQuery)->count(),
            'active_plans' => (clone $baseQuery)->where('status', 'active')->count(),
            'pending_approval' => (clone $baseQuery)->where('status', 'pending_approval')->count(),
            'completed_plans' => (clone $baseQuery)->where('status', 'completed')->count(),
            'by_care_type' => (clone $baseQuery)->select('care_type')
                ->selectRaw('count(*) as count')
                ->groupBy('care_type')
                ->get()
                ->mapWithKeys(function($item) {
                    $careTypes = CarePlan::getCareTypes();
                    $careTypeLabel = $careTypes[$item->care_type] ?? $item->care_type;
                    return [$careTypeLabel => $item->count];
                }),
            'by_priority' => (clone $baseQuery)->select('priority')
                ->selectRaw('count(*) as count')
                ->groupBy('priority')
                ->get()
                ->mapWithKeys(function($item) {
                    $priorities = CarePlan::getPriorities();
                    $priorityLabel = $priorities[$item->priority] ?? $item->priority;
                    return [$priorityLabel => $item->count];
                }),
            'completion_rates' => [
                'average' => (clone $baseQuery)->where('status', '!=', 'draft')->avg('completion_percentage') ?: 0,
                'completed_this_month' => (clone $baseQuery)->where('status', 'completed')
                    ->whereMonth('updated_at', now()->month)
                    ->whereYear('updated_at', now()->year)
                    ->count()
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
    
    public function getDoctors(): JsonResponse
    {
        $user = auth()->user();

        // If the user is a doctor, only return themselves
        if ($user->role === 'doctor') {
            $doctors = collect([$user->only(['id', 'first_name', 'last_name', 'specialization', 'email'])]);
        } else {
            // For admin/superadmin, return all doctors
            $doctors = User::whereIn('role', ['doctor', 'admin', 'superadmin'])
                ->where('is_active', true)
                ->where('is_verified', true)
                ->select('id', 'first_name', 'last_name', 'specialization', 'email')
                ->orderBy('first_name')
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $doctors
        ]);
    }

    public function getPatients(): JsonResponse
    {
        $user = auth()->user();

        $query = User::where('role', 'patient')
            ->where('is_active', true)
            ->where('is_verified', true);

        // If the user is a doctor, only return patients they have care plans for
        if ($user->role === 'doctor') {
            $patientIds = CarePlan::where('doctor_id', $user->id)
                ->pluck('patient_id')
                ->unique();
            
            // Include all patients for now, but in a real scenario you might want to restrict this
            // $query->whereIn('id', $patientIds);
        }

        $patients = $query->select('id', 'first_name', 'last_name', 'email', 'phone', 'date_of_birth')
            ->orderBy('first_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $patients
        ]);
    }

    public function getNurses(): JsonResponse
    {
        $user = auth()->user();

        // If the user is a nurse, only return themselves
        if ($user->role === 'nurse') {
            $nurses = collect([$user->only(['id', 'first_name', 'last_name', 'specialization', 'years_experience', 'email'])]);
        } else {
            // For admin/superadmin/doctor, return all nurses
            $nurses = User::where('role', 'nurse')
                ->where('is_active', true)
                ->where('is_verified', true)
                ->select('id', 'first_name', 'last_name', 'specialization', 'years_experience', 'email')
                ->orderBy('first_name')
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $nurses
        ]);
    }

    /**
     * Export care plans data
     */
    public function export(Request $request)
    {
        try {
            $query = CarePlan::with(['patient', 'doctor', 'primaryNurse', 'secondaryNurse', 'createdBy', 'approvedBy']);

            // Apply filters
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('care_type') && $request->care_type !== 'all') {
                $query->where('care_type', $request->care_type);
            }

            if ($request->has('priority') && $request->priority !== 'all') {
                $query->where('priority', $request->priority);
            }

            // Search functionality
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('patient', function($pq) use ($search) {
                        $pq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
                });
            }

            $carePlans = $query->get();

            $filename = 'care_plans_export_' . date('Y-m-d_H-i-s') . '.csv';

            return response()->streamDownload(function () use ($carePlans) {
                $handle = fopen('php://output', 'w');
                
                // CSV Headers
                fputcsv($handle, [
                    'ID',
                    'Title',
                    'Patient Name',
                    'Patient Email',
                    'Doctor Name',
                    'Primary Nurse',
                    'Secondary Nurse',
                    'Care Type',
                    'Priority',
                    'Status',
                    'Start Date',
                    'End Date',
                    'Frequency',
                    'Completion %',
                    'Care Tasks Count',
                    'Estimated Hours/Day',
                    'Created By',
                    'Approved By',
                    'Approved At',
                    'Created At'
                ]);

                // CSV Data
                foreach ($carePlans as $plan) {
                    // Format care type
                    $careType = ucwords(str_replace('_', ' ', $plan->care_type));
                    
                    // Format priority
                    $priority = ucfirst($plan->priority);
                    
                    // Format status
                    $status = ucwords(str_replace('_', ' ', $plan->status));
                    
                    // Format frequency
                    $frequency = ucwords(str_replace('_', ' ', $plan->frequency));
                    
                    // Count care tasks
                    $tasksCount = is_array($plan->care_tasks) ? count($plan->care_tasks) : 0;
                    
                    // Get patient name
                    $patientName = $plan->patient 
                        ? $plan->patient->first_name . ' ' . $plan->patient->last_name 
                        : 'N/A';
                    
                    $patientEmail = $plan->patient ? $plan->patient->email : 'N/A';
                    
                    // Get doctor name
                    $doctorName = $plan->doctor 
                        ? 'Dr. ' . $plan->doctor->first_name . ' ' . $plan->doctor->last_name 
                        : 'N/A';
                    
                    // Get primary nurse name
                    $primaryNurse = $plan->primaryNurse 
                        ? $plan->primaryNurse->first_name . ' ' . $plan->primaryNurse->last_name 
                        : 'Not Assigned';
                    
                    // Get secondary nurse name
                    $secondaryNurse = $plan->secondaryNurse 
                        ? $plan->secondaryNurse->first_name . ' ' . $plan->secondaryNurse->last_name 
                        : 'Not Assigned';
                    
                    // Get created by name
                    $createdBy = $plan->createdBy 
                        ? $plan->createdBy->first_name . ' ' . $plan->createdBy->last_name 
                        : 'N/A';
                    
                    // Get approved by name
                    $approvedBy = $plan->approvedBy 
                        ? $plan->approvedBy->first_name . ' ' . $plan->approvedBy->last_name 
                        : 'N/A';

                    fputcsv($handle, [
                        $plan->id,
                        $plan->title,
                        $patientName,
                        $patientEmail,
                        $doctorName,
                        $primaryNurse,
                        $secondaryNurse,
                        $careType,
                        $priority,
                        $status,
                        $plan->start_date ? \Carbon\Carbon::parse($plan->start_date)->format('Y-m-d') : 'N/A',
                        $plan->end_date ? \Carbon\Carbon::parse($plan->end_date)->format('Y-m-d') : 'Ongoing',
                        $frequency,
                        $plan->completion_percentage . '%',
                        $tasksCount,
                        $plan->estimated_hours_per_day ?: 'N/A',
                        $createdBy,
                        $approvedBy,
                        $plan->approved_at ? \Carbon\Carbon::parse($plan->approved_at)->format('Y-m-d H:i:s') : 'N/A',
                        $plan->created_at->format('Y-m-d H:i:s')
                    ]);
                }

                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename={$filename}",
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to export care plans: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to export care plans',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }
}