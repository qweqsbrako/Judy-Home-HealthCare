<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Http\Resources\UserResource;
use App\Mail\UserVerificationMail;
use App\Mail\UserRejectionMail;
use Carbon\Carbon;

class PendingVerificationController extends Controller
{
    /**
     * Display a listing of pending verification users
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::query()
                ->where('verification_status', 'pending')
                ->whereIn('role', ['patient', 'nurse', 'doctor'])
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
                      ->orWhere('ghana_card_number', 'like', "%{$search}%");
                });
            }

            // Role filter
            if ($request->has('role') && $request->role !== 'all') {
                $query->where('role', $request->role);
            }

            // Sorting
            $sortField = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            
            $allowedSortFields = ['first_name', 'last_name', 'email', 'role', 'created_at'];
            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortDirection);
            }

            // Calculate statistics BEFORE filtering
            $totalPending = User::where('verification_status', 'pending')
                ->whereIn('role', ['patient', 'nurse', 'doctor'])
                ->count();

            // Add role counts
            $roleCounts = User::query()
                ->where('verification_status', 'pending')
                ->whereIn('role', ['patient', 'nurse', 'doctor'])
                ->groupBy('role')
                ->selectRaw('role, count(*) as count')
                ->pluck('count', 'role')
                ->toArray();

            // Get pending users for this page
            $pendingUsers = $query->get();

            return response()->json([
                'success' => true,
                'data' => UserResource::collection($pendingUsers),
                'meta' => [
                    'total' => $pendingUsers->count(),
                ],
                'stats' => [
                    'total_pending' => $totalPending,
                    'pending_patients' => $roleCounts['patient'] ?? 0,
                    'pending_nurses' => $roleCounts['nurse'] ?? 0,
                    'pending_doctors' => $roleCounts['doctor'] ?? 0,
                    'role_counts' => $roleCounts
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch pending verifications: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending verifications',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Verify a pending user
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function verify(Request $request, User $user): JsonResponse
    {
        // Check if user is in pending status and allowed roles
        if ($user->verification_status !== 'pending' || !in_array($user->role, ['patient', 'nurse', 'doctor'])) {
            return response()->json([
                'success' => false,
                'message' => 'User is not eligible for verification'
            ], 400);
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

        DB::beginTransaction();

        try {
            $user->update([
                'verification_status' => 'verified',
                'is_verified' => true,
                'is_active' => true,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'verification_notes' => $request->verification_notes
            ]);

            // Send verification email
            try {
                Mail::to($user->email)->send(new UserVerificationMail($user, $request->verification_notes));
            } catch (\Exception $e) {
                \Log::error('Failed to send verification email: ' . $e->getMessage());
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => ucfirst($user->role) . ' verified successfully',
                'data' => new UserResource($user->fresh(['roleModel', 'verifier']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify user',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Reject a pending user
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function reject(Request $request, User $user): JsonResponse
    {
        // Check if user is in pending status and allowed roles
        if ($user->verification_status !== 'pending' || !in_array($user->role, ['patient', 'nurse', 'doctor'])) {
            return response()->json([
                'success' => false,
                'message' => 'User is not eligible for rejection'
            ], 400);
        }

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

        DB::beginTransaction();

        try {
            $user->update([
                'verification_status' => 'rejected',
                'is_verified' => false,
                'is_active' => false,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'verification_notes' => $request->rejection_reason
            ]);

            // Send rejection email
            try {
                Mail::to($user->email)->send(new UserRejectionMail($user, $request->rejection_reason));
            } catch (\Exception $e) {
                \Log::error('Failed to send rejection email: ' . $e->getMessage());
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => ucfirst($user->role) . ' rejected successfully',
                'data' => new UserResource($user->fresh(['roleModel', 'verifier']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject user',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Bulk verify multiple users
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkVerify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|integer|exists:users,id',
            'verification_notes' => 'nullable|string|max:1000'
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
            // Get users that are eligible for verification
            $users = User::whereIn('id', $request->user_ids)
                ->where('verification_status', 'pending')
                ->whereIn('role', ['patient', 'nurse', 'doctor'])
                ->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No eligible users found for verification'
                ], 400);
            }

            $verifiedCount = 0;
            $failedUsers = [];

            foreach ($users as $user) {
                try {
                    $user->update([
                        'verification_status' => 'verified',
                        'is_verified' => true,
                        'is_active' => true,
                        'verified_by' => auth()->id(),
                        'verified_at' => now(),
                        'verification_notes' => $request->verification_notes
                    ]);

                    // Send verification email
                    try {
                        Mail::to($user->email)->send(new UserVerificationMail($user, $request->verification_notes));
                    } catch (\Exception $e) {
                        \Log::error("Failed to send verification email to {$user->email}: " . $e->getMessage());
                    }

                    $verifiedCount++;
                } catch (\Exception $e) {
                    $failedUsers[] = [
                        'id' => $user->id,
                        'name' => $user->first_name . ' ' . $user->last_name,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            $message = "{$verifiedCount} users verified successfully";
            if (!empty($failedUsers)) {
                $message .= ", " . count($failedUsers) . " failed";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'verified_count' => $verifiedCount,
                    'failed_count' => count($failedUsers),
                    'failed_users' => $failedUsers
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk verify users',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Get statistics about pending verifications
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_pending' => 0,
                'by_role' => [],
                'by_date' => [],
                'oldest_pending' => null,
                'newest_pending' => null
            ];

            // Get total count and role breakdown
            $roleStats = User::where('verification_status', 'pending')
                ->whereIn('role', ['patient', 'nurse', 'doctor'])
                ->groupBy('role')
                ->selectRaw('role, count(*) as count')
                ->get()
                ->pluck('count', 'role')
                ->toArray();

            $stats['by_role'] = $roleStats;
            $stats['total_pending'] = array_sum($roleStats);

            // Get date-based statistics (last 30 days)
            $dateStats = User::where('verification_status', 'pending')
                ->whereIn('role', ['patient', 'nurse', 'doctor'])
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy(DB::raw('DATE(created_at)'))
                ->selectRaw('DATE(created_at) as date, count(*) as count')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date')
                ->toArray();

            $stats['by_date'] = $dateStats;

            // Get oldest and newest pending
            $oldestPending = User::where('verification_status', 'pending')
                ->whereIn('role', ['patient', 'nurse', 'doctor'])
                ->orderBy('created_at', 'asc')
                ->first();

            $newestPending = User::where('verification_status', 'pending')
                ->whereIn('role', ['patient', 'nurse', 'doctor'])
                ->orderBy('created_at', 'desc')
                ->first();

            $stats['oldest_pending'] = $oldestPending ? $oldestPending->created_at : null;
            $stats['newest_pending'] = $newestPending ? $newestPending->created_at : null;

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Export pending verifications data
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        \Log::info("Downloading");
        try {
            $query = User::where('verification_status', 'pending')
                ->whereIn('role', ['patient', 'nurse', 'doctor']);

            // Apply filters
            if ($request->has('role') && $request->role !== 'all') {
                $query->where('role', $request->role);
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('license_number', 'like', "%{$search}%")
                      ->orWhere('ghana_card_number', 'like', "%{$search}%");
                });
            }

            $pendingUsers = $query->orderBy('created_at', 'desc')->get();

            $filename = 'pending_verifications_' . date('Y-m-d_H-i-s') . '.csv';

            return response()->streamDownload(function () use ($pendingUsers) {
                $handle = fopen('php://output', 'w');
                
                // CSV Headers
                fputcsv($handle, [
                    'ID', 'Role', 'First Name', 'Last Name', 'Email', 'Phone',
                    'Gender', 'Date of Birth', 'Ghana Card', 'License Number',
                    'Specialization', 'Years Experience', 'Registration Date', 
                    'Registration IP', 'Days Pending'
                ]);

                // CSV Data
                foreach ($pendingUsers as $user) {
                    $daysPending = now()->diffInDays($user->created_at);
                    
                    fputcsv($handle, [
                        $user->id,
                        ucfirst($user->role),
                        $user->first_name,
                        $user->last_name,
                        $user->email,
                        $user->phone,
                        ucfirst($user->gender),
                        $user->date_of_birth,
                        $user->ghana_card_number,
                        $user->license_number ?: 'N/A',
                        $user->specialization ? ucwords(str_replace('_', ' ', $user->specialization)) : 'N/A',
                        $user->years_experience ?: 0,
                        $user->created_at->format('Y-m-d H:i:s'),
                        $user->registered_ip ?: 'Not recorded',
                        $daysPending
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
                'message' => 'Failed to export pending verifications',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Get details of a specific pending user
     *
     * @param User $user
     * @return JsonResponse
     */
    public function show(User $user): JsonResponse
    {
        // Check if user is in pending status and allowed roles
        if ($user->verification_status !== 'pending' || !in_array($user->role, ['patient', 'nurse', 'doctor'])) {
            return response()->json([
                'success' => false,
                'message' => 'User not found in pending verifications'
            ], 404);
        }

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
     * Handle bulk rejection (if needed in the future)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkReject(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|integer|exists:users,id',
            'rejection_reason' => 'required|string|max:1000'
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
            // Get users that are eligible for rejection
            $users = User::whereIn('id', $request->user_ids)
                ->where('verification_status', 'pending')
                ->whereIn('role', ['patient', 'nurse', 'doctor'])
                ->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No eligible users found for rejection'
                ], 400);
            }

            $rejectedCount = 0;
            $failedUsers = [];

            foreach ($users as $user) {
                try {
                    $user->update([
                        'verification_status' => 'rejected',
                        'is_verified' => false,
                        'is_active' => false,
                        'verified_by' => auth()->id(),
                        'verified_at' => now(),
                        'verification_notes' => $request->rejection_reason
                    ]);

                    // Send rejection email
                    try {
                        Mail::to($user->email)->send(new UserRejectionMail($user, $request->rejection_reason));
                    } catch (\Exception $e) {
                        \Log::error("Failed to send rejection email to {$user->email}: " . $e->getMessage());
                    }

                    $rejectedCount++;
                } catch (\Exception $e) {
                    $failedUsers[] = [
                        'id' => $user->id,
                        'name' => $user->first_name . ' ' . $user->last_name,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            $message = "{$rejectedCount} users rejected successfully";
            if (!empty($failedUsers)) {
                $message .= ", " . count($failedUsers) . " failed";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'rejected_count' => $rejectedCount,
                    'failed_count' => count($failedUsers),
                    'failed_users' => $failedUsers
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk reject users',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }
}