<?php

namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MobileNotification;
use App\Models\NotificationPreference;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\Facades\Image;
use Carbon\Carbon;
use App\Notifications\TwoFactorAuthNotification;
use App\Services\SmsService;

class ProfileController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Get authenticated user's profile
     * GET /api/mobile/profile
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatUserData($user)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching profile: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profile'
            ], 500);
        }
    }

    /**
     * Update authenticated user's profile
     * PUT /api/mobile/profile/update
     */
    public function update(Request $request): JsonResponse
    {
        try {
            \Log::info('🔵 Profile update request received');
            \Log::info('Request data:', $request->all());
            
            $user = auth()->user();
            
            if (!$user) {
                \Log::error('❌ No authenticated user found');
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            \Log::info('✅ User authenticated: ' . $user->email);

            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'phone' => 'required|string|max:20|unique:users,phone,' . $user->id,
                'gender' => 'nullable|in:male,female,other',
                'date_of_birth' => 'nullable|date|before:today',
                'ghana_card_number' => 'nullable|string|max:50',
                'license_number' => 'nullable|string|max:50',
                'specialization' => 'nullable|string|max:255',
                'years_of_experience' => 'nullable|integer|min:0|max:100',
            ]);

            if ($validator->fails()) {
                \Log::error('❌ Validation failed:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            \Log::info('✅ Validation passed');

            DB::beginTransaction();

            try {
                $updateData = [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'gender' => $request->gender,
                    'date_of_birth' => $request->date_of_birth,
                    'ghana_card_number' => $request->ghana_card_number,
                    'license_number' => $request->license_number,
                    'specialization' => $request->specialization,
                    'years_experience' => $request->years_of_experience,
                ];
                
                \Log::info('📝 Updating user with data:', $updateData);
                
                $user->update($updateData);

                DB::commit();
                
                \Log::info('✅ User updated successfully');

                $user->refresh();

                return response()->json([
                    'success' => true,
                    'message' => 'Profile updated successfully',
                    'data' => $this->formatUserData($user)
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('❌ Database error: ' . $e->getMessage());
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('❌ Error updating profile: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Upload profile avatar
     * POST /api/mobile/profile/avatar
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'avatar' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
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
                // Delete old avatar if exists
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }

                // Process and store new avatar
                $image = $request->file('avatar');
                $filename = 'avatars/' . $user->id . '_' . time() . '.' . $image->getClientOriginalExtension();
                
                // Resize image to 400x400
                $img = Image::make($image)->fit(400, 400);
                
                // Save to storage
                Storage::disk('public')->put($filename, (string) $img->encode());
                
                // Update user avatar path
                $user->update([
                    'avatar' => $filename,
                    'avatar_url' => Storage::url($filename)
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Avatar uploaded successfully',
                    'data' => [
                        'avatar' => $user->avatar,
                        'avatar_url' => $user->avatar_url
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error uploading avatar: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload avatar',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Change user password
     * POST /api/mobile/profile/change-password
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            \Log::info('🔵 Change password request received');
            
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
                'new_password_confirmation' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 422);
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            \Log::info('✅ Password updated successfully for user: ' . $user->email);

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('❌ Error changing password: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to change password'
            ], 500);
        }
    }

    /**
     * Enable two-factor authentication (Send verification code)
     * POST /api/mobile/profile/enable-2fa
     */
    public function enableTwoFactor(Request $request): JsonResponse
    {
        try {
            \Log::info('🔵 Enable 2FA request received');
            
            $user = auth()->user();
            
            if (!$user) {
                \Log::error('❌ No authenticated user found');
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            \Log::info('✅ User authenticated: ' . $user->email);

            $validator = Validator::make($request->all(), [
                'method' => 'required|in:email,sms,biometric'
            ]);

            if ($validator->fails()) {
                \Log::error('❌ Validation failed:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $method = $request->method;

            // Biometric doesn't need server-side verification
            if ($method === 'biometric') {
                DB::beginTransaction();
                try {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'two_factor_enabled' => 1,
                            'two_factor_method' => 'biometric',
                            'two_factor_enabled_at' => Carbon::now(),
                            'two_factor_disabled_at' => null,
                            'updated_at' => Carbon::now()
                        ]);

                    DB::commit();
                    $user->refresh();

                    \Log::info('✅ Biometric 2FA enabled for user: ' . $user->email);

                    return response()->json([
                        'success' => true,
                        'message' => 'Biometric authentication enabled successfully',
                        'data' => [
                            'two_factor_enabled' => (bool) $user->two_factor_enabled,
                            'two_factor_method' => $user->two_factor_method,
                            'requires_verification' => false
                        ]
                    ], 200);
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }

            // For SMS and Email, generate and send OTP
            $otp = $this->generateOtp();
            $expiresAt = Carbon::now()->addMinutes(10);

            DB::beginTransaction();

            try {
                // Store OTP temporarily (will be validated before enabling 2FA)
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'two_factor_temp_token' => Hash::make($otp),
                        'two_factor_temp_method' => $method,
                        'two_factor_temp_expires' => $expiresAt,
                        'updated_at' => Carbon::now()
                    ]);

                DB::commit();

                // Send verification code
                if ($method === 'email') {
                    $user->notify(new TwoFactorAuthNotification($otp, $expiresAt));
                    
                    \Log::info('✅ 2FA verification code sent via email to: ' . $user->email);

                    return response()->json([
                        'success' => true,
                        'message' => 'Verification code sent to your email',
                        'data' => [
                            'method' => 'email',
                            'email' => $this->maskEmail($user->email),
                            'requires_verification' => true,
                            'expires_in' => 10
                        ]
                    ], 200);
                } else { // SMS
                    $smsResult = $this->smsService->sendOtp($user->phone, $otp);

                    if (!$smsResult['success']) {
                        \Log::error('❌ Failed to send OTP SMS', [
                            'phone' => $user->phone,
                            'error' => $smsResult['message']
                        ]);
                        
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to send verification code. Please try again.'
                        ], 500);
                    }

                    \Log::info('✅ 2FA verification code sent via SMS to: ' . $user->phone);

                    return response()->json([
                        'success' => true,
                        'message' => 'Verification code sent to your phone',
                        'data' => [
                            'method' => 'sms',
                            'phone' => $this->maskPhone($user->phone),
                            'requires_verification' => true,
                            'expires_in' => 10
                        ]
                    ], 200);
                }

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('❌ Database error: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('❌ Error enabling 2FA: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to enable two-factor authentication',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Verify OTP and complete 2FA setup
     * POST /api/mobile/profile/verify-2fa
     */
    public function verifyTwoFactorOtp(Request $request): JsonResponse
    {
        try {
            \Log::info('🔵 Verify 2FA OTP request received');
            
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'otp' => 'required|string|size:6'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user has a pending 2FA setup
            if (!$user->two_factor_temp_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending two-factor authentication setup found'
                ], 400);
            }

            // Verify OTP
            if (!Hash::check($request->otp, $user->two_factor_temp_token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification code'
                ], 400);
            }

            // Check expiration
            if (Carbon::now()->gt($user->two_factor_temp_expires)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verification code has expired. Please request a new one.'
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Enable 2FA with the verified method
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'two_factor_enabled' => 1,
                        'two_factor_method' => $user->two_factor_temp_method,
                        'two_factor_enabled_at' => Carbon::now(),
                        'two_factor_disabled_at' => null,
                        'two_factor_temp_token' => null,
                        'two_factor_temp_method' => null,
                        'two_factor_temp_expires' => null,
                        'updated_at' => Carbon::now()
                    ]);

                DB::commit();

                $user->refresh();

                \Log::info('✅ 2FA verified and enabled for user: ' . $user->email);
                \Log::info('📝 Method: ' . $user->two_factor_method);

                return response()->json([
                    'success' => true,
                    'message' => 'Two-factor authentication enabled successfully',
                    'data' => [
                        'two_factor_enabled' => (bool) $user->two_factor_enabled,
                        'two_factor_method' => $user->two_factor_method,
                        'two_factor_enabled_at' => $user->two_factor_enabled_at
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('❌ Database error: ' . $e->getMessage());
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('❌ Error verifying 2FA OTP: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify code'
            ], 500);
        }
    }

    /**
     * Resend 2FA verification code
     * POST /api/mobile/profile/resend-2fa-code
     */
    public function resendTwoFactorCode(Request $request): JsonResponse
    {
        try {
            \Log::info('🔵 Resend 2FA code request received');
            
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Check if user has a pending 2FA setup
            if (!$user->two_factor_temp_method) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending two-factor authentication setup found'
                ], 400);
            }

            $method = $user->two_factor_temp_method;
            $otp = $this->generateOtp();
            $expiresAt = Carbon::now()->addMinutes(10);

            DB::beginTransaction();

            try {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'two_factor_temp_token' => Hash::make($otp),
                        'two_factor_temp_expires' => $expiresAt,
                        'updated_at' => Carbon::now()
                    ]);

                DB::commit();

                // Resend code
                if ($method === 'email') {
                    $user->notify(new TwoFactorAuthNotification($otp, $expiresAt));
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'New verification code sent to your email',
                        'data' => [
                            'method' => 'email',
                            'expires_in' => 10
                        ]
                    ], 200);
                } else { // SMS
                    $smsResult = $this->smsService->sendOtp($user->phone, $otp);

                    if (!$smsResult['success']) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to send verification code'
                        ], 500);
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'New verification code sent to your phone',
                        'data' => [
                            'method' => 'sms',
                            'expires_in' => 10
                        ]
                    ], 200);
                }

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('❌ Error resending 2FA code: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend code'
            ], 500);
        }
    }

    /**
     * Disable two-factor authentication
     * POST /api/mobile/profile/disable-2fa
     */
    public function disableTwoFactor(Request $request): JsonResponse
    {
        try {
            \Log::info('🔵 Disable 2FA request received');
            
            $user = auth()->user();
            
            if (!$user) {
                \Log::error('❌ No authenticated user found');
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            \Log::info('✅ User authenticated: ' . $user->email);

            DB::beginTransaction();

            try {
                // Update 2FA settings using query builder
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'two_factor_enabled' => 0,
                        'two_factor_method' => null,
                        'two_factor_disabled_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);

                DB::commit();

                \Log::info('✅ 2FA disabled for user: ' . $user->email);
                \Log::info('📝 Disabled at: ' . Carbon::now());

                // Refresh user to get updated data
                $user->refresh();

                return response()->json([
                    'success' => true,
                    'message' => 'Two-factor authentication disabled successfully',
                    'data' => [
                        'two_factor_enabled' => (bool) $user->two_factor_enabled,
                        'two_factor_method' => null,
                        'two_factor_disabled_at' => $user->two_factor_disabled_at
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('❌ Database error: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('❌ Error disabling 2FA: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to disable two-factor authentication',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update notification settings (Security settings - simple)
     * POST /api/mobile/profile/notification-settings
     */
    public function updateNotificationSettings(Request $request): JsonResponse
    {
        try {
            \Log::info('🔵 Update notification settings request received');
            \Log::info('Request data:', $request->all());
            
            $user = auth()->user();
            
            if (!$user) {
                \Log::error('❌ No authenticated user found');
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            \Log::info('✅ User authenticated: ' . $user->email);

            $validator = Validator::make($request->all(), [
                'email_notifications' => 'required|boolean',
                'login_alerts' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                \Log::error('❌ Validation failed:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Update or create notification settings
                $notificationSettings = MobileNotification::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'email_notifications' => $request->email_notifications,
                        'login_alerts' => $request->login_alerts
                    ]
                );

                DB::commit();

                \Log::info('✅ Notification settings updated for user: ' . $user->email);
                \Log::info('📝 Email notifications: ' . ($request->email_notifications ? 'enabled' : 'disabled'));
                \Log::info('📝 Login alerts: ' . ($request->login_alerts ? 'enabled' : 'disabled'));

                return response()->json([
                    'success' => true,
                    'message' => 'Notification settings updated successfully',
                    'data' => [
                        'email_notifications' => $notificationSettings->email_notifications,
                        'login_alerts' => $notificationSettings->login_alerts
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('❌ Database error: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('❌ Error updating notification settings: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification settings',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update comprehensive notification preferences
     * POST /api/mobile/profile/notification-preferences
     */
    public function updateNotificationPreferences(Request $request): JsonResponse
    {
        try {
            \Log::info('🔵 Update notification preferences request received');
            \Log::info('Request data:', $request->all());
            
            $user = auth()->user();
            
            if (!$user) {
                \Log::error('❌ No authenticated user found');
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            \Log::info('✅ User authenticated: ' . $user->email);

            $validator = Validator::make($request->all(), [
                'all_notifications' => 'sometimes|boolean',
                'new_patient_assignment' => 'sometimes|boolean',
                'careplan_updates' => 'sometimes|boolean',
                'patient_vitals_alert' => 'sometimes|boolean',
                'medication_reminders' => 'sometimes|boolean',
                'appointment_reminders' => 'sometimes|boolean',
                'vitals_tracking' => 'sometimes|boolean',
                'health_tips' => 'sometimes|boolean',              // ✅ Added
                'shift_reminders' => 'sometimes|boolean',
                'shift_changes' => 'sometimes|boolean',
                'clock_in_reminders' => 'sometimes|boolean',
                'transport_requests' => 'sometimes|boolean',
                'incident_reports' => 'sometimes|boolean',
                'system_updates' => 'sometimes|boolean',
                'security_alerts' => 'sometimes|boolean',
                'email_notifications' => 'sometimes|boolean',
                'sms_notifications' => 'sometimes|boolean',
                'quiet_hours_enabled' => 'sometimes|boolean',
                'quiet_hours_start' => 'nullable|string',
                'quiet_hours_end' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                \Log::error('❌ Validation failed:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            try {
                $data = ['user_id' => $user->id];
                
                $fields = [
                    'all_notifications',
                    'new_patient_assignment',
                    'careplan_updates',
                    'patient_vitals_alert',
                    'medication_reminders',
                    'appointment_reminders',
                    'vitals_tracking',
                    'health_tips',                  // ✅ Added
                    'shift_reminders',
                    'shift_changes',
                    'clock_in_reminders',
                    'transport_requests',
                    'incident_reports',
                    'system_updates',
                    'security_alerts',
                    'email_notifications',
                    'sms_notifications',
                    'quiet_hours_enabled',
                    'quiet_hours_start',
                    'quiet_hours_end',
                ];

                foreach ($fields as $field) {
                    if ($request->has($field)) {
                        $data[$field] = $request->input($field);
                    }
                }

                \Log::info('📦 Data to be saved:', $data);

                $preferences = NotificationPreference::updateOrCreate(
                    ['user_id' => $user->id],
                    $data
                );

                DB::commit();

                \Log::info('✅ Notification preferences updated for user: ' . $user->email);

                return response()->json([
                    'success' => true,
                    'message' => 'Notification preferences updated successfully',
                    'data' => $preferences
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('❌ Database error: ' . $e->getMessage());
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('❌ Error updating notification preferences: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification preferences',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    /**
     * Format user data for API response
     * IMPORTANT: Keep this consistent with AuthController::prepareUserData
     */
    private function formatUserData(User $user): array
    {
        $notificationSettings = MobileNotification::where('user_id', $user->id)->first();
        $notificationPreferences = NotificationPreference::where('user_id', $user->id)->first();

        return [
            'id' => $user->id,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'name' => $user->first_name . ' ' . $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'dob' => $user->date_of_birth,
            'dateOfBirth' => $user->date_of_birth,
            'ghanaCardNumber' => $user->ghana_card_number,
            'licenseNumber' => $user->license_number,
            'specialization' => $user->specialization,
            'yearsOfExperience' => $user->years_experience ?? 0,
            'avatar' => $user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . 
                urlencode($user->first_name . '+' . $user->last_name) . 
                '&background=199A8E&color=fff&size=200',
            'role' => $user->role,
            'isActive' => $user->is_active,
            'isVerified' => $user->is_verified,
            'twoFactorEnabled' => (bool) $user->two_factor_enabled,
            'twoFactorMethod' => $user->two_factor_method,
            'emailNotifications' => $notificationSettings ? $notificationSettings->email_notifications : true,
            'loginAlerts' => $notificationSettings ? $notificationSettings->login_alerts : true,
            'notificationPreferences' => $notificationPreferences ? [
                'all_notifications' => $notificationPreferences->all_notifications,
                'new_patient_assignment' => $notificationPreferences->new_patient_assignment,
                'careplan_updates' => $notificationPreferences->careplan_updates,
                'patient_vitals_alert' => $notificationPreferences->patient_vitals_alert,
                'medication_reminders' => $notificationPreferences->medication_reminders,
                'appointment_reminders' => $notificationPreferences->appointment_reminders,
                'vitals_tracking' => $notificationPreferences->vitals_tracking,
                'health_tips' => $notificationPreferences->health_tips,              // ✅ Added
                'shift_reminders' => $notificationPreferences->shift_reminders,
                'shift_changes' => $notificationPreferences->shift_changes,
                'clock_in_reminders' => $notificationPreferences->clock_in_reminders,
                'transport_requests' => $notificationPreferences->transport_requests,
                'incident_reports' => $notificationPreferences->incident_reports,
                'system_updates' => $notificationPreferences->system_updates,
                'security_alerts' => $notificationPreferences->security_alerts,
                'email_notifications' => $notificationPreferences->email_notifications,
                'sms_notifications' => $notificationPreferences->sms_notifications,
                'quiet_hours_enabled' => $notificationPreferences->quiet_hours_enabled,
                'quiet_hours_start' => $notificationPreferences->quiet_hours_start ? 
                    $notificationPreferences->quiet_hours_start->format('H:i') : null,
                'quiet_hours_end' => $notificationPreferences->quiet_hours_end ? 
                    $notificationPreferences->quiet_hours_end->format('H:i') : null,
            ] : null,
        ];
    }

    /**
     * Helper method to send SMS verification code
     * TODO: Implement actual SMS sending logic
     */
    private function sendSMSVerificationCode(User $user)
    {
        // Generate and send SMS verification code
        // Example: Use Twilio, AWS SNS, or other SMS service
    }

    /**
     * Helper method to send email verification code
     * TODO: Implement actual email sending logic
     */
    private function sendEmailVerificationCode(User $user)
    {
        // Generate and send email verification code
        // Example: Use Laravel Mail or notification system
    }

    /**
     * Generate 6-digit OTP
     */
    private function generateOtp(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Mask phone number for privacy
     */
    private function maskPhone(string $phone): string
    {
        $length = strlen($phone);
        if ($length <= 4) return $phone;
        
        return substr($phone, 0, 3) . str_repeat('*', $length - 6) . substr($phone, -3);
    }

    /**
     * Mask email for privacy
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return $email;
        
        $username = $parts[0];
        $domain = $parts[1];
        
        $usernameLength = strlen($username);
        if ($usernameLength <= 2) {
            $maskedUsername = $username[0] . '*';
        } else {
            $visibleChars = min(2, floor($usernameLength / 3));
            $maskedUsername = substr($username, 0, $visibleChars) 
                            . str_repeat('*', $usernameLength - ($visibleChars * 2)) 
                            . substr($username, -$visibleChars);
        }
        
        return $maskedUsername . '@' . $domain;
    }
}