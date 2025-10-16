<?php

namespace App\Http\Controllers;

use App\Models\CareAssignment;
use App\Models\CarePlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CareAssignmentController extends Controller
{
    /**
     * Display a listing of care assignments with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = CareAssignment::with([
                'carePlan:id,title,care_type,priority,patient_id', 
                'patient:id,first_name,last_name,email,phone', 
                'primaryNurse:id,first_name,last_name,email,years_experience,specialization', 
                'secondaryNurse:id,first_name,last_name,email,years_experience,specialization', 
                'assignedBy:id,first_name,last_name',
                'approvedBy:id,first_name,last_name'
            ])->latest();

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Filter by assignment type
            if ($request->has('assignment_type') && $request->assignment_type !== 'all') {
                $query->where('assignment_type', $request->assignment_type);
            }

            // Filter by nurse
            if ($request->has('nurse_id') && $request->nurse_id !== 'all') {
                $query->where(function($q) use ($request) {
                    $q->where('primary_nurse_id', $request->nurse_id)
                      ->orWhere('secondary_nurse_id', $request->nurse_id);
                });
            }

            // Filter by patient
            if ($request->has('patient_id') && $request->patient_id !== 'all') {
                $query->where('patient_id', $request->patient_id);
            }

            // Filter by priority
            if ($request->has('priority_level') && $request->priority_level !== 'all') {
                $query->where('priority_level', $request->priority_level);
            }

            // Filter by emergency
            if ($request->has('is_emergency') && $request->is_emergency !== 'all') {
                $query->where('is_emergency', $request->is_emergency === 'true');
            }

            // Search functionality
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('assignment_notes', 'like', "%{$search}%")
                      ->orWhereHas('patient', function($pq) use ($search) {
                          $pq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                      })
                      ->orWhereHas('primaryNurse', function($nq) use ($search) {
                          $nq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('carePlan', function($cq) use ($search) {
                          $cq->where('title', 'like', "%{$search}%");
                      });
                });
            }

            // Date range filter
            if ($request->has('start_date') && $request->start_date) {
                $query->where('start_date', '>=', $request->start_date);
            }

            if ($request->has('end_date') && $request->end_date) {
                $query->where('start_date', '<=', $request->end_date);
            }

            $assignments = $query->paginate($request->get('per_page', 15));

            // Add avatar URLs for nurses
            $assignments->getCollection()->transform(function ($assignment) {
                if ($assignment->primaryNurse) {
                    $assignment->primaryNurse->avatar_url = $this->generateAvatarUrl($assignment->primaryNurse);
                }
                if ($assignment->secondaryNurse) {
                    $assignment->secondaryNurse->avatar_url = $this->generateAvatarUrl($assignment->secondaryNurse);
                }
                return $assignment;
            });

            return response()->json([
                'success' => true,
                'data' => $assignments,
                'filters' => [
                    'statuses' => CareAssignment::getStatuses(),
                    'assignment_types' => CareAssignment::getAssignmentTypes(),
                    'intensity_levels' => CareAssignment::getIntensityLevels(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading care assignments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load assignments. Please try again.'
            ], 500);
        }
    }

    /**
     * Store a newly created care assignment
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'care_plan_id' => 'required|exists:care_plans,id',
                'primary_nurse_id' => 'required|exists:users,id',
                'secondary_nurse_id' => 'nullable|exists:users,id|different:primary_nurse_id',
                'assignment_type' => ['required', Rule::in(array_keys(CareAssignment::getAssignmentTypes()))],
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'nullable|date|after:start_date',
                'assignment_notes' => 'nullable|string|max:1000',
                'special_requirements' => 'nullable|string|max:1000',
                'estimated_hours_per_day' => 'nullable|integer|min:1|max:24',
                'total_estimated_hours' => 'nullable|integer|min:1',
                'intensity_level' => ['required', Rule::in(array_keys(CareAssignment::getIntensityLevels()))],
                'priority_level' => 'required|in:low,medium,high,urgent',
                'is_emergency' => 'boolean',
            ]);

            // Verify care plan exists and is approved
            $carePlan = CarePlan::findOrFail($validated['care_plan_id']);
            if (!in_array($carePlan->status, ['approved', 'active'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Care plan must be approved before assignment.',
                ], 422);
            }

            // Verify nurses are actually nurses
            $primaryNurse = User::findOrFail($validated['primary_nurse_id']);
            if ($primaryNurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Primary nurse must have nurse role.',
                ], 422);
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

            // Check if nurses meet care plan requirements (if method exists)
            if (method_exists($carePlan, 'matchesNurseQualifications') && !$carePlan->matchesNurseQualifications($primaryNurse)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Primary nurse does not meet care plan requirements.',
                ], 422);
            }

            // Auto-populate assignment data
            $validated['patient_id'] = $carePlan->patient_id;
            $validated['assigned_by'] = auth()->id();
            $validated['assigned_at'] = now();
            $validated['status'] = 'pending';

            // Set emergency priority if needed
            if ($validated['is_emergency'] ?? false) {
                $validated['priority_level'] = 'urgent';
                $validated['emergency_assigned_at'] = now();
            }

            $assignment = CareAssignment::create($validated);

            // Calculate match scores if method exists
            if (method_exists($assignment, 'calculateMatchScore')) {
                $assignment->calculateMatchScore($primaryNurse);
            }

            $assignment->load([
                'carePlan:id,title,care_type,priority,patient_id', 
                'patient:id,first_name,last_name,email,phone', 
                'primaryNurse:id,first_name,last_name,email,years_experience,specialization', 
                'secondaryNurse:id,first_name,last_name,email,years_experience,specialization', 
                'assignedBy:id,first_name,last_name'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Care assignment created successfully.',
                'data' => $assignment
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating care assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create assignment. Please try again.'
            ], 500);
        }
    }

    /**
     * Display the specified care assignment
     */
    public function show(CareAssignment $careAssignment): JsonResponse
    {
        try {
            $careAssignment->load([
                'carePlan:id,title,care_type,priority,patient_id',
                'patient:id,first_name,last_name,email,phone,date_of_birth',
                'primaryNurse:id,first_name,last_name,email,years_experience,specialization',
                'secondaryNurse:id,first_name,last_name,email,years_experience,specialization',
                'assignedBy:id,first_name,last_name',
                'approvedBy:id,first_name,last_name'
            ]);

            // Add avatar URLs
            if ($careAssignment->primaryNurse) {
                $careAssignment->primaryNurse->avatar_url = $this->generateAvatarUrl($careAssignment->primaryNurse);
            }
            if ($careAssignment->secondaryNurse) {
                $careAssignment->secondaryNurse->avatar_url = $this->generateAvatarUrl($careAssignment->secondaryNurse);
            }

            return response()->json([
                'success' => true,
                'data' => $careAssignment
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading care assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load assignment details.'
            ], 500);
        }
    }

    /**
     * Update the specified care assignment
     */
    public function update(Request $request, CareAssignment $careAssignment): JsonResponse
    {
        try {
            // Only allow updates for certain statuses
            if (!in_array($careAssignment->status, ['pending', 'nurse_review', 'on_hold'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot modify assignment in current status.',
                ], 422);
            }

            $validated = $request->validate([
                'secondary_nurse_id' => 'nullable|exists:users,id|different:primary_nurse_id',
                'assignment_notes' => 'nullable|string|max:1000',
                'special_requirements' => 'nullable|string|max:1000',
                'start_date' => 'sometimes|date|after_or_equal:today',
                'end_date' => 'nullable|date|after:start_date',
                'estimated_hours_per_day' => 'nullable|integer|min:1|max:24',
                'total_estimated_hours' => 'nullable|integer|min:1',
                'intensity_level' => ['sometimes', Rule::in(array_keys(CareAssignment::getIntensityLevels()))],
                'priority_level' => 'sometimes|in:low,medium,high,urgent',
            ]);

            $careAssignment->update($validated);
            $careAssignment->load([
                'carePlan:id,title,care_type,priority,patient_id', 
                'patient:id,first_name,last_name,email,phone', 
                'primaryNurse:id,first_name,last_name,email,years_experience,specialization', 
                'secondaryNurse:id,first_name,last_name,email,years_experience,specialization'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assignment updated successfully.',
                'data' => $careAssignment
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating care assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update assignment. Please try again.'
            ], 500);
        }
    }

    /**
     * Remove the specified care assignment
     */
    public function destroy(CareAssignment $careAssignment): JsonResponse
    {
        try {
            // Only allow deletion if not active
            if (in_array($careAssignment->status, ['active', 'completed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete active or completed assignments.',
                ], 422);
            }

            $careAssignment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Assignment deleted successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting care assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete assignment. Please try again.'
            ], 500);
        }
    }

    /**
     * Approve a pending care assignment
     */
    public function approve(CareAssignment $careAssignment): JsonResponse
    {
        try {
            // Only allow approval for pending assignments
            if ($careAssignment->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignment must be in pending status to approve.',
                ], 422);
            }

            // Check if user has permission to approve
            $user = auth()->user();
            if (!in_array($user->role, ['admin', 'superadmin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to approve assignments.',
                ], 403);
            }

            // Update assignment status to nurse_review (ready for nurse to accept/decline)
            $careAssignment->update([
                'status' => 'nurse_review',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // TODO: Send notification to the assigned nurse(s)
            // $this->notifyNurseOfAssignment($careAssignment);

            $careAssignment->load([
                'carePlan:id,title,care_type,priority,patient_id', 
                'patient:id,first_name,last_name,email,phone', 
                'primaryNurse:id,first_name,last_name,email,years_experience,specialization', 
                'secondaryNurse:id,first_name,last_name,email,years_experience,specialization',
                'approvedBy:id,first_name,last_name'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assignment approved successfully.',
                'data' => $careAssignment
            ]);
        } catch (\Exception $e) {
            Log::error('Error approving care assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve assignment. Please try again.'
            ], 500);
        }
    }

    /**
     * Accept a care assignment (nurse action)
     */
    public function accept(Request $request, CareAssignment $careAssignment): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nurse_response_notes' => 'nullable|string|max:1000'
            ]);

            $nurse = auth()->user();
            
            // Check if nurse has permission
            if ($nurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can accept assignments.',
                ], 403);
            }

            // Check if this nurse is assigned to this assignment
            if ($nurse->id !== $careAssignment->primary_nurse_id && $nurse->id !== $careAssignment->secondary_nurse_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to this care plan.',
                ], 422);
            }

            // Check assignment status
            if (!in_array($careAssignment->status, ['nurse_review', 'approved'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignment is not available for acceptance.',
                ], 422);
            }

            // Update assignment
            $careAssignment->update([
                'status' => 'accepted',
                'nurse_response_notes' => $validated['nurse_response_notes'] ?? null,
                'nurse_responded_at' => now(),
                'response_time_hours' => $careAssignment->approved_at ? 
                    Carbon::parse($careAssignment->approved_at)->diffInHours(now()) : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assignment accepted successfully.',
                'data' => $careAssignment->fresh(['carePlan', 'patient', 'primaryNurse'])
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error accepting care assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept assignment. Please try again.'
            ], 500);
        }
    }

    /**
     * Decline a care assignment (nurse action)
     */
    public function decline(Request $request, CareAssignment $careAssignment): JsonResponse
    {
        try {
            $validated = $request->validate([
                'decline_reason' => 'required|string|max:1000'
            ]);

            $nurse = auth()->user();
            
            // Check if nurse has permission
            if ($nurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can decline assignments.',
                ], 403);
            }

            // Check if this nurse is assigned to this assignment
            if ($nurse->id !== $careAssignment->primary_nurse_id && $nurse->id !== $careAssignment->secondary_nurse_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to this care plan.',
                ], 422);
            }

            // Check assignment status
            if (!in_array($careAssignment->status, ['nurse_review', 'approved'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignment is not available for declining.',
                ], 422);
            }

            // Update assignment
            $careAssignment->update([
                'status' => 'declined',
                'decline_reason' => $validated['decline_reason'],
                'nurse_responded_at' => now(),
                'response_time_hours' => $careAssignment->approved_at ? 
                    Carbon::parse($careAssignment->approved_at)->diffInHours(now()) : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assignment declined.',
                'data' => $careAssignment->fresh()
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error declining care assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to decline assignment. Please try again.'
            ], 500);
        }
    }

    /**
     * Activate a care assignment
     */
    public function activate(CareAssignment $careAssignment): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Check permissions first
            if (!in_array($user->role, ['admin', 'superadmin', 'nurse'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to activate assignments.',
                ], 403);
            }
            
            // Different rules for admins vs nurses
            if (in_array($user->role, ['admin', 'superadmin'])) {
                // Admins can activate from pending, nurse_review, or accepted status
                if (!in_array($careAssignment->status, ['pending', 'nurse_review', 'accepted'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Assignment cannot be activated from current status.',
                    ], 422);
                }
                
                // If activating from pending/nurse_review, automatically mark as admin override
                if (in_array($careAssignment->status, ['pending', 'nurse_review'])) {
                    $careAssignment->update([
                        'status' => 'active',
                        'activated_at' => now(),
                        'activated_by' => auth()->id(),
                        'admin_override' => true,
                        'admin_override_reason' => 'Administrative activation without nurse review',
                        'admin_override_at' => now(),
                    ]);
                } else {
                    // Normal activation from accepted status
                    $careAssignment->update([
                        'status' => 'active',
                        'activated_at' => now(),
                        'activated_by' => auth()->id(),
                    ]);
                }
            } else {
                // Nurses can only activate if they've accepted
                if ($careAssignment->status !== 'accepted') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Assignment must be accepted before activation.',
                    ], 422);
                }
                
                // Check if this nurse is assigned
                if ($user->id !== $careAssignment->primary_nurse_id && $user->id !== $careAssignment->secondary_nurse_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are not assigned to this care plan.',
                    ], 422);
                }
                
                $careAssignment->update([
                    'status' => 'active',
                    'activated_at' => now(),
                    'activated_by' => auth()->id(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Assignment activated successfully.',
                'data' => $careAssignment
            ]);
        } catch (\Exception $e) {
            Log::error('Error activating care assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate assignment. Please try again.'
            ], 500);
        }
    }

    /**
     * Complete a care assignment
     */
    public function complete(CareAssignment $careAssignment): JsonResponse
    {
        try {
            // Check if assignment can be completed
            if ($careAssignment->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only active assignments can be completed.',
                ], 422);
            }

            $careAssignment->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completed_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assignment completed successfully.',
                'data' => $careAssignment
            ]);
        } catch (\Exception $e) {
            Log::error('Error completing care assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete assignment. Please try again.'
            ], 500);
        }
    }

    /**
     * Reassign a care assignment to a new nurse
     */
    public function reassign(Request $request, CareAssignment $careAssignment): JsonResponse
    {
        try {
            $validated = $request->validate([
                'new_nurse_id' => 'required|exists:users,id',
                'reassignment_reason' => 'required|string|max:1000',
                'assignment_notes' => 'nullable|string|max:1000'
            ]);

            // Check permissions
            $user = auth()->user();
            if (!in_array($user->role, ['admin', 'superadmin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to reassign assignments.',
                ], 403);
            }

            // Check if assignment can be reassigned
            if (!in_array($careAssignment->status, ['pending', 'nurse_review', 'declined', 'active'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignment cannot be reassigned in current status.',
                ], 422);
            }

            $newNurse = User::findOrFail($validated['new_nurse_id']);
            
            if ($newNurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'New assignee must be a nurse.',
                ], 422);
            }

            // Check if it's the same nurse
            if ($newNurse->id === $careAssignment->primary_nurse_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot reassign to the same nurse.',
                ], 422);
            }

            // Check if nurse meets care plan requirements (if method exists)
            if (method_exists($careAssignment->carePlan, 'matchesNurseQualifications') && 
                !$careAssignment->carePlan->matchesNurseQualifications($newNurse)) {
                return response()->json([
                    'success' => false,
                    'message' => 'New nurse does not meet care plan requirements.',
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Store the previous assignment details for audit
                $previousNurseId = $careAssignment->primary_nurse_id;
                
                // Update the assignment
                $careAssignment->update([
                    'primary_nurse_id' => $newNurse->id,
                    'status' => 'pending', // Reset to pending for new approval workflow
                    'assignment_notes' => $validated['assignment_notes'] ?? $careAssignment->assignment_notes,
                    'reassignment_reason' => $validated['reassignment_reason'],
                    'reassigned_by' => auth()->id(),
                    'reassigned_at' => now(),
                    'previous_nurse_id' => $previousNurseId,
                    // Reset approval/response fields
                    'approved_by' => null,
                    'approved_at' => null,
                    'nurse_responded_at' => null,
                    'nurse_response_notes' => null,
                    'decline_reason' => null,
                ]);

                // Recalculate match scores if method exists
                if (method_exists($careAssignment, 'calculateMatchScore')) {
                    $careAssignment->calculateMatchScore($newNurse);
                }

                DB::commit();

                $careAssignment->load([
                    'carePlan:id,title,care_type,priority,patient_id', 
                    'patient:id,first_name,last_name,email,phone', 
                    'primaryNurse:id,first_name,last_name,email,years_experience,specialization'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Assignment reassigned successfully.',
                    'data' => $careAssignment
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
            Log::error('Error reassigning care assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reassign assignment. Please try again.'
            ], 500);
        }
    }

    /**
     * Find suitable nurses for a care plan
     */
    public function findSuitableNurses(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'care_plan_id' => 'required|exists:care_plans,id',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after:start_date',
            ]);

            $carePlan = CarePlan::with('patient')->findOrFail($validated['care_plan_id']);
            
            // Get all nurses who meet the basic qualifications
            $qualifiedNurses = User::where('role', 'nurse')
                ->where('is_active', true)
                ->where('is_verified', true)
                ->when(isset($carePlan->min_years_experience), function ($query) use ($carePlan) {
                    return $query->where('years_experience', '>=', $carePlan->min_years_experience);
                })
                ->get();

            $suitableNurses = $qualifiedNurses->map(function($nurse) use ($carePlan, $validated) {
                // Calculate basic match scores
                $skillMatch = $this->calculateSkillMatch($nurse, $carePlan);
                $locationMatch = $this->calculateLocationMatch($nurse, $carePlan);
                $availabilityMatch = $this->calculateAvailabilityMatch($nurse, $validated);
                $workloadBalance = $this->calculateWorkloadBalance($nurse);
                
                $overallScore = ($skillMatch + $locationMatch + $availabilityMatch + $workloadBalance) / 4;
                
                $currentAssignments = CareAssignment::where('primary_nurse_id', $nurse->id)
                    ->whereIn('status', ['active', 'accepted'])
                    ->count();
                
                return [
                    'nurse' => $nurse->only(['id', 'first_name', 'last_name', 'email', 'specialization', 'years_experience']),
                    'match_scores' => [
                        'skill_match' => round($skillMatch, 1),
                        'location_match' => round($locationMatch, 1),
                        'availability_match' => round($availabilityMatch, 1),
                        'workload_balance' => round($workloadBalance, 1),
                        'overall_match' => round($overallScore, 1),
                    ],
                    'current_assignments' => $currentAssignments,
                ];
            })
            ->filter(function($nurseData) {
                return $nurseData['match_scores']['overall_match'] >= 50; // Minimum 50% match
            })
            ->sortByDesc(function($nurseData) {
                return $nurseData['match_scores']['overall_match'];
            })
            ->values();

            return response()->json([
                'success' => true,
                'data' => $suitableNurses,
                'total_qualified' => $suitableNurses->count()
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error finding suitable nurses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to find suitable nurses. Please try again.'
            ], 500);
        }
    }

    /**
     * Get assignment statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $stats = [
                'total_assignments' => CareAssignment::count(),
                'active_assignments' => CareAssignment::where('status', 'active')->count(),
                'pending_assignments' => CareAssignment::whereIn('status', ['pending', 'nurse_review'])->count(),
                'completed_assignments' => CareAssignment::where('status', 'completed')->count(),
                'emergency_assignments' => CareAssignment::where('is_emergency', true)->count(),
                'by_status' => CareAssignment::select('status')
                    ->selectRaw('count(*) as count')
                    ->groupBy('status')
                    ->get()
                    ->mapWithKeys(function($item) {
                        $statuses = CareAssignment::getStatuses();
                        $label = $statuses[$item->status] ?? ucfirst(str_replace('_', ' ', $item->status));
                        return [$label => $item->count];
                    }),
                'by_intensity' => CareAssignment::select('intensity_level')
                    ->selectRaw('count(*) as count')
                    ->whereNotNull('intensity_level')
                    ->groupBy('intensity_level')
                    ->get()
                    ->mapWithKeys(function($item) {
                        $intensityLevels = CareAssignment::getIntensityLevels();
                        $label = $intensityLevels[$item->intensity_level] ?? ucfirst(str_replace('_', ' ', $item->intensity_level));
                        return [$label => $item->count];
                    }),
                'average_match_score' => CareAssignment::whereNotNull('overall_match_score')
                    ->avg('overall_match_score'),
                'response_times' => [
                    'average_hours' => CareAssignment::whereNotNull('response_time_hours')
                        ->avg('response_time_hours'),
                    'acceptance_rate' => CareAssignment::count() > 0 ? 
                        (CareAssignment::whereIn('status', ['accepted', 'active', 'completed'])->count() / CareAssignment::count() * 100) : 0
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading assignment statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load statistics.'
            ], 500);
        }
    }

    /**
     * Get available nurses for assignments
     */
    public function getNurses(): JsonResponse
    {
        try {
            $nurses = User::where('role', 'nurse')
                ->where('is_active', true)
                ->where('is_verified', true)
                ->select('id', 'first_name', 'last_name', 'specialization', 'years_experience', 'email')
                ->orderBy('first_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $nurses
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading nurses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load nurses.'
            ], 500);
        }
    }

    /**
     * Get approved care plans available for assignment
     */
    public function getApprovedCarePlans(): JsonResponse
    {
        try {
            $carePlans = CarePlan::whereIn('status', ['approved', 'active'])
                ->with('patient:id,first_name,last_name')
                ->select('id', 'title', 'patient_id', 'care_type', 'priority')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $carePlans
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading care plans: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load care plans.'
            ], 500);
        }
    }

    /**
     * Helper method to generate avatar URL
     */
    private function generateAvatarUrl($user)
    {
        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&color=667eea&background=f8f9fa&size=200&font-size=0.6";
    }

    /**
     * Calculate skill match score
     */
    private function calculateSkillMatch($nurse, $carePlan): float
    {
        $score = 70; // Base score
        
        // Add points for relevant specialization
        if ($nurse->specialization && $carePlan->care_type) {
            $nurseSpec = strtolower($nurse->specialization);
            $careType = strtolower($carePlan->care_type);
            
            if (str_contains($nurseSpec, $careType) || str_contains($careType, $nurseSpec)) {
                $score += 20;
            }
        }
        
        // Add points for experience
        $experience = $nurse->years_experience ?? 0;
        if ($experience >= 5) $score += 10;
        if ($experience >= 10) $score += 5;
        
        return min($score, 100);
    }

    /**
     * Calculate location match score
     */
    private function calculateLocationMatch($nurse, $carePlan): float
    {
        // TODO: Implement based on actual location data
        // For now, return a random score between 60-90
        return rand(60, 90);
    }

    /**
     * Calculate availability match score
     */
    private function calculateAvailabilityMatch($nurse, $dateRange): float
    {
        // TODO: Check nurse's schedule/availability
        // For now, return a score based on current workload
        $currentAssignments = CareAssignment::where('primary_nurse_id', $nurse->id)
            ->whereIn('status', ['active', 'accepted'])
            ->count();
        
        if ($currentAssignments == 0) return 100;
        if ($currentAssignments <= 2) return 80;
        if ($currentAssignments <= 4) return 60;
        return 40;
    }

    /**
     * Calculate workload balance score
     */
    private function calculateWorkloadBalance($nurse): float
    {
        $activeAssignments = CareAssignment::where('primary_nurse_id', $nurse->id)
            ->whereIn('status', ['active', 'accepted'])
            ->count();
        
        // Ideal workload is 2-3 assignments
        if ($activeAssignments <= 1) return 85; // Could take more
        if ($activeAssignments <= 3) return 100; // Perfect load
        if ($activeAssignments <= 5) return 70; // Getting busy
        return 40; // Overloaded
    }
}