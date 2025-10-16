<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ProfileController extends Controller
{
    /**
     * Get authenticated user's profile
     */
    public function show(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }
        
        // Convert avatar path to full URL
        $userData = $user->toArray();
        if ($user->avatar) {
            $userData['avatar'] = Storage::url($user->avatar);
        }
        
        return response()->json($userData);
    }

    /**
     * Update general profile information
     */
    public function update(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'required|string|max:20',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
        ]);

        // If email is changed, mark as unverified
        if ($user->email !== $validated['email']) {
            $validated['email_verified_at'] = null;
        }

        $user->update($validated);
        
        // Return user data with avatar URL
        $userData = $user->toArray();
        if ($user->avatar) {
            $userData['avatar'] = Storage::url($user->avatar);
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $userData
        ]);
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 422);
        }

        // Update password
        $user->update([
            'password' => Hash::make($validated['new_password']),
            'password_changed_at' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Password updated successfully'
        ]);
    }

    /**
     * Update professional information (for nurses and doctors)
     */
    public function updateProfessional(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Only nurses and doctors can update professional info
        if (!in_array($user->role, ['nurse', 'doctor'])) {
            return response()->json([
                'message' => 'Only nurses and doctors can update professional information'
            ], 403);
        }

        $validated = $request->validate([
            'license_number' => 'nullable|string|max:50|unique:users,license_number,' . $user->id,
            'specialization' => 'nullable|string|max:255',
            'years_experience' => 'nullable|integer|min:0',
        ]);

        $user->update($validated);
        
        // Return user data with avatar URL
        $userData = $user->toArray();
        if ($user->avatar) {
            $userData['avatar'] = Storage::url($user->avatar);
        }

        return response()->json([
            'message' => 'Professional information updated successfully',
            'user' => $userData
        ]);
    }

    /**
     * Update avatar/profile picture
     */
    public function updateAvatar(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,jpg,png,gif|max:2048', // 2MB max
        ]);

        // Delete old avatar if exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Store new avatar
        $avatarPath = $request->file('avatar')->store('avatars', 'public');

        $user->update([
            'avatar' => $avatarPath
        ]);

        return response()->json([
            'message' => 'Profile picture updated successfully',
            'avatar_url' => Storage::url($avatarPath)
        ]);
    }

    /**
     * Enable two-factor authentication
     */
    public function enableTwoFactor(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $validated = $request->validate([
            'method' => 'required|in:email,sms,voice'
        ]);

        // Update user settings
        $user->update([
            'two_factor_enabled' => true,
            'two_factor_method' => $validated['method'],
            'two_factor_enabled_at' => Carbon::now(),
        ]);

        // TODO: Send verification code via selected method
        $this->sendTwoFactorVerificationCode($user, $validated['method']);
        
        // Return user data with avatar URL
        $userData = $user->toArray();
        if ($user->avatar) {
            $userData['avatar'] = Storage::url($user->avatar);
        }

        return response()->json([
            'message' => 'Two-factor authentication enabled successfully',
            'user' => $userData
        ]);
    }

    /**
     * Disable two-factor authentication
     */
    public function disableTwoFactor(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $user->update([
            'two_factor_enabled' => false,
            'two_factor_method' => null,
            'two_factor_disabled_at' => Carbon::now(),
        ]);
        
        // Return user data with avatar URL
        $userData = $user->toArray();
        if ($user->avatar) {
            $userData['avatar'] = Storage::url($user->avatar);
        }

        return response()->json([
            'message' => 'Two-factor authentication disabled successfully',
            'user' => $userData
        ]);
    }

    /**
     * Send two-factor verification code
     */
    private function sendTwoFactorVerificationCode($user, $method)
    {
        // Generate 6-digit code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store code in cache
        cache()->put(
            "2fa_code_{$user->id}",
            Hash::make($code),
            now()->addMinutes(10)
        );

        // Send code based on method
        switch ($method) {
            case 'email':
                // TODO: Send email with code
                break;
            
            case 'sms':
            case 'voice':
                // TODO: Send SMS/voice call with code
                break;
        }
    }

    /**
     * Verify two-factor code
     */
    public function verifyTwoFactor(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $validated = $request->validate([
            'code' => 'required|string|size:6'
        ]);

        // Get stored code from cache
        $storedCodeHash = cache()->get("2fa_code_{$user->id}");

        if (!$storedCodeHash) {
            return response()->json([
                'message' => 'Verification code expired. Please request a new one.'
            ], 422);
        }

        // Verify code
        if (!Hash::check($validated['code'], $storedCodeHash)) {
            return response()->json([
                'message' => 'Invalid verification code'
            ], 422);
        }

        // Mark as verified
        $user->update([
            'two_factor_verified_at' => Carbon::now()
        ]);

        // Clear code from cache
        cache()->forget("2fa_code_{$user->id}");
        
        // Return user data with avatar URL
        $userData = $user->toArray();
        if ($user->avatar) {
            $userData['avatar'] = Storage::url($user->avatar);
        }

        return response()->json([
            'message' => 'Two-factor authentication verified successfully',
            'user' => $userData
        ]);
    }

    /**
     * Get profile statistics
     */
    public function statistics(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }
        
        $stats = [];

        switch ($user->role) {
            case 'nurse':
                $stats = [
                    'total_patients' => \DB::table('progress_notes')
                        ->where('nurse_id', $user->id)
                        ->distinct('patient_id')
                        ->count('patient_id'),
                    'total_shifts' => \DB::table('schedules')
                        ->where('nurse_id', $user->id)
                        ->count(),
                    'completed_shifts' => \DB::table('schedules')
                        ->where('nurse_id', $user->id)
                        ->where('status', 'completed')
                        ->count(),
                    'total_hours' => \DB::table('time_trackings')
                        ->where('nurse_id', $user->id)
                        ->where('status', 'completed')
                        ->sum(\DB::raw('total_duration_minutes / 60')),
                ];
                break;

            case 'doctor':
                $stats = [
                    'total_care_plans' => \DB::table('care_plans')
                        ->where('doctor_id', $user->id)
                        ->count(),
                    'active_care_plans' => \DB::table('care_plans')
                        ->where('doctor_id', $user->id)
                        ->where('status', 'active')
                        ->count(),
                    'completed_care_plans' => \DB::table('care_plans')
                        ->where('doctor_id', $user->id)
                        ->where('status', 'completed')
                        ->count(),
                ];
                break;

            case 'patient':
                $stats = [
                    'active_care_plans' => \DB::table('care_plans')
                        ->where('patient_id', $user->id)
                        ->where('status', 'active')
                        ->count(),
                    'total_visits' => \DB::table('progress_notes')
                        ->where('patient_id', $user->id)
                        ->count(),
                    'upcoming_appointments' => \DB::table('schedules')
                        ->where('patient_id', $user->id)
                        ->where('schedule_date', '>=', Carbon::today())
                        ->count(),
                ];
                break;

            case 'admin':
            case 'superadmin':
                $stats = [
                    'total_users' => \DB::table('users')->whereNull('deleted_at')->count(),
                    'pending_verifications' => \DB::table('users')
                        ->whereNull('deleted_at')
                        ->where('verification_status', 'pending')
                        ->count(),
                    'active_care_plans' => \DB::table('care_plans')
                        ->whereNull('deleted_at')
                        ->where('status', 'active')
                        ->count(),
                    'total_nurses' => \DB::table('users')
                        ->whereNull('deleted_at')
                        ->where('role', 'nurse')
                        ->count(),
                ];
                break;
        }

        return response()->json($stats);
    }

    /**
     * Update preferences
     */
    public function updatePreferences(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $validated = $request->validate([
            'timezone' => 'required|string|max:50',
            'locale' => 'required|string|max:10',
            'preferences' => 'nullable|array',
        ]);

        $user->update($validated);
        
        // Return user data with avatar URL
        $userData = $user->toArray();
        if ($user->avatar) {
            $userData['avatar'] = Storage::url($user->avatar);
        }

        return response()->json([
            'message' => 'Preferences updated successfully',
            'user' => $userData
        ]);
    }

    /**
     * Delete account (soft delete)
     */
    public function deleteAccount(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $validated = $request->validate([
            'password' => 'required|string',
            'confirmation' => 'required|string|in:DELETE MY ACCOUNT',
        ]);

        // Verify password
        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Password is incorrect'
            ], 422);
        }

        // Soft delete user
        $user->delete();

        // Logout user
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Account deleted successfully'
        ]);
    }
}