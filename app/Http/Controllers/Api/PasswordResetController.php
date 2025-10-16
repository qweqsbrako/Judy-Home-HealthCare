<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PasswordReset;
use App\Notifications\PasswordResetNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    /**
     * Send password reset link email
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function sendResetLinkEmail(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|max:255',
                'security_answer' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', $validator->errors(), 422);
            }

            // Check rate limiting
            $key = 'password-reset.' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 5)) {
                $seconds = RateLimiter::availableIn($key);
                return $this->errorResponse(
                    'Too many password reset attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.',
                    null,
                    429
                );
            }

            // Find user by email
            $user = User::where('email', $request->email)->first();

            // Always return success response for security (don't reveal if email exists)
            $response = [
                'success' => true,
                'message' => 'If an account with that email exists, we have sent password reset instructions.',
                'data' => [
                    'email_sent' => true,
                    'masked_email' => $this->maskEmail($request->email)
                ]
            ];

            // If user doesn't exist, just return success (security measure)
            if (!$user) {
                RateLimiter::hit($key, 300); // Still count the attempt
                return response()->json($response);
            }

            // Check if user account is active
            if (!$user->is_active) {
                // Log attempt on inactive account
                Log::warning('Password reset attempted on inactive account', [
                    'email' => $request->email,
                    'ip' => $request->ip()
                ]);
                return response()->json($response);
            }

            // Check security question if provided and user has one set
            if ($user->security_question && $request->security_answer) {
                if (!Hash::check($request->security_answer, $user->security_answer_hash)) {
                    RateLimiter::hit($key, 300);
                    return $this->errorResponse(
                        'Security answer is incorrect.',
                        ['security_answer' => ['The security answer is incorrect.']],
                        422
                    );
                }
            }

            // Delete any existing tokens for this user
            PasswordReset::where('email', $user->email)->delete();

            // Generate secure token
            $token = Str::random(64);
            $expiresAt = Carbon::now()->addMinutes(15); // 15 minutes expiry

            // Store password reset token
            PasswordReset::create([
                'email' => $user->email,
                'token' => Hash::make($token),
                'created_at' => now(),
                'expires_at' => $expiresAt,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Send reset email
            try {
                $resetUrl = config('app.url') . '/password-reset?' . http_build_query([
                    'token' => $token,
                    'email' => $user->email,
                    'timestamp' => time()
                ]);

                $user->notify(new PasswordResetNotification($resetUrl, $token, $expiresAt));

                // Log successful password reset request
                Log::info('Password reset email sent', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $request->ip(),
                    'expires_at' => $expiresAt
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to send password reset email', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage()
                ]);

                // Delete the token if email failed
                PasswordReset::where('email', $user->email)->delete();

                return $this->errorResponse(
                    'Failed to send password reset email. Please try again.',
                    null,
                    500
                );
            }

            // Increment rate limiter on successful attempt
            RateLimiter::hit($key, 300);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Password reset request error: ' . $e->getMessage(), [
                'email' => $request->email,
                'ip' => $request->ip()
            ]);

            return $this->errorResponse(
                'An error occurred while processing your request.',
                null,
                500
            );
        }
    }

    /**
     * Validate password reset token
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function validateToken(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
                'email' => 'required|email'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Invalid request', $validator->errors(), 422);
            }

            $email = $request->email;
            $token = $request->token;

            // Find the password reset record
            $passwordReset = PasswordReset::where('email', $email)
                ->where('used_at', null)
                ->orderBy('created_at', 'desc')
                ->first();

            // Check if token exists
            if (!$passwordReset) {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => 'Invalid or expired reset token.'
                ], 400);
            }

            // Check if token has expired
            if (Carbon::now()->isAfter($passwordReset->expires_at)) {
                // Clean up expired token
                $passwordReset->delete();
                
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'expired' => true,
                    'message' => 'Reset token has expired.'
                ], 400);
            }

            // Verify token
            if (!Hash::check($token, $passwordReset->token)) {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => 'Invalid reset token.'
                ], 400);
            }

            // Get user details
            $user = User::where('email', $email)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => 'User not found.'
                ], 404);
            }

            // Calculate remaining time
            $remainingMinutes = Carbon::now()->diffInMinutes($passwordReset->expires_at, false);
            $remainingSeconds = max(0, $remainingMinutes * 60);

            return response()->json([
                'success' => true,
                'valid' => true,
                'message' => 'Token is valid.',
                'data' => [
                    'user_email' => $user->email,
                    'user_name' => $user->full_name,
                    'expires_at' => $passwordReset->expires_at->toISOString(),
                    'remaining_seconds' => $remainingSeconds
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Token validation error: ' . $e->getMessage(), [
                'email' => $request->email,
                'ip' => $request->ip()
            ]);

            return $this->errorResponse(
                'An error occurred while validating the token.',
                null,
                500
            );
        }
    }

    /**
     * Reset user password
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function reset(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
                'email' => 'required|email',
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'max:255',
                    'confirmed',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/'
                ],
                'password_confirmation' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', $validator->errors(), 422);
            }

            $email = $request->email;
            $token = $request->token;
            $newPassword = $request->password;

            // Check rate limiting for password reset attempts
            $key = 'password-reset-confirm.' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 5)) {
                $seconds = RateLimiter::availableIn($key);
                return $this->errorResponse(
                    'Too many password reset attempts. Please try again later.',
                    null,
                    429
                );
            }

            // Find the password reset record
            $passwordReset = PasswordReset::where('email', $email)
                ->where('used_at', null)
                ->orderBy('created_at', 'desc')
                ->first();

            // Validate token exists and is not used
            if (!$passwordReset) {
                RateLimiter::hit($key, 300);
                return $this->errorResponse(
                    'Invalid or expired reset token.',
                    null,
                    400
                );
            }

            // Check if token has expired
            if (Carbon::now()->isAfter($passwordReset->expires_at)) {
                $passwordReset->delete();
                return $this->errorResponse(
                    'Reset token has expired. Please request a new one.',
                    null,
                    400
                );
            }

            // Verify token
            if (!Hash::check($token, $passwordReset->token)) {
                RateLimiter::hit($key, 300);
                return $this->errorResponse(
                    'Invalid reset token.',
                    null,
                    400
                );
            }

            // Find user
            $user = User::where('email', $email)->first();
            if (!$user) {
                return $this->errorResponse(
                    'User not found.',
                    null,
                    404
                );
            }

            // Check if user is active
            if (!$user->is_active) {
                return $this->errorResponse(
                    'Account is inactive. Please contact support.',
                    null,
                    403
                );
            }

            // Check if new password is different from current
            if (Hash::check($newPassword, $user->password)) {
                return $this->errorResponse(
                    'New password must be different from your current password.',
                    ['password' => ['New password must be different from current password.']],
                    422
                );
            }

            // Use database transaction for password update
            DB::transaction(function () use ($user, $newPassword, $passwordReset, $request) {
                // Update user password
                $user->update([
                    'password' => Hash::make($newPassword),
                    'password_changed_at' => now(),
                    'force_password_change' => false
                ]);

                // Mark token as used
                $passwordReset->update([
                    'used_at' => now(),
                    'used_ip' => $request->ip()
                ]);

                // Revoke all existing tokens (force re-login for security)
                $user->tokens()->delete();

                // Log password reset
                Log::info('Password reset successful', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $request->ip(),
                    'reset_token_created' => $passwordReset->created_at
                ]);
            });

            // Clear rate limiting on successful reset
            RateLimiter::clear($key);

            // Clean up old password reset tokens for this user
            PasswordReset::where('email', $email)
                ->where('created_at', '<', now()->subHours(24))
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully. Please login with your new password.',
                'data' => [
                    'password_reset' => true,
                    'user_email' => $user->email,
                    'tokens_revoked' => true
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Password reset error: ' . $e->getMessage(), [
                'email' => $request->email,
                'ip' => $request->ip()
            ]);

            return $this->errorResponse(
                'An error occurred while resetting your password. Please try again.',
                null,
                500
            );
        }
    }

    /**
     * Check password strength
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function checkPasswordStrength(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Invalid request', $validator->errors(), 422);
        }

        $password = $request->password;
        $strength = $this->calculatePasswordStrength($password);

        return response()->json([
            'success' => true,
            'data' => [
                'strength' => $strength,
                'requirements' => [
                    'length' => strlen($password) >= 8,
                    'uppercase' => preg_match('/[A-Z]/', $password),
                    'lowercase' => preg_match('/[a-z]/', $password),
                    'number' => preg_match('/\d/', $password),
                    'special' => preg_match('/[@$!%*?&]/', $password)
                ],
                'is_strong' => $strength['score'] >= 4,
                'recommendations' => $this->getPasswordRecommendations($password)
            ]
        ]);
    }

    // Private helper methods

    /**
     * Calculate password strength
     */
    private function calculatePasswordStrength(string $password): array
    {
        $score = 0;
        $feedback = [];

        // Length check
        if (strlen($password) >= 8) {
            $score++;
        } else {
            $feedback[] = 'Use at least 8 characters';
        }

        // Uppercase check
        if (preg_match('/[A-Z]/', $password)) {
            $score++;
        } else {
            $feedback[] = 'Include uppercase letters';
        }

        // Lowercase check
        if (preg_match('/[a-z]/', $password)) {
            $score++;
        } else {
            $feedback[] = 'Include lowercase letters';
        }

        // Number check
        if (preg_match('/\d/', $password)) {
            $score++;
        } else {
            $feedback[] = 'Include numbers';
        }

        // Special character check
        if (preg_match('/[@$!%*?&]/', $password)) {
            $score++;
        } else {
            $feedback[] = 'Include special characters (@$!%*?&)';
        }

        // Determine strength level
        $levels = [
            0 => ['level' => 'very_weak', 'text' => 'Very Weak', 'color' => '#dc3545'],
            1 => ['level' => 'weak', 'text' => 'Weak', 'color' => '#fd7e14'],
            2 => ['level' => 'fair', 'text' => 'Fair', 'color' => '#ffc107'],
            3 => ['level' => 'good', 'text' => 'Good', 'color' => '#20c997'],
            4 => ['level' => 'strong', 'text' => 'Strong', 'color' => '#198754'],
            5 => ['level' => 'very_strong', 'text' => 'Very Strong', 'color' => '#0d6efd']
        ];

        return [
            'score' => $score,
            'max_score' => 5,
            'percentage' => ($score / 5) * 100,
            'level' => $levels[$score]['level'],
            'text' => $levels[$score]['text'],
            'color' => $levels[$score]['color'],
            'feedback' => $feedback
        ];
    }

    /**
     * Get password recommendations
     */
    private function getPasswordRecommendations(string $password): array
    {
        $recommendations = [];

        if (strlen($password) < 12) {
            $recommendations[] = 'Consider using 12+ characters for better security';
        }

        // Check for common patterns
        if (preg_match('/123|abc|qwe/i', $password)) {
            $recommendations[] = 'Avoid common sequences like "123" or "abc"';
        }

        if (preg_match('/password|admin|user/i', $password)) {
            $recommendations[] = 'Avoid using common words like "password"';
        }

        // Check for repeated characters
        if (preg_match('/(.)\1{2,}/', $password)) {
            $recommendations[] = 'Avoid repeating the same character multiple times';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Your password meets our security requirements';
        }

        return $recommendations;
    }

    /**
     * Mask email for security
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $username = $parts[0];
        $domain = $parts[1];
        
        $maskedUsername = substr($username, 0, 1) . 
            str_repeat('*', max(0, strlen($username) - 2)) . 
            substr($username, -1);
            
        return $maskedUsername . '@' . $domain;
    }

    /**
     * Standard error response format
     */
    private function errorResponse(string $message, $errors = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }
}