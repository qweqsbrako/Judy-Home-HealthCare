<?php
namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MedicalAssessment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Mail\UserInvitationMail;

class MedicalAssessmentController extends Controller
{
    /**
     * Get medical assessments for authenticated nurse
     * GET /api/mobile/medical-assessments
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $nurse = auth()->user();

            if ($nurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can access this endpoint.'
                ], 403);
            }

            $perPage = $request->input('per_page', 15);

            $assessments = MedicalAssessment::where('nurse_id', $nurse->id)
                ->with(['patient:id,first_name,last_name', 'nurse:id,first_name,last_name'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $transformedData = $assessments->getCollection()->map(function($assessment) {
                return [
                    'id' => $assessment->id,
                    'patient' => $assessment->patient ? [
                        'id' => $assessment->patient->id,
                        'name' => $assessment->patient->first_name . ' ' . $assessment->patient->last_name,
                    ] : null,
                    'assessment_status' => $assessment->assessment_status,
                    'presenting_condition' => $assessment->presenting_condition,
                    'general_condition' => $assessment->general_condition,
                    'created_at' => $assessment->created_at->toIso8601String(),
                ];
            });

            $assessments->setCollection($transformedData);

            return response()->json([
                'success' => true,
                'data' => $assessments->items(),
                'pagination' => [
                    'current_page' => $assessments->currentPage(),
                    'last_page' => $assessments->lastPage(),
                    'per_page' => $assessments->perPage(),
                    'total' => $assessments->total(),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching medical assessments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch medical assessments.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get a specific medical assessment
     * GET /api/mobile/medical-assessments/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $nurse = auth()->user();

            if ($nurse->role !== 'nurse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only nurses can access this endpoint.'
                ], 403);
            }

            $assessment = MedicalAssessment::with(['patient', 'nurse'])
                ->where('id', $id)
                ->where('nurse_id', $nurse->id)
                ->first();

            if (!$assessment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Medical assessment not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $assessment
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching medical assessment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch medical assessment.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Create a new medical assessment
     * POST /api/mobile/medical-assessments
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $nurse = auth()->user();

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
                    'message' => 'Invalid nurse selected.'
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

                // Create new patient if needed
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to create medical assessment.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Helper method to create a new patient user
     */
    private function createPatient(Request $request): User
    {
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
            Mail::to($email)->send(new UserInvitationMail($patient, $temporaryPassword));
        } catch (\Exception $e) {
            \Log::error('Failed to send invitation email: ' . $e->getMessage());
        }

        return $patient;
    }

    /**
     * Generate a unique email for the patient
     */
    private function generatePatientEmail(string $firstName, string $lastName): string
    {
        $baseEmail = strtolower($firstName . '.' . $lastName);
        $domain = '@patient.judyhomecare.com';
        
        $email = $baseEmail . $domain;
        $counter = 1;
        
        while (User::where('email', $email)->exists()) {
            $email = $baseEmail . $counter . $domain;
            $counter++;
        }
        
        return $email;
    }
}