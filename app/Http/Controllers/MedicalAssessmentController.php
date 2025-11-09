<?php

namespace App\Http\Controllers;

use App\Models\MedicalAssessment;
use App\Models\User;
use App\Models\CarePlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Mail\UserInvitationMail;
use Illuminate\Support\Facades\Mail;
use App\Notifications\UserInvitationNotification;

class MedicalAssessmentController extends Controller
{
    /**
     * Display a listing of medical assessments.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $query = MedicalAssessment::with(['patient', 'nurse'])->ordered();

            // Apply role-based filtering
            $this->applyRoleBasedFiltering($query, $user);

            // Apply additional filters
            if ($request->filled('patient_id')) {
                // Ensure user can access this patient's data
                if ($this->canAccessPatientData($user, $request->patient_id)) {
                    $query->forPatient($request->patient_id);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to access this patient\'s data.',
                    ], 403);
                }
            }

            if ($request->filled('nurse_id')) {
                // Only admins and the nurse themselves can filter by nurse
                if (in_array($user->role, ['admin', 'superadmin']) || 
                    ($user->role === 'nurse' && $user->id == $request->nurse_id)) {
                    $query->byNurse($request->nurse_id);
                }
            }

            if ($request->filled('status')) {
                $query->byStatus($request->status);
            }

            if ($request->filled('condition')) {
                $query->byCondition($request->condition);
            }

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->filled('recent_days')) {
                $query->recent($request->recent_days);
            }

            // Date filtering
            if ($request->filled('date_from') && $request->filled('date_to')) {
                $query->whereBetween('created_at', [
                    $request->date_from . ' 00:00:00',
                    $request->date_to . ' 23:59:59'
                ]);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $assessments = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $assessments->items(),
                'pagination' => [
                    'current_page' => $assessments->currentPage(),
                    'per_page' => $assessments->perPage(),
                    'total' => $assessments->total(),
                    'last_page' => $assessments->lastPage(),
                    'from' => $assessments->firstItem(),
                    'to' => $assessments->lastItem(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch medical assessments.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created medical assessment.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            // Only nurses and admins can create assessments
            if (!in_array($user->role, ['nurse', 'admin', 'superadmin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to create medical assessments.',
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
            $nurse = User::find($request->nurse_id);
            if (!$nurse || !in_array($nurse->role, ['nurse', 'admin', 'superadmin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid nurse selected or insufficient permissions.'
                ], 422);
            }

            // Nurses can only create assessments for themselves
            if ($user->role === 'nurse' && $user->id !== $request->nurse_id) {
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to create medical assessment.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified medical assessment.
     */
    public function show(MedicalAssessment $medicalAssessment): JsonResponse
    {
        try {
            $user = auth()->user();

            // Check if user can access this assessment
            if (!$this->canAccessAssessment($user, $medicalAssessment)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view this assessment.',
                ], 403);
            }

            $medicalAssessment->load(['patient', 'nurse']);

            return response()->json([
                'success' => true,
                'data' => $medicalAssessment
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch medical assessment.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update the specified medical assessment.
     */
    public function update(Request $request, MedicalAssessment $medicalAssessment): JsonResponse
    {
        try {
            $user = auth()->user();

            // Check if user can edit this assessment
            if (!$this->canEditAssessment($user, $medicalAssessment)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to edit this assessment.',
                ], 403);
            }

            // Validate the request
            $validator = Validator::make(
                $request->all(),
                MedicalAssessment::validationRules(true),
                MedicalAssessment::validationMessages()
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update the assessment
            $updateData = $request->only([
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

            $medicalAssessment->update($updateData);

            // Load relationships for response
            $medicalAssessment->load(['patient', 'nurse']);

            return response()->json([
                'success' => true,
                'message' => 'Medical assessment updated successfully.',
                'data' => $medicalAssessment
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update medical assessment.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified medical assessment.
     */
    public function destroy(MedicalAssessment $medicalAssessment): JsonResponse
    {
        try {
            $user = auth()->user();

            // Only admins can delete assessments
            if (!in_array($user->role, ['admin', 'superadmin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to delete assessments.',
                ], 403);
            }

            $medicalAssessment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Medical assessment deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete medical assessment.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get assessments for a specific patient.
     */
    public function getPatientAssessments(User $patient, Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if ($patient->role !== 'patient') {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not a patient.'
                ], 422);
            }

            // Check if user can access this patient's data
            if (!$this->canAccessPatientData($user, $patient->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to access this patient\'s data.',
                ], 403);
            }

            $query = MedicalAssessment::forPatient($patient->id)
                ->with(['nurse'])
                ->ordered();

            if ($request->filled('limit')) {
                $query->limit($request->limit);
            }

            $assessments = $query->get();

            return response()->json([
                'success' => true,
                'data' => $assessments,
                'patient' => [
                    'id' => $patient->id,
                    'name' => $patient->first_name . ' ' . $patient->last_name,
                    'ghana_card' => $patient->ghana_card_number
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch patient assessments.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get statistics for medical assessments.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $startDate = $request->get('start_date', Carbon::now()->subDays(30)->toDateString());
            $endDate = $request->get('end_date', Carbon::now()->toDateString());

            // Build base query with role-based filtering
            $baseQuery = MedicalAssessment::whereBetween('created_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ]);

            // Apply role-based filtering to statistics
            $this->applyRoleBasedFiltering($baseQuery, $user);

            $stats = [
                'total_assessments' => (clone $baseQuery)->count(),
                
                'assessments_by_condition' => (clone $baseQuery)
                    ->select('general_condition', DB::raw('count(*) as count'))
                    ->groupBy('general_condition')
                    ->pluck('count', 'general_condition'),
                
                'assessments_by_mobility' => (clone $baseQuery)
                    ->select('mobility_status', DB::raw('count(*) as count'))
                    ->groupBy('mobility_status')
                    ->pluck('count', 'mobility_status'),
                
                'patients_with_wounds' => (clone $baseQuery)->where('has_wounds', true)->count(),
                
                'average_pain_level' => (clone $baseQuery)->avg('pain_level'),
                
                'high_risk_patients' => (clone $baseQuery)->get()->filter(function ($assessment) {
                    return $assessment->getRiskLevel() === 'high';
                })->count(),
                
                'recent_assessments' => $this->getRecentAssessmentsForUser($user)
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Mark assessment as reviewed.
     */
    public function markReviewed(MedicalAssessment $medicalAssessment): JsonResponse
    {
        try {
            $user = auth()->user();

            // Only doctors and admins can mark as reviewed
            if (!in_array($user->role, ['doctor', 'admin', 'superadmin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to mark assessments as reviewed.',
                ], 403);
            }

            // Check if user can access this assessment
            if (!$this->canAccessAssessment($user, $medicalAssessment)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to access this assessment.',
                ], 403);
            }

            $medicalAssessment->markReviewed();

            return response()->json([
                'success' => true,
                'message' => 'Assessment marked as reviewed.',
                'data' => $medicalAssessment
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark assessment as reviewed.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Export assessments to CSV.
     */
    public function export(Request $request)
    {
        $user = auth()->user();
        
        // Build query with role-based filtering
        $query = MedicalAssessment::with(['patient', 'nurse'])->ordered();
        $this->applyRoleBasedFiltering($query, $user);

        // Apply filters similar to index method
        if ($request->filled('patient_id') && $this->canAccessPatientData($user, $request->patient_id)) {
            $query->forPatient($request->patient_id);
        }

        if ($request->filled('nurse_id')) {
            if (in_array($user->role, ['admin', 'superadmin']) || 
                ($user->role === 'nurse' && $user->id == $request->nurse_id)) {
                $query->byNurse($request->nurse_id);
            }
        }

        if ($request->filled('condition')) {
            $query->byCondition($request->condition);
        }

        $filename = 'medical_assessments_' . $user->role . '_' . date('Y-m-d_H-i-s') . '.csv';

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($handle, [
                'ID',
                'Patient Name',
                'Patient Ghana Card',
                'Nurse Name',
                'Physical Address',
                'Occupation',
                'Religion',
                'Emergency Contact 1',
                'Emergency Contact 1 Phone',
                'Presenting Condition',
                'Past Medical History',
                'Allergies',
                'Current Medications',
                'General Condition',
                'Hydration Status',
                'Nutrition Status',
                'Mobility Status',
                'Has Wounds',
                'Pain Level',
                'Temperature',
                'Pulse',
                'Blood Pressure',
                'SpO2',
                'Weight',
                'Risk Level',
                'Assessment Date',
                'Nurse Impression'
            ]);

            // Process in chunks
            $query->chunk(1000, function ($assessments) use ($handle) {
                foreach ($assessments as $assessment) {
                    $vitals = $assessment->initial_vitals ?? [];
                    
                    fputcsv($handle, [
                        $assessment->id,
                        $assessment->patient_name,
                        $assessment->patient->ghana_card_number ?? '',
                        $assessment->nurse_name,
                        $assessment->physical_address,
                        $assessment->occupation ?? '',
                        $assessment->religion ?? '',
                        $assessment->emergency_contact_1_name,
                        $assessment->emergency_contact_1_phone,
                        $assessment->presenting_condition,
                        $assessment->past_medical_history ?? '',
                        $assessment->allergies ?? '',
                        $assessment->current_medications ?? '',
                        $assessment->general_condition,
                        $assessment->hydration_status,
                        $assessment->nutrition_status,
                        $assessment->mobility_status,
                        $assessment->has_wounds ? 'Yes' : 'No',
                        $assessment->pain_level,
                        $vitals['temperature'] ?? '',
                        $vitals['pulse'] ?? '',
                        $vitals['blood_pressure'] ?? '',
                        $vitals['spo2'] ?? '',
                        $vitals['weight'] ?? '',
                        $assessment->getRiskLevel(),
                        $assessment->created_at->format('Y-m-d H:i:s'),
                        $assessment->initial_nursing_impression
                    ]);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Apply role-based filtering to medical assessment queries
     */
    private function applyRoleBasedFiltering($query, $user)
    {
        switch ($user->role) {
            case 'patient':
                // Patients can only see their own assessments
                $query->where('patient_id', $user->id);
                break;
            case 'nurse':
                // Nurses can see assessments they created or for patients they're assigned to
                $query->where(function($q) use ($user) {
                    $q->where('nurse_id', $user->id)
                      ->orWhereHas('patient', function($patientQuery) use ($user) {
                          $patientQuery->whereHas('carePlansAsPatient', function($carePlanQuery) use ($user) {
                              $carePlanQuery->where('primary_nurse_id', $user->id)
                                          ->orWhere('secondary_nurse_id', $user->id);
                          });
                      });
                });
                break;
            case 'doctor':
                // Doctors can see assessments for patients in their care plans
                $query->whereHas('patient', function($patientQuery) use ($user) {
                    $patientQuery->whereHas('carePlansAsPatient', function($carePlanQuery) use ($user) {
                        $carePlanQuery->where('doctor_id', $user->id);
                    });
                });
                break;
            case 'admin':
            case 'superadmin':
                // Admin and superadmin can see all assessments (no filtering)
                break;
        }
    }

    /**
     * Check if user can access a specific assessment
     */
    private function canAccessAssessment($user, $assessment): bool
    {
        switch ($user->role) {
            case 'patient':
                return $assessment->patient_id === $user->id;
            case 'nurse':
                // Can access if they created it or are assigned to the patient
                if ($assessment->nurse_id === $user->id) {
                    return true;
                }
                return $assessment->patient->carePlansAsPatient()
                    ->where(function($q) use ($user) {
                        $q->where('primary_nurse_id', $user->id)
                          ->orWhere('secondary_nurse_id', $user->id);
                    })->exists();
            case 'doctor':
                return $assessment->patient->carePlansAsPatient()
                    ->where('doctor_id', $user->id)->exists();
            case 'admin':
            case 'superadmin':
                return true;
            default:
                return false;
        }
    }

    /**
     * Check if user can edit a specific assessment
     */
    private function canEditAssessment($user, $assessment): bool
    {
        switch ($user->role) {
            case 'nurse':
                return $assessment->nurse_id === $user->id;
            case 'admin':
            case 'superadmin':
                return true;
            default:
                return false;
        }
    }

    /**
     * Check if user can access a specific patient's data
     */
    private function canAccessPatientData($user, $patientId): bool
    {
        switch ($user->role) {
            case 'patient':
                return $user->id == $patientId;
            case 'nurse':
                // Can access if assigned to patient's care plan
                return CarePlan::where('patient_id', $patientId)
                    ->where(function($q) use ($user) {
                        $q->where('primary_nurse_id', $user->id)
                          ->orWhere('secondary_nurse_id', $user->id);
                    })->exists();
            case 'doctor':
                return CarePlan::where('patient_id', $patientId)
                    ->where('doctor_id', $user->id)->exists();
            case 'admin':
            case 'superadmin':
                return true;
            default:
                return false;
        }
    }

    /**
     * Get recent assessments filtered by user role
     */
    private function getRecentAssessmentsForUser($user)
    {
        $query = MedicalAssessment::with(['patient', 'nurse'])
            ->orderBy('created_at', 'desc')
            ->limit(5);

        $this->applyRoleBasedFiltering($query, $user);

        return $query->get()->map(function ($assessment) {
            return [
                'id' => $assessment->id,
                'patient_name' => $assessment->patient_name,
                'nurse_name' => $assessment->nurse_name,
                'condition' => $assessment->general_condition,
                'created_at' => $assessment->created_at->format('Y-m-d H:i:s')
            ];
        });
    }

    /**
     * Create a new patient user.
     */
    private function createPatient(Request $request): User
    {
        // Generate email and password for the patient
        $firstName = $request->patient_first_name;
        $lastName = $request->patient_last_name;
        $email = $request->patient_email ?? $this->generatePatientEmail($firstName, $lastName);
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
            $patient->notify(new UserInvitationNotification($temporaryPassword));
        } catch (\Exception $e) {
            \Log::error('Failed to send invitation email: ' . $e->getMessage());
        }
        
        return $patient;
    }

    /**
     * Generate a unique email for the patient.
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
}