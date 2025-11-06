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

class UserController extends Controller
{
    /**
     * Display a listing of users
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::query()->with(['roleModel', 'verifier']);
            
            // Store the base query for stats calculation
            $statsQuery = clone $query;
            
            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('ghana_card_number', 'like', "%{$search}%");
                });
            }
            
            // Role filter
            if ($request->has('role') && $request->role !== 'all') {
                $query->where('role', $request->role);
            }
            
            // Status filter
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('verification_status', $request->status);
            }
            
            // Active filter
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }
            
            // Calculate stats from filtered query
            $totalUsers = User::count();
            $verifiedCount = User::where('verification_status', 'verified')->count();
            $pendingCount = User::where('verification_status', 'pending')->count();
            $activeTodayCount = User::whereDate('last_login_at', today())->count();
            
            // Sorting
            $sortField = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $allowedSortFields = ['first_name', 'last_name', 'email', 'role', 'verification_status', 'created_at', 'last_login_at'];
            
            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortDirection);
            }
            
            // Pagination
            $perPage = $request->get('per_page', 15);
            $users = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => UserResource::collection($users->items()),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ],
                'stats' => [
                    'total_users' => $totalUsers,
                    'verified_count' => $verifiedCount,
                    'pending_count' => $pendingCount,
                    'active_today_count' => $activeTodayCount,
                    'verified_percentage' => $totalUsers > 0 ? round(($verifiedCount / $totalUsers) * 100) : 0,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Store a newly created user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
            \Log::info("Store User");
        \Log::info($request->all());
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'role' => 'required|in:patient,nurse,doctor,admin,superadmin',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'nullable|date|before:today',
            'ghana_card_number' => 'nullable|string|unique:users,ghana_card_number',
            
            // Photo validation
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048', // 2MB max
            
            // Conditional fields
            'license_number' => 'nullable|string|unique:users,license_number',
            'specialization' => 'nullable|string|max:255',
            'years_experience' => 'nullable|integer|min:0|max:50',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            
            // Authentication
            'send_invite' => 'boolean',
            'password' => 'nullable|string|min:8',
        ]);

        // $validator->sometimes(['license_number', 'specialization'], 'required', function ($input) {
        //     return in_array($input->role, ['nurse', 'doctor']);
        // });

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
            $userData = $request->only([
                'first_name', 'last_name', 'email', 'phone', 'role', 'gender',
                'date_of_birth', 'ghana_card_number', 'license_number', 
                'specialization', 'years_experience', 'emergency_contact_name',
                'emergency_contact_phone'
            ]);

            // Handle photo upload
            if ($request->hasFile('photo')) {
                $photoPath = $this->handlePhotoUpload($request->file('photo'));
                $userData['avatar'] = $photoPath;
            }

            // Set password
            if ($request->send_invite) {
                $temporaryPassword = Str::random(12);
                $userData['password'] = Hash::make($temporaryPassword);
                $userData['force_password_change'] = true;
            } else {
                $userData['password'] = Hash::make($request->password);
                $userData['force_password_change'] = false;
            }

            $userData['registered_ip'] = $request->ip();
            $userData['verification_status'] = 'verified';
            $userData['is_verified'] = true;
            $userData['is_active'] = true;
            $userData['verified_at'] = now();
            $userData['verified_by'] = auth()->id();

            $user = User::create($userData);

            // Send invitation email if requested
            if ($request->send_invite) {
                try {
                    Mail::to($user->email)->send(new UserInvitationMail($user, $temporaryPassword));
                } catch (\Exception $e) {
                    \Log::error('Failed to send invitation email: ' . $e->getMessage());
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $request->send_invite 
                    ? 'User created successfully and invitation email sent'
                    : 'User created successfully',
                'data' => new UserResource($user->fresh(['roleModel', 'verifier']))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Display the specified user
     *
     * @param User $user
     * @return JsonResponse
     */
    public function show(User $user): JsonResponse
    {
        try {
            $user->load(['roleModel', 'verifier']);
            
            return response()->json([
                'success' => true,
                'data' => new UserResource($user)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user details',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Update the specified user
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function update(Request $request, User $user): JsonResponse
    {
        \Log::info("Update User");
        \Log::info($request->all());
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|required|string|max:20',
            'role' => 'sometimes|required|in:patient,nurse,doctor,admin,superadmin',
            'gender' => 'sometimes|required|in:male,female,other',
            'date_of_birth' => 'sometimes|nullable|date|before:today',
            'ghana_card_number' => 'sometimes|nullable|string|unique:users,ghana_card_number,' . $user->id,
            
            // Photo validation
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'remove_photo' => 'nullable|boolean',
            
            // Other fields
            'license_number' => 'nullable|string|unique:users,license_number,' . $user->id,
            'specialization' => 'nullable|string|max:255',
            'years_experience' => 'nullable|integer|min:0|max:50',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
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
                'first_name', 'last_name', 'email', 'phone', 'role', 'gender',
                'date_of_birth', 'ghana_card_number', 'license_number',
                'specialization', 'years_experience', 'emergency_contact_name',
                'emergency_contact_phone', 'is_active'
            ]);

            // Handle photo upload
            if ($request->hasFile('photo')) {
                // Delete old photo if exists
                if ($user->avatar) {
                    $this->deletePhoto($user->avatar);
                }
                
                $photoPath = $this->handlePhotoUpload($request->file('photo'));
                $updateData['avatar'] = $photoPath;
            }

            // Handle photo removal
            if ($request->has('remove_photo') && $request->remove_photo) {
                if ($user->avatar) {
                    $this->deletePhoto($user->avatar);
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

            $user->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => new UserResource($user->fresh(['roleModel', 'verifier']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Remove the specified user (soft delete)
     *
     * @param User $user
     * @return JsonResponse
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            // Prevent self-deletion
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account'
                ], 422);
            }

            // Prevent deletion of super admin by non-super admin
            if ($user->role === 'superadmin' && auth()->user()->role !== 'superadmin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to delete super admin'
                ], 403);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Verify a user account
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function verify(Request $request, User $user): JsonResponse
    {
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
            $success = $user->markAsVerified(
                auth()->user(),
                $request->verification_notes
            );

            if ($success) {
                // Send approval notification to user (Email + SMS)
                try {
                    $user->notify(new \App\Notifications\AccountApprovedNotification());
                    
                    \Log::info('Account approval notification sent', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'phone' => $user->phone
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to send approval notification', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                    // Don't fail the verification if notification fails
                }

                return response()->json([
                    'success' => true,
                    'message' => 'User verified successfully. Approval notification sent via email and SMS.',
                    'data' => new UserResource($user->fresh(['roleModel', 'verifier']))
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify user'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify user',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Reject user verification
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function reject(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $success = $user->rejectVerification(
                auth()->user(),
                $request->rejection_reason
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'User verification rejected',
                    'data' => new UserResource($user->fresh(['roleModel', 'verifier']))
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject user verification'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject user verification',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Suspend a user account
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function suspend(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Prevent self-suspension
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot suspend your own account'
                ], 422);
            }

            $success = $user->suspend(
                auth()->user(),
                $request->suspension_reason
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'User suspended successfully',
                    'data' => new UserResource($user->fresh(['roleModel', 'verifier']))
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend user'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend user',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Activate a suspended user account
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function activate(Request $request, User $user): JsonResponse
    {
        try {
            $success = $user->activate(auth()->user());

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'User activated successfully',
                    'data' => new UserResource($user->fresh(['roleModel', 'verifier']))
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to activate user'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate user',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Get user statistics
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::where('is_active', true)->count(),
                'pending_verification' => User::where('verification_status', 'pending')->count(),
                'by_role' => User::selectRaw('role, COUNT(*) as count')
                    ->groupBy('role')
                    ->pluck('count', 'role'),
                'by_status' => User::selectRaw('verification_status, COUNT(*) as count')
                    ->groupBy('verification_status')
                    ->pluck('count', 'verification_status'),
                'recent_registrations' => User::where('created_at', '>=', Carbon::now()->subDays(30))
                    ->count(),
                'recent_logins' => User::where('last_login_at', '>=', Carbon::now()->subDays(7))
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Resend invitation email to user
     *
     * @param User $user
     * @return JsonResponse
     */
    public function resendInvitation(User $user): JsonResponse
    {
        try {
            // Check if user needs invitation
            if ($user->email_verified_at || !$user->force_password_change) {
                return response()->json([
                    'success' => false,
                    'message' => 'User has already completed registration'
                ], 422);
            }

            // Generate new temporary password
            $temporaryPassword = Str::random(12);
            $user->update([
                'password' => Hash::make($temporaryPassword),
                'force_password_change' => true
            ]);

            // Send invitation email
            Mail::to($user->email)->send(new UserInvitationMail($user, $temporaryPassword));

            return response()->json([
                'success' => true,
                'message' => 'Invitation email sent successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send invitation email',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Export users data
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        try {
            $query = User::query();

            // Apply same filters as index method
            if ($request->has('role') && $request->role !== 'all') {
                $query->where('role', $request->role);
            }

            if ($request->has('status') && $request->status !== 'all') {
                $query->where('verification_status', $request->status);
            }

            $users = $query->get();

            $filename = 'users_export_' . date('Y-m-d_H-i-s') . '.csv';

            return response()->streamDownload(function () use ($users) {
                $handle = fopen('php://output', 'w');
                
                // CSV Headers
                fputcsv($handle, [
                    'ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Role',
                    'Gender', 'Date of Birth', 'Ghana Card', 'License Number',
                    'Specialization', 'Years Experience', 'Verification Status',
                    'Is Active', 'Last Login', 'Created At'
                ]);

                // CSV Data
                foreach ($users as $user) {
                    fputcsv($handle, [
                        $user->id,
                        $user->first_name,
                        $user->last_name,
                        $user->email,
                        $user->phone,
                        $user->role,
                        $user->gender,
                        $user->date_of_birth,
                        $user->ghana_card_number,
                        $user->license_number,
                        $user->specialization,
                        $user->years_experience,
                        $user->verification_status,
                        $user->is_active ? 'Yes' : 'No',
                        $user->last_login_at?->format('Y-m-d H:i:s'),
                        $user->created_at->format('Y-m-d H:i:s')
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
                'message' => 'Failed to export users',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request, User $user): JsonResponse
    {
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
            $user->update([
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
     * Send password reset email to user
     */
    public function sendPasswordResetEmail(Request $request, User $user): JsonResponse
    {
        try {
            // Generate new temporary password
            $temporaryPassword = Str::random(12);
            
            $user->update([
                'password' => Hash::make($temporaryPassword),
                'force_password_change' => true
            ]);

            // Send email
            try {
                Mail::to($user->email)->send(new UserInvitationMail($user, $temporaryPassword));
            } catch (\Exception $e) {
                \Log::error('Failed to send password reset email: ' . $e->getMessage());
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send password reset email'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Password reset email sent successfully. The user will receive a temporary password.'
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
            
            // Return the URL path
            // return Storage::url($path);
            return $path;
            
        } catch (\Exception $e) {
            \Log::error('Photo upload failed: ' . $e->getMessage());
            throw new \Exception('Failed to upload photo');
        }
    }

    /**
     * Delete photo from storage
     *
     * @param string $photoUrl
     * @return void
     */
    private function deletePhoto(string $photoUrl): void
    {
        try {
            // Extract path from URL
            $path = str_replace('/storage/', '', parse_url($photoUrl, PHP_URL_PATH));
            
            // Delete from storage
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (\Exception $e) {
            \Log::error('Photo deletion failed: ' . $e->getMessage());
        }
    }

    public function deletePhotoEndpoint(User $user): JsonResponse
    {
        try {
            if ($user->avatar) { 
                $this->deletePhoto($user->avatar);
                $user->update(['avatar' => null]);
                
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