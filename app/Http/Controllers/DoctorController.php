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
use App\Notifications\UserInvitationNotification;

class DoctorController extends Controller
{
    /**
     * Display a listing of doctors
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::query()
                ->where('role', 'doctor')
                ->with(['roleModel', 'verifier']);

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('license_number', 'like', "%{$search}%")
                      ->orWhere('specialization', 'like', "%{$search}%")
                      ->orWhere('ghana_card_number', 'like', "%{$search}%");
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

            // Specialization filter
            if ($request->has('specialization') && $request->specialization !== 'all') {
                $query->where('specialization', $request->specialization);
            }

            // Experience filter
            if ($request->has('experience_level')) {
                switch ($request->experience_level) {
                    case 'junior':
                        $query->where('years_experience', '<=', 5);
                        break;
                    case 'mid':
                        $query->whereBetween('years_experience', [6, 15]);
                        break;
                    case 'senior':
                        $query->where('years_experience', '>=', 16);
                        break;
                }
            }

            // Sorting
            $sortField = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            
            $allowedSortFields = ['first_name', 'last_name', 'email', 'verification_status', 'created_at', 'last_login_at', 'years_experience', 'specialization'];
            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortDirection);
            }

            // Calculate statistics BEFORE pagination
            $totalDoctors = User::where('role', 'doctor')->count();
            $verifiedCount = User::where('role', 'doctor')
                ->where('verification_status', 'verified')
                ->count();
            $pendingCount = User::where('role', 'doctor')
                ->where('verification_status', 'pending')
                ->count();
            
            // Active today - logged in within last 24 hours
            $activeTodayCount = User::where('role', 'doctor')
                ->where('last_login_at', '>=', now()->subDay())
                ->count();
            
            // Calculate percentage
            $verifiedPercentage = $totalDoctors > 0 
                ? round(($verifiedCount / $totalDoctors) * 100, 1) 
                : 0;

            // Pagination
            $perPage = $request->get('per_page', 15);
            $doctors = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => UserResource::collection($doctors->items()),
                'meta' => [
                    'current_page' => $doctors->currentPage(),
                    'last_page' => $doctors->lastPage(),
                    'per_page' => $doctors->perPage(),
                    'total' => $doctors->total(),
                ],
                'stats' => [
                    'total_doctors' => $totalDoctors,
                    'verified_count' => $verifiedCount,
                    'pending_count' => $pendingCount,
                    'active_today_count' => $activeTodayCount,
                    'verified_percentage' => $verifiedPercentage,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch doctors: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch doctors',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Store a newly created doctor
     */
    public function store(Request $request): JsonResponse
    {
        \Log::info("Store Doctor");
        \Log::info($request->all());
        
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'required|date|before:today',
            'ghana_card_number' => 'required|string|unique:users,ghana_card_number',
            'license_number' => 'required|string|unique:users,license_number',
            'specialization' => 'required|string|max:255',
            'years_experience' => 'required|integer|min:0|max:60',
            
            // Photo validation
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            
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
            $doctorData = $request->only([
                'first_name', 'last_name', 'email', 'phone', 'gender',
                'date_of_birth', 'ghana_card_number', 'license_number', 
                'specialization', 'years_experience'
            ]);

            // Handle photo upload
            if ($request->hasFile('photo')) {
                $photoPath = $this->handlePhotoUpload($request->file('photo'));
                $doctorData['avatar'] = $photoPath;
            }

            // Force role to doctor
            $doctorData['role'] = 'doctor';

            // Set password or generate temporary one for invitation
            if ($request->send_invite) {
                $temporaryPassword = Str::random(12);
                $doctorData['password'] = Hash::make($temporaryPassword);
                $doctorData['force_password_change'] = true;
            } else {
                $doctorData['password'] = Hash::make($request->password);
                $doctorData['force_password_change'] = false;
            }

            // Set registered IP
            $doctorData['registered_ip'] = $request->ip();
            $doctorData['verification_status'] = 'verified';
            $doctorData['is_verified'] = true;
            $doctorData['is_active'] = true;
            $doctorData['verified_at'] = now();
            $doctorData['verified_by'] = auth()->id();

            // Create doctor
            $doctor = User::create($doctorData);

            // Send invitation email if requested
            if ($request->send_invite) {
                try {
                    $doctor->notify(new UserInvitationNotification($temporaryPassword));
                } catch (\Exception $e) {
                    \Log::error('Failed to send invitation email: ' . $e->getMessage());
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $request->send_invite 
                    ? 'Doctor created successfully and invitation email sent'
                    : 'Doctor created successfully',
                'data' => new UserResource($doctor->fresh(['roleModel', 'verifier']))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create doctor',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Display the specified doctor
     *
     * @param User $doctor
     * @return JsonResponse
     */
    public function show(User $doctor): JsonResponse
    {
        if ($doctor->role !== 'doctor') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a doctor'
            ], 404);
        }

        try {
            $doctor->load(['roleModel', 'verifier']);
            
            return response()->json([
                'success' => true,
                'data' => new UserResource($doctor)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch doctor details',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Update the specified doctor
     *
     * @param Request $request
     * @param User $doctor
     * @return JsonResponse
     */
    public function update(Request $request, User $doctor): JsonResponse
    {
        if ($doctor->role !== 'doctor') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a doctor'
            ], 404);
        }

        \Log::info("Update Doctor");
        \Log::info($request->all());

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $doctor->id,
            'phone' => 'sometimes|required|string|max:20',
            'gender' => 'sometimes|required|in:male,female,other',
            'date_of_birth' => 'sometimes|required|date|before:today',
            'ghana_card_number' => 'sometimes|required|string|unique:users,ghana_card_number,' . $doctor->id,
            'license_number' => 'sometimes|required|string|unique:users,license_number,' . $doctor->id,
            'specialization' => 'sometimes|required|string|max:255',
            'years_experience' => 'sometimes|required|integer|min:0|max:60',
            
            // Photo validation
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'remove_photo' => 'nullable|boolean',
            
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
                'date_of_birth', 'ghana_card_number', 'license_number',
                'specialization', 'years_experience', 'is_active'
            ]);

            // Handle photo upload
            if ($request->hasFile('photo')) {
                if ($doctor->avatar) {
                    $this->deletePhoto($doctor->avatar);
                }
                
                $photoPath = $this->handlePhotoUpload($request->file('photo'));
                $updateData['avatar'] = $photoPath;
            }

            // Handle photo removal
            if ($request->has('remove_photo') && $request->remove_photo) {
                if ($doctor->avatar) {
                    $this->deletePhoto($doctor->avatar);
                }
                $updateData['avatar'] = null;
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

            $doctor->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Doctor updated successfully',
                'data' => new UserResource($doctor->fresh(['roleModel', 'verifier']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update doctor',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Remove the specified doctor
     *
     * @param User $doctor
     * @return JsonResponse
     */
    public function destroy(User $doctor): JsonResponse
    {
        if ($doctor->role !== 'doctor') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a doctor'
            ], 404);
        }

        try {
            $doctor->delete();

            return response()->json([
                'success' => true,
                'message' => 'Doctor deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete doctor',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Verify a doctor
     */
    public function verify(Request $request, User $doctor): JsonResponse
    {
        if ($doctor->role !== 'doctor') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a doctor'
            ], 404);
        }

        try {
            $success = $doctor->markAsVerified(auth()->user(), $request->verification_notes);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Doctor verified successfully',
                    'data' => new UserResource($doctor->fresh(['roleModel', 'verifier']))
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify doctor'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify doctor',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Suspend a doctor
     */
    public function suspend(Request $request, User $doctor): JsonResponse
    {
        if ($doctor->role !== 'doctor') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a doctor'
            ], 404);
        }

        try {
            $success = $doctor->suspend(auth()->user(), $request->suspension_reason);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Doctor suspended successfully',
                    'data' => new UserResource($doctor->fresh(['roleModel', 'verifier']))
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend doctor'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend doctor',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Activate a suspended doctor
     */
    public function activate(Request $request, User $doctor): JsonResponse
    {
        if ($doctor->role !== 'doctor') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a doctor'
            ], 404);
        }

        try {
            $success = $doctor->activate(auth()->user());

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Doctor activated successfully',
                    'data' => new UserResource($doctor->fresh(['roleModel', 'verifier']))
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to activate doctor'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate doctor',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Export doctors data
     */
    public function export(Request $request)
    {
        try {
            $query = User::where('role', 'doctor');

            // Apply filters
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('verification_status', $request->status);
            }

            if ($request->has('specialization') && $request->specialization !== 'all') {
                $query->where('specialization', $request->specialization);
            }

            if ($request->has('experience_level') && $request->experience_level !== 'all') {
                switch ($request->experience_level) {
                    case 'junior':
                        $query->where('years_experience', '<=', 5);
                        break;
                    case 'mid':
                        $query->whereBetween('years_experience', [6, 15]);
                        break;
                    case 'senior':
                        $query->where('years_experience', '>=', 16);
                        break;
                }
            }

            $doctors = $query->get();

            $filename = 'doctors_export_' . date('Y-m-d_H-i-s') . '.csv';

            return response()->streamDownload(function () use ($doctors) {
                $handle = fopen('php://output', 'w');
                
                // CSV Headers
                fputcsv($handle, [
                    'ID', 'First Name', 'Last Name', 'Email', 'Phone',
                    'Gender', 'Date of Birth', 'Ghana Card', 'License Number',
                    'Specialization', 'Years Experience', 'Verification Status', 
                    'Is Active', 'Last Login', 'Created At'
                ]);

                // CSV Data
                foreach ($doctors as $doctor) {
                    fputcsv($handle, [
                        $doctor->id,
                        $doctor->first_name,
                        $doctor->last_name,
                        $doctor->email,
                        $doctor->phone,
                        ucfirst($doctor->gender),
                        $doctor->date_of_birth,
                        $doctor->ghana_card_number,
                        $doctor->license_number,
                        ucwords(str_replace('_', ' ', $doctor->specialization)),
                        $doctor->years_experience,
                        ucfirst($doctor->verification_status),
                        $doctor->is_active ? 'Yes' : 'No',
                        $doctor->last_login_at?->format('Y-m-d H:i:s') ?: 'Never',
                        $doctor->created_at->format('Y-m-d H:i:s')
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
                'message' => 'Failed to export doctors',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }


        /**
     * Change doctor password
     */
    public function changePassword(Request $request, User $doctor): JsonResponse
    {
        if ($doctor->role !== 'doctor') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a doctor'
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
            $doctor->update([
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
     * Send password reset email to doctor
     */
    public function sendPasswordResetEmail(Request $request, User $doctor): JsonResponse
    {
        if ($doctor->role !== 'doctor') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a doctor'
            ], 404);
        }

        try {
            $temporaryPassword = Str::random(12);
            
            $doctor->update([
                'password' => Hash::make($temporaryPassword),
                'force_password_change' => true
            ]);

            try {
                $doctor->notify(new UserInvitationNotification($temporaryPassword));

            } catch (\Exception $e) {
                \Log::error('Failed to send password reset email: ' . $e->getMessage());
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send password reset email'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Password reset email sent successfully. The doctor will receive a temporary password.'
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
     * Delete doctor photo endpoint
     */
    public function deletePhotoEndpoint(User $doctor): JsonResponse
    {
        if ($doctor->role !== 'doctor') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a doctor'
            ], 404);
        }

        try {
            if ($doctor->avatar) { 
                $this->deletePhoto($doctor->avatar);
                $doctor->update(['avatar' => null]);
                
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