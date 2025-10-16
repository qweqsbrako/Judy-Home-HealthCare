<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
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
     * Display a listing of patients
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::query()
                ->where('role', 'patient')
                ->with(['roleModel', 'verifier']);

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('ghana_card_number', 'like', "%{$search}%")
                    ->orWhere('emergency_contact_name', 'like', "%{$search}%")
                    ->orWhere('emergency_contact_phone', 'like', "%{$search}%");
                });
            }

            // Status filter
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('verification_status', $request->status);
            }

            // Active filter
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Gender filter
            if ($request->has('gender') && $request->gender !== 'all') {
                $query->where('gender', $request->gender);
            }

            // Age group filter
            if ($request->has('age_group') && $request->age_group !== 'all') {
                switch ($request->age_group) {
                    case 'child':
                        $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18');
                        break;
                    case 'adult':
                        $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 64');
                        break;
                    case 'senior':
                        $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= 65');
                        break;
                }
            }

            // Sorting
            $sortField = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            
            $allowedSortFields = ['first_name', 'last_name', 'email', 'verification_status', 'created_at', 'last_login_at', 'date_of_birth'];
            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortDirection);
            }

            // Calculate statistics BEFORE pagination
            $totalPatients = User::where('role', 'patient')->count();
            $verifiedCount = User::where('role', 'patient')
                ->where('verification_status', 'verified')
                ->count();
            $pendingCount = User::where('role', 'patient')
                ->where('verification_status', 'pending')
                ->count();
            
            // Active today - logged in within last 24 hours
            $activeTodayCount = User::where('role', 'patient')
                ->where('last_login_at', '>=', now()->subDay())
                ->count();
            
            // Calculate percentage
            $verifiedPercentage = $totalPatients > 0 
                ? round(($verifiedCount / $totalPatients) * 100, 1) 
                : 0;

            // Pagination
            $perPage = $request->get('per_page', 15);
            $patients = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => UserResource::collection($patients->items()),
                'meta' => [
                    'current_page' => $patients->currentPage(),
                    'last_page' => $patients->lastPage(),
                    'per_page' => $patients->perPage(),
                    'total' => $patients->total(),
                ],
                'stats' => [
                    'total_patients' => $totalPatients,
                    'verified_count' => $verifiedCount,
                    'pending_count' => $pendingCount,
                    'active_today_count' => $activeTodayCount,
                    'verified_percentage' => $verifiedPercentage,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch patients: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch patients',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Store a newly created patient
     *
     * @param Request $request
     * @return JsonResponse
     */
    /**
     * Store a newly created patient
     */
    public function store(Request $request): JsonResponse
    {
        \Log::info("Store Patient");
        \Log::info($request->all());
        
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'nullable|date|before:today',
            'ghana_card_number' => 'nullable|string|unique:users,ghana_card_number',
            
            // Photo validation
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            
            // Emergency contact
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            
            // Medical information
            'medical_conditions' => 'nullable|string',
            'allergies' => 'nullable|string',
            'current_medications' => 'nullable|string',
            
            // Authentication setup
            'send_invite' => 'boolean',
            'password' => 'nullable|string|min:8',
        ]);

        $validator->sometimes('password', 'required', function ($input) {
            return !$input->send_invite;
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $patientData = $request->only([
                'first_name', 'last_name', 'email', 'phone', 'gender',
                'date_of_birth', 'ghana_card_number', 'emergency_contact_name', 
                'emergency_contact_phone'
            ]);

            // Handle photo upload
            if ($request->hasFile('photo')) {
                $photoPath = $this->handlePhotoUpload($request->file('photo'));
                $patientData['avatar'] = $photoPath;
            }

            // Force role to patient
            $patientData['role'] = 'patient';

            // Handle medical information as JSON arrays
            $patientData['medical_conditions'] = $this->parseCommaSeparatedString($request->medical_conditions);
            $patientData['allergies'] = $this->parseCommaSeparatedString($request->allergies);
            $patientData['current_medications'] = $this->parseCommaSeparatedString($request->current_medications);

            // Set password or generate temporary one
            if ($request->send_invite) {
                $temporaryPassword = Str::random(12);
                $patientData['password'] = Hash::make($temporaryPassword);
                $patientData['force_password_change'] = true;
            } else {
                $patientData['password'] = Hash::make($request->password);
                $patientData['force_password_change'] = false;
            }

            $patientData['registered_ip'] = $request->ip();
            $patientData['verification_status'] = 'verified';
            $patientData['is_verified'] = true;
            $patientData['is_active'] = true;
            $patientData['verified_at'] = now();
            $patientData['verified_by'] = auth()->id();

            $patient = User::create($patientData);

            // Send invitation email if requested
            if ($request->send_invite) {
                try {
                    Mail::to($patient->email)->send(new UserInvitationMail($patient, $temporaryPassword));
                } catch (\Exception $e) {
                    \Log::error('Failed to send invitation email: ' . $e->getMessage());
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $request->send_invite 
                    ? 'Patient created successfully and invitation email sent'
                    : 'Patient created successfully',
                'data' => new UserResource($patient->fresh(['roleModel', 'verifier']))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create patient',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Display the specified patient
     *
     * @param User $patient
     * @return JsonResponse
     */
    public function show(User $patient): JsonResponse
    {
        if ($patient->role !== 'patient') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a patient'
            ], 404);
        }

        try {
            $patient->load(['roleModel', 'verifier']);
            
            return response()->json([
                'success' => true,
                'data' => new UserResource($patient)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch patient details',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Update the specified patient
     *
     * @param Request $request
     * @param User $patient
     * @return JsonResponse
     */
    /**
     * Update the specified patient
     */
    public function update(Request $request, User $patient): JsonResponse
    {
        if ($patient->role !== 'patient') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a patient'
            ], 404);
        }

        \Log::info("Update Patient");
        \Log::info($request->all());

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $patient->id,
            'phone' => 'sometimes|required|string|max:20',
            'gender' => 'sometimes|required|in:male,female,other',
            'date_of_birth' => 'sometimes|nullable|date|before:today',
            'ghana_card_number' => 'sometimes|nullable|string|unique:users,ghana_card_number,' . $patient->id,
            
            // Photo validation
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'remove_photo' => 'nullable|boolean',
            
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'medical_conditions' => 'nullable|string',
            'allergies' => 'nullable|string',
            'current_medications' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'verification_status' => 'sometimes|in:pending,verified,rejected,suspended',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $updateData = $request->only([
                'first_name', 'last_name', 'email', 'phone', 'gender',
                'date_of_birth', 'ghana_card_number', 'emergency_contact_name',
                'emergency_contact_phone', 'is_active'
            ]);

            // Handle photo upload
            if ($request->hasFile('photo')) {
                if ($patient->avatar) {
                    $this->deletePhoto($patient->avatar);
                }
                
                $photoPath = $this->handlePhotoUpload($request->file('photo'));
                $updateData['avatar'] = $photoPath;
            }

            // Handle photo removal
            if ($request->has('remove_photo') && $request->remove_photo) {
                if ($patient->avatar) {
                    $this->deletePhoto($patient->avatar);
                }
                $updateData['avatar'] = null;
            }

            // Handle medical information updates
            if ($request->has('medical_conditions')) {
                $updateData['medical_conditions'] = $this->parseCommaSeparatedString($request->medical_conditions);
            }
            if ($request->has('allergies')) {
                $updateData['allergies'] = $this->parseCommaSeparatedString($request->allergies);
            }
            if ($request->has('current_medications')) {
                $updateData['current_medications'] = $this->parseCommaSeparatedString($request->current_medications);
            }

            // Handle verification status updates
            if ($request->has('verification_status')) {
                $updateData['verification_status'] = $request->verification_status;
                
                if ($request->verification_status === 'verified') {
                    $updateData['is_verified'] = true;
                    $updateData['verified_by'] = auth()->id();
                    $updateData['verified_at'] = now();
                } elseif (in_array($request->verification_status, ['rejected', 'suspended'])) {
                    $updateData['is_verified'] = false;
                    $updateData['verified_by'] = auth()->id();
                    $updateData['verified_at'] = now();
                }
                
                if ($request->has('verification_notes')) {
                    $updateData['verification_notes'] = $request->verification_notes;
                }
            }

            $patient->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Patient updated successfully',
                'data' => new UserResource($patient->fresh(['roleModel', 'verifier']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update patient',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Remove the specified patient
     *
     * @param User $patient
     * @return JsonResponse
     */
    public function destroy(User $patient): JsonResponse
    {
        if ($patient->role !== 'patient') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a patient'
            ], 404);
        }

        try {
            $patient->delete();

            return response()->json([
                'success' => true,
                'message' => 'Patient deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete patient',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Verify a patient
     */
    public function verify(Request $request, User $patient): JsonResponse
    {
        if ($patient->role !== 'patient') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a patient'
            ], 404);
        }

        try {
            $success = $patient->markAsVerified(auth()->user(), $request->verification_notes);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Patient verified successfully',
                    'data' => new UserResource($patient->fresh(['roleModel', 'verifier']))
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify patient'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify patient',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Suspend a patient
     */
    public function suspend(Request $request, User $patient): JsonResponse
    {
        if ($patient->role !== 'patient') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a patient'
            ], 404);
        }

        try {
            $success = $patient->suspend(auth()->user(), $request->suspension_reason);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Patient suspended successfully',
                    'data' => new UserResource($patient->fresh(['roleModel', 'verifier']))
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend patient'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend patient',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Activate a suspended patient
     */
    public function activate(Request $request, User $patient): JsonResponse
    {
        if ($patient->role !== 'patient') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a patient'
            ], 404);
        }

        try {
            $success = $patient->activate(auth()->user());

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Patient activated successfully',
                    'data' => new UserResource($patient->fresh(['roleModel', 'verifier']))
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to activate patient'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate patient',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Export patients data
     */
    public function export(Request $request)
    {
        try {
            $query = User::where('role', 'patient');

            // Apply filters
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('verification_status', $request->status);
            }

            if ($request->has('gender') && $request->gender !== 'all') {
                $query->where('gender', $request->gender);
            }

            if ($request->has('age_group') && $request->age_group !== 'all') {
                switch ($request->age_group) {
                    case 'child':
                        $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18');
                        break;
                    case 'adult':
                        $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 64');
                        break;
                    case 'senior':
                        $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= 65');
                        break;
                }
            }

            $patients = $query->get();

            $filename = 'patients_export_' . date('Y-m-d_H-i-s') . '.csv';

            return response()->streamDownload(function () use ($patients) {
                $handle = fopen('php://output', 'w');
                
                // CSV Headers
                fputcsv($handle, [
                    'ID', 'First Name', 'Last Name', 'Email', 'Phone',
                    'Gender', 'Date of Birth', 'Age', 'Ghana Card',
                    'Emergency Contact Name', 'Emergency Contact Phone',
                    'Medical Conditions', 'Allergies', 'Current Medications',
                    'Verification Status', 'Is Active', 'Last Login', 'Created At'
                ]);

                // CSV Data
                foreach ($patients as $patient) {
                    // Calculate age
                    $age = $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->age : 'N/A';
                    
                    // Format medical data
                    $medicalConditions = is_array($patient->medical_conditions) 
                        ? implode('; ', $patient->medical_conditions) 
                        : ($patient->medical_conditions ?: 'None');
                    
                    $allergies = is_array($patient->allergies) 
                        ? implode('; ', $patient->allergies) 
                        : ($patient->allergies ?: 'None');
                    
                    $medications = is_array($patient->current_medications) 
                        ? implode('; ', $patient->current_medications) 
                        : ($patient->current_medications ?: 'None');

                    fputcsv($handle, [
                        $patient->id,
                        $patient->first_name,
                        $patient->last_name,
                        $patient->email,
                        $patient->phone,
                        ucfirst($patient->gender),
                        $patient->date_of_birth,
                        $age,
                        $patient->ghana_card_number,
                        $patient->emergency_contact_name ?: 'N/A',
                        $patient->emergency_contact_phone ?: 'N/A',
                        $medicalConditions,
                        $allergies,
                        $medications,
                        ucfirst($patient->verification_status),
                        $patient->is_active ? 'Yes' : 'No',
                        $patient->last_login_at?->format('Y-m-d H:i:s') ?: 'Never',
                        $patient->created_at->format('Y-m-d H:i:s')
                    ]);
                }

                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename={$filename}",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export patients',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Parse comma-separated string into JSON array
     *
     * @param string|null $input
     * @return array|null
     */
    private function parseCommaSeparatedString(?string $input): ?array
    {
        if (!$input || trim($input) === '') {
            return null;
        }

        $items = array_map('trim', explode(',', $input));
        return array_filter($items, function($item) {
            return !empty($item);
        });
    }

        /**
     * Change patient password
     */
    public function changePassword(Request $request, User $patient): JsonResponse
    {
        if ($patient->role !== 'patient') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a patient'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'new_password' => 'required|string|min:8'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $patient->update([
                'password' => Hash::make($request->new_password),
                'force_password_change' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to change password',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Send password reset email to patient
     */
    public function sendPasswordResetEmail(Request $request, User $patient): JsonResponse
    {
        if ($patient->role !== 'patient') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a patient'
            ], 404);
        }

        try {
            $temporaryPassword = Str::random(12);
            
            $patient->update([
                'password' => Hash::make($temporaryPassword),
                'force_password_change' => true
            ]);

            try {
                Mail::to($patient->email)->send(new UserInvitationMail($patient, $temporaryPassword));
            } catch (\Exception $e) {
                \Log::error('Failed to send password reset email: ' . $e->getMessage());
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send password reset email'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Password reset email sent successfully. The patient will receive a temporary password.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset email',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Handle photo upload
     */
    private function handlePhotoUpload($photo): string
    {
        try {
            $filename = time() . '_' . Str::random(10) . '.' . $photo->getClientOriginalExtension();
            $path = $photo->storeAs('avatars', $filename, 'public');
            return $path;
        } catch (\Exception $e) {
            \Log::error('Photo upload failed: ' . $e->getMessage());
            throw new \Exception('Failed to upload photo');
        }
    }

    /**
     * Delete photo from storage
     */
    private function deletePhoto(string $photoPath): void
    {
        try {
            if (Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
        } catch (\Exception $e) {
            \Log::error('Photo deletion failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete patient photo endpoint
     */
    public function deletePhotoEndpoint(User $patient): JsonResponse
    {
        if ($patient->role !== 'patient') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a patient'
            ], 404);
        }

        try {
            if ($patient->avatar) { 
                $this->deletePhoto($patient->avatar);
                $patient->update(['avatar' => null]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Photo deleted successfully'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No photo to delete'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete photo',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }
}