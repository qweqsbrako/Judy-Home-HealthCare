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
use Illuminate\Support\Facades\Storage;
use App\Notifications\UserInvitationNotification;

class NurseController extends Controller
{
    /**
     * Display a listing of nurses
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::query()
                ->where('role', 'nurse')
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
            if ($request->has('experience') && $request->experience !== 'all') {
                switch ($request->experience) {
                    case 'junior':
                        $query->where('years_experience', '<', 3);
                        break;
                    case 'mid':
                        $query->whereBetween('years_experience', [3, 7]);
                        break;
                    case 'senior':
                        $query->where('years_experience', '>=', 8);
                        break;
                }
            }

            // Calculate stats (before pagination, for all nurses)
            $totalNurses = User::where('role', 'nurse')->count();
            $verifiedCount = User::where('role', 'nurse')->where('verification_status', 'verified')->count();
            $pendingCount = User::where('role', 'nurse')->where('verification_status', 'pending')->count();
            $activeTodayCount = User::where('role', 'nurse')->whereDate('last_login_at', today())->count();

            // Sorting
            $sortField = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            
            $allowedSortFields = ['first_name', 'last_name', 'email', 'verification_status', 'created_at', 'last_login_at', 'years_experience'];
            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortDirection);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $nurses = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => UserResource::collection($nurses->items()),
                'meta' => [
                    'current_page' => $nurses->currentPage(),
                    'last_page' => $nurses->lastPage(),
                    'per_page' => $nurses->perPage(),
                    'total' => $nurses->total(),
                    'from' => $nurses->firstItem(),
                    'to' => $nurses->lastItem(),
                ],
                'stats' => [
                    'total_nurses' => $totalNurses,
                    'verified_count' => $verifiedCount,
                    'pending_count' => $pendingCount,
                    'active_today_count' => $activeTodayCount,
                    'verified_percentage' => $totalNurses > 0 ? round(($verifiedCount / $totalNurses) * 100) : 0,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch nurses',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Store a newly created nurse
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        \Log::info("Store Nurse - Raw Request Data");
        \Log::info($request->all());
        
        // Debug the send_invite value specifically
        \Log::info("send_invite raw value: " . var_export($request->input('send_invite'), true));
        \Log::info("send_invite as boolean: " . var_export($request->boolean('send_invite'), true));
        
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
            
            // Professional fields
            'license_number' => 'nullable|string|unique:users,license_number',
            'specialization' => 'nullable|string|max:255',
            'years_experience' => 'nullable|integer|min:0|max:50',
            
            // Authentication setup - CHANGED: nullable instead of just boolean
            'send_invite' => 'nullable|in:true,false,1,0,yes,no',
            'password' => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            \Log::error("Validation failed", $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get boolean value - this handles string "true"/"false" conversion
        $sendInvite = $request->boolean('send_invite', false);
        
        \Log::info("Processed send_invite as boolean: " . ($sendInvite ? 'true' : 'false'));
        
        // Validate password requirement
        if (!$sendInvite && empty($request->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [
                    'password' => ['Password is required when not sending an invitation.']
                ]
            ], 422);
        }

        DB::beginTransaction();

        try {
            $nurseData = $request->only([
                'first_name', 'last_name', 'email', 'phone', 'gender',
                'date_of_birth', 'ghana_card_number', 'license_number', 
                'specialization', 'years_experience'
            ]);

            // Handle photo upload
            if ($request->hasFile('photo')) {
                $photoPath = $this->handlePhotoUpload($request->file('photo'));
                $nurseData['avatar'] = $photoPath;
            }

            // Force role to nurse
            $nurseData['role'] = 'nurse';

            // Set password or generate temporary one for invitation
            if ($sendInvite) {
                $temporaryPassword = Str::random(12);
                $nurseData['password'] = Hash::make($temporaryPassword);
                $nurseData['force_password_change'] = true;
                
                \Log::info("Generated temporary password for invitation");
            } else {
                $nurseData['password'] = Hash::make($request->password);
                $nurseData['force_password_change'] = false;
                
                \Log::info("Using provided password");
            }

            // Set registered IP and verification
            $nurseData['registered_ip'] = $request->ip();
            $nurseData['verification_status'] = 'verified';
            $nurseData['is_verified'] = true;
            $nurseData['is_active'] = true;
            $nurseData['verified_at'] = now();
            $nurseData['verified_by'] = auth()->id();

            // Create nurse
            $nurse = User::create($nurseData);
            
            \Log::info("Nurse created successfully with ID: " . $nurse->id);

            // Send invitation email if requested
            if ($sendInvite) {
                try {
                    $nurse->notify(new UserInvitationNotification($temporaryPassword));
                    \Log::info("Invitation email sent to: " . $nurse->email);
                } catch (\Exception $e) {
                    \Log::error('Failed to send invitation email: ' . $e->getMessage());
                    // Don't fail the whole operation if email fails
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $sendInvite 
                    ? 'Nurse created successfully and invitation email sent'
                    : 'Nurse created successfully',
                'data' => new UserResource($nurse->fresh(['roleModel', 'verifier']))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Failed to create nurse: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create nurse',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Display the specified nurse
     *
     * @param User $nurse
     * @return JsonResponse
     */
    public function show(User $nurse): JsonResponse
    {
        if ($nurse->role !== 'nurse') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a nurse'
            ], 404);
        }

        try {
            $nurse->load(['roleModel', 'verifier']);
            
            return response()->json([
                'success' => true,
                'data' => new UserResource($nurse)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch nurse details',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Update the specified nurse
     *
     * @param Request $request
     * @param User $nurse
     * @return JsonResponse
     */
    public function update(Request $request, User $nurse): JsonResponse
    {
        if ($nurse->role !== 'nurse') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a nurse'
            ], 404);
        }

        \Log::info("Update Nurse");
        \Log::info($request->all());

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $nurse->id,
            'phone' => 'sometimes|required|string|max:20',
            'gender' => 'sometimes|required|in:male,female,other',
            'date_of_birth' => 'sometimes|nullable|date|before:today',
            'ghana_card_number' => 'sometimes|nullable|string|unique:users,ghana_card_number,' . $nurse->id,
            
            // Photo validation
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'remove_photo' => 'nullable|boolean',
            
            // Professional fields
            'license_number' => 'sometimes|nullable|string|unique:users,license_number,' . $nurse->id,
            'specialization' => 'sometimes|nullable|string|max:255',
            'years_experience' => 'sometimes|nullable|integer|min:0|max:50',
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
                // Delete old photo if exists
                if ($nurse->avatar) {
                    $this->deletePhoto($nurse->avatar);
                }
                
                $photoPath = $this->handlePhotoUpload($request->file('photo'));
                $updateData['avatar'] = $photoPath;
            }

            // Handle photo removal
            if ($request->has('remove_photo') && $request->remove_photo) {
                if ($nurse->avatar) {
                    $this->deletePhoto($nurse->avatar);
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

            $nurse->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Nurse updated successfully',
                'data' => new UserResource($nurse->fresh(['roleModel', 'verifier']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update nurse',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Remove the specified nurse
     *
     * @param User $nurse
     * @return JsonResponse
     */
    public function destroy(User $nurse): JsonResponse
    {
        if ($nurse->role !== 'nurse') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a nurse'
            ], 404);
        }

        try {
            $nurse->delete();

            return response()->json([
                'success' => true,
                'message' => 'Nurse deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete nurse',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Verify a nurse
     */
    public function verify(Request $request, User $nurse): JsonResponse
    {
        if ($nurse->role !== 'nurse') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a nurse'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'verification_notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $success = $nurse->markAsVerified(
                auth()->user(),
                $request->verification_notes
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Nurse verified successfully',
                    'data' => new UserResource($nurse->fresh(['roleModel', 'verifier']))
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify nurse'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify nurse',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Suspend a nurse
     */
    public function suspend(Request $request, User $nurse): JsonResponse
    {
        if ($nurse->role !== 'nurse') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a nurse'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'suspension_reason' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $success = $nurse->suspend(
                auth()->user(),
                $request->suspension_reason
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Nurse suspended successfully',
                    'data' => new UserResource($nurse->fresh(['roleModel', 'verifier']))
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend nurse'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend nurse',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Activate a suspended nurse
     */
    public function activate(Request $request, User $nurse): JsonResponse
    {
        if ($nurse->role !== 'nurse') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a nurse'
            ], 404);
        }

        try {
            $success = $nurse->activate(auth()->user());

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Nurse activated successfully',
                    'data' => new UserResource($nurse->fresh(['roleModel', 'verifier']))
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to activate nurse'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate nurse',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Change nurse password
     */
    public function changePassword(Request $request, User $nurse): JsonResponse
    {
        if ($nurse->role !== 'nurse') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a nurse'
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
            $nurse->update([
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
     * Send password reset email to nurse
     */
    public function sendPasswordResetEmail(Request $request, User $nurse): JsonResponse
    {
        if ($nurse->role !== 'nurse') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a nurse'
            ], 404);
        }

        try {
            // Generate new temporary password
            $temporaryPassword = Str::random(12);
            
            $nurse->update([
                'password' => Hash::make($temporaryPassword),
                'force_password_change' => true
            ]);

            // Send email
            try {
                $nurse->notify(new UserInvitationNotification($temporaryPassword));

            } catch (\Exception $e) {
                \Log::error('Failed to send password reset email: ' . $e->getMessage());
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send password reset email'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Password reset email sent successfully. The nurse will receive a temporary password.'
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
     * Export nurses data
     */
    public function export(Request $request)
    {
        try {
            $query = User::where('role', 'nurse');

            // Apply filters
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('verification_status', $request->status);
            }

            if ($request->has('specialization') && $request->specialization !== 'all') {
                $query->where('specialization', $request->specialization);
            }

            if ($request->has('experience') && $request->experience !== 'all') {
                switch ($request->experience) {
                    case 'junior':
                        $query->where('years_experience', '<', 3);
                        break;
                    case 'mid':
                        $query->whereBetween('years_experience', [3, 7]);
                        break;
                    case 'senior':
                        $query->where('years_experience', '>=', 8);
                        break;
                }
            }

            $nurses = $query->get();

            $filename = 'nurses_export_' . date('Y-m-d_H-i-s') . '.csv';

            return response()->streamDownload(function () use ($nurses) {
                $handle = fopen('php://output', 'w');
                
                // CSV Headers
                fputcsv($handle, [
                    'ID', 'First Name', 'Last Name', 'Email', 'Phone',
                    'Gender', 'Date of Birth', 'Ghana Card', 'License Number',
                    'Specialization', 'Years Experience', 'Verification Status',
                    'Is Active', 'Last Login', 'Created At'
                ]);

                // CSV Data
                foreach ($nurses as $nurse) {
                    fputcsv($handle, [
                        $nurse->id,
                        $nurse->first_name,
                        $nurse->last_name,
                        $nurse->email,
                        $nurse->phone,
                        $nurse->gender,
                        $nurse->date_of_birth,
                        $nurse->ghana_card_number,
                        $nurse->license_number,
                        $nurse->specialization,
                        $nurse->years_experience,
                        $nurse->verification_status,
                        $nurse->is_active ? 'Yes' : 'No',
                        $nurse->last_login_at?->format('Y-m-d H:i:s'),
                        $nurse->created_at->format('Y-m-d H:i:s')
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
                'message' => 'Failed to export nurses',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Handle photo upload
     *
     * @param \Illuminate\Http\UploadedFile $photo
     * @return string
     */
    private function handlePhotoUpload($photo): string
    {
        try {
            // Generate unique filename
            $filename = time() . '_' . Str::random(10) . '.' . $photo->getClientOriginalExtension();
            
            // Store in public/storage/avatars directory
            $path = $photo->storeAs('avatars', $filename, 'public');
            
            // Return ONLY the relative path (without /storage/ prefix)
            return $path;
            
        } catch (\Exception $e) {
            \Log::error('Photo upload failed: ' . $e->getMessage());
            throw new \Exception('Failed to upload photo');
        }
    }

    /**
     * Delete photo from storage
     *
     * @param string $photoPath
     * @return void
     */
    private function deletePhoto(string $photoPath): void
    {
        try {
            // The path is already relative (e.g., avatars/1760462333_YOFmgRKVmW.png)
            if (Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
        } catch (\Exception $e) {
            \Log::error('Photo deletion failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete nurse photo endpoint
     *
     * @param User $nurse
     * @return JsonResponse
     */
    public function deletePhotoEndpoint(User $nurse): JsonResponse
    {
        if ($nurse->role !== 'nurse') {
            return response()->json([
                'success' => false,
                'message' => 'User is not a nurse'
            ], 404);
        }

        try {
            if ($nurse->avatar) { 
                $this->deletePhoto($nurse->avatar);
                $nurse->update(['avatar' => null]);
                
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