<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Notifications\PasswordResetNotification;
use App\Notifications\PasswordResetOtpNotification;
use App\Services\SmsService;

class AuthController extends Controller
{

    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'remember_me' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials.'
                ], 401);
            }

            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is inactive.'
                ], 403);
            }

            if (in_array($user->role, ['nurse', 'doctor']) && !$user->is_verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is pending verification.'
                ], 403);
            }

            $user->update([
                'last_login_at' => Carbon::now(),
                'last_login_ip' => $request->ip(),
            ]);

            $isWebRequest = $this->isWebRequest($request);

            if ($isWebRequest) {
                Auth::login($user, $request->input('remember_me', false));
                $userData = $this->prepareUserData($user);

                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'data' => [
                        'user' => $userData,
                        'redirect_to' => $this->getDashboardRoute($user->role)
                    ]
                ], 200);
            } else {
                //$user->tokens()->delete();
                $tokenName = $request->input('remember_me', false) ? 'remember-token' : 'auth-token';
                $expiresAt = $request->input('remember_me', false) ? now()->addDays(30) : now()->addDays(7);
                $token = $user->createToken($tokenName, ['*'], $expiresAt)->plainTextToken;
                $userData = $this->prepareUserData($user);

                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'data' => [
                        'user' => $userData,
                        'token' => $token,
                        'token_type' => 'Bearer',
                        'expires_at' => $expiresAt->toISOString(),
                        'redirect_to' => $this->getDashboardRoute($user->role)
                    ]
                ], 200);
            }
        } catch (\Exception $e) {
            \Log::error('Login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Login failed.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            if ($this->isWebRequest($request)) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            } else {
                if ($request->user()) {
                    $request->user()->currentAccessToken()->delete();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed'
            ], 500);
        }
    }

    public function me(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => $this->prepareUserData($user)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user data'
            ], 500);
        }
    }

    /**
     * Send password reset code via email or SMS
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact' => 'required|string',
            'contact_type' => 'required|in:email,phone'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $contactType = $request->contact_type;
            $contact = $request->contact;
            $isMobileRequest = !$this->isWebRequest($request);
            
            // Find user by email or phone
            $user = $contactType === 'email'
                ? User::where('email', $contact)->first()
                : User::where('phone', $contact)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => $contactType === 'email' 
                        ? 'No account found with this email address.' 
                        : 'No account found with this phone number.'
                ], 404);
            }

            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is inactive.'
                ], 403);
            }

            // MOBILE APP FLOW: Always use OTP for both email and phone
            if ($isMobileRequest) {
                $otp = $this->generateOtp();
                $expiresAt = Carbon::now()->addMinutes(10);

                $user->update([
                    'password_reset_token' => Hash::make($otp),
                    'password_reset_expires' => $expiresAt
                ]);

                if ($contactType === 'email') {
                    // Send OTP via email for mobile app
                    $user->notify(new PasswordResetOtpNotification($otp, $expiresAt));

                    return response()->json([
                        'success' => true,
                        'message' => 'Verification code sent to your email.',
                        'data' => [
                            'contact_type' => 'email',
                            'email' => $this->maskEmail($user->email),
                            'requires_verification' => true,
                            'expires_in' => 10 // minutes
                        ]
                    ], 200);
                } else {
                    // Send OTP via SMS for mobile app
                    $smsResult = $this->smsService->sendOtp($user->phone, $otp);

                    if (!$smsResult['success']) {
                        \Log::error('Failed to send OTP SMS', [
                            'phone' => $user->phone,
                            'error' => $smsResult['message']
                        ]);
                        
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to send verification code. Please try again.'
                        ], 500);
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'Verification code sent to your phone.',
                        'data' => [
                            'contact_type' => 'phone',
                            'phone' => $this->maskPhone($user->phone),
                            'requires_verification' => true,
                            'expires_in' => 10 // minutes
                        ]
                    ], 200);
                }
            } 
            // WEB FLOW: Email gets link, Phone gets OTP
            else {
                if ($contactType === 'email') {
                    // Send email with reset link for web
                    $token = Str::random(64);
                    $expiresAt = Carbon::now()->addMinutes(15);
                    
                    $user->update([
                        'password_reset_token' => Hash::make($token),
                        'password_reset_expires' => $expiresAt
                    ]);

                    $resetUrl = config('app.url') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);
                    $user->notify(new PasswordResetNotification($resetUrl, $token, $expiresAt));

                    return response()->json([
                        'success' => true,
                        'message' => 'Password reset link sent to your email.',
                        'data' => [
                            'contact_type' => 'email',
                            'requires_verification' => false,
                            'expires_in' => 15 // minutes
                        ]
                    ], 200);
                } else {
                    // Send SMS with OTP for web
                    $otp = $this->generateOtp();
                    $expiresAt = Carbon::now()->addMinutes(10);
                    
                    $user->update([
                        'password_reset_token' => Hash::make($otp),
                        'password_reset_expires' => $expiresAt
                    ]);

                    $smsResult = $this->smsService->sendOtp($user->phone, $otp);

                    if (!$smsResult['success']) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to send verification code. Please try again.'
                        ], 500);
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'Verification code sent to your phone.',
                        'data' => [
                            'contact_type' => 'phone',
                            'phone' => $this->maskPhone($user->phone),
                            'requires_verification' => true,
                            'expires_in' => 10 // minutes
                        ]
                    ], 200);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Forgot password error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process request. Please try again.'
            ], 500);
        }
    }

        /**
     * Verify OTP for phone-based reset
     */
    public function verifyResetOtp(Request $request)
    {
        \Log::info("What is here");
        \Log::info($request->all());
        $validator = Validator::make($request->all(), [
            'contact' => 'required|string',
            'contact_type' => 'required|in:email,phone',
            'otp' => 'required|string|size:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $contactType = $request->contact_type;
            $contact = $request->contact;

            $user = $contactType === 'email'
                ? User::where('email', $contact)->first()
                : User::where('phone', $contact)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => $contactType === 'email' ? 'Invalid email address.' : 'Invalid phone number.'
                ], 404);
            }

            if (!$user->password_reset_token || !Hash::check($request->otp, $user->password_reset_token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification code.'
                ], 400);
            }

            if (Carbon::now()->gt($user->password_reset_expires)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verification code has expired. Please request a new one.'
                ], 400);
            }

            // Generate a temporary token for password reset
            $resetToken = Str::random(64);
            $user->update([
                'password_reset_token' => Hash::make($resetToken),
                'password_reset_expires' => Carbon::now()->addMinutes(15)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Code verified successfully.',
                'data' => [
                    'reset_token' => $resetToken,
                    'contact' => $contact,
                    'contact_type' => $contactType,
                    'expires_in' => 15 // minutes
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Verify OTP error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify code.'
            ], 500);
        }
    }

        /**
     * Resend OTP
     */
    public function resendResetOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact' => 'required|string',
            'contact_type' => 'required|in:email,phone'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $contactType = $request->contact_type;
            $contact = $request->contact;

            $user = $contactType === 'email'
                ? User::where('email', $contact)->first()
                : User::where('phone', $contact)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => $contactType === 'email' ? 'Invalid email address.' : 'Invalid phone number.'
                ], 404);
            }

            $otp = $this->generateOtp();
            $expiresAt = Carbon::now()->addMinutes(10);
            
            $user->update([
                'password_reset_token' => Hash::make($otp),
                'password_reset_expires' => $expiresAt
            ]);

            if ($contactType === 'email') {
                // Resend OTP via email
                $user->notify(new PasswordResetOtpNotification($otp, $expiresAt));
            } else {
                // Resend OTP via SMS
                $smsResult = $this->smsService->sendOtp($user->phone, $otp);

                if (!$smsResult['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to send verification code.'
                    ], 500);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'New verification code sent.',
                'data' => [
                    'contact_type' => $contactType,
                    'expires_in' => 10
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Resend OTP error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resend code.'
            ], 500);
        }
    }


    /**
     * Reset password (works for both email and phone)
     */
    public function resetPassword(Request $request)
    {
        \Log::info('Token');
        \Log::info($request->all());
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'contact' => 'required|string', // email or phone
            'contact_type' => 'required|in:email,phone',
            'password' => 'required|string|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $contactType = $request->contact_type;
            $contact = $request->contact;
            
            $user = $contactType === 'email'
                ? User::where('email', $contact)->first()
                : User::where('phone', $contact)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.'
                ], 404);
            }

            if (!$user->password_reset_token || !Hash::check($request->token, $user->password_reset_token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired reset token.'
                ], 400);
            }

            if (Carbon::now()->gt($user->password_reset_expires)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reset token has expired.'
                ], 400);
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->password),
                'password_reset_token' => null,
                'password_reset_expires' => null,
                'password_changed_at' => Carbon::now()
            ]);

            // Delete all tokens for security
            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Password reset successful. You can now login with your new password.'
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Reset password error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password.'
            ], 500);
        }
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

    private function isWebRequest(Request $request)
    {
        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return true;
        }
        
        if ($request->header('X-Client-Type') === 'web') {
            return true;
        }
        
        $userAgent = $request->userAgent();
        $mobileKeywords = ['Mobile', 'Android', 'iPhone', 'iPad', 'Flutter', 'ReactNative', 'Dart'];
        
        foreach ($mobileKeywords as $keyword) {
            if (stripos($userAgent, $keyword) !== false) {
                return false;
            }
        }
        
        return true;
    }

    private function prepareUserData($user)
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->first_name . ' ' . $user->last_name,
            'email' => $user->email,
            'role' => $user->role,
            'avatar_url' => $user->avatar_url ?: $this->generateAvatarUrl($user),
            'is_active' => $user->is_active,
            'is_verified' => $user->is_verified,
            'last_login_at' => $user->last_login_at,
            'permissions' => $this->getUserPermissions($user),
            'dashboard_route' => $this->getDashboardRoute($user->role)
        ];
    }

    private function getDashboardRoute($role)
    {
        return '/dashboard';
    }

    private function getUserPermissions($user)
    {
        try {
            // If user has a role_id, get permissions through role
            if ($user->role_id) {
                $role = \App\Models\Role::with('permissions')->find($user->role_id);
                if ($role) {
                    return $role->permissions->pluck('name')->toArray();
                }
            }

            // Fallback: get permissions by role name
            $role = \App\Models\Role::with('permissions')->where('name', $user->role)->first();
            if ($role) {
                return $role->permissions->pluck('name')->toArray();
            }

            // If no role found, return empty array
            return [];
        } catch (\Exception $e) {
            \Log::error('Error getting user permissions: ' . $e->getMessage());
            return [];
        }
    }

    private function generateAvatarUrl($user)
    {
        $name = urlencode($user->first_name . '+' . $user->last_name);
        return "https://ui-avatars.com/api/?name={$name}&color=667eea&background=f8f9fa&size=200";
    }
}