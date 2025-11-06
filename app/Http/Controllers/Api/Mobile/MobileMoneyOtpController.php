<?php
// app/Http/Controllers/API/Mobile/MobileMoneyOtpController.php

namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileMoneyOtp;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MobileMoneyOtpController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Validate network prefix matches phone number
     */
    private function validateNetworkPrefix(string $phoneNumber, string $network): bool
    {
        $prefixes = [
            'MTN Mobile Money' => ['024', '054', '055', '059', '025'],
            'Vodafone Cash' => ['020', '050'],
            'AirtelTigo Money' => ['027', '057', '026', '056'],
        ];

        $prefix = substr($phoneNumber, 0, 3);
        
        return isset($prefixes[$network]) && in_array($prefix, $prefixes[$network]);
    }

    /**
     * Send OTP to mobile money number
     * POST /api/mobile/payments/send-otp
     */
    public function sendOtp(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone_number' => 'required|string|size:10|regex:/^0[0-9]{9}$/',
                'network' => 'required|string|in:MTN Mobile Money,Vodafone Cash,AirtelTigo Money',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $phoneNumber = $request->phone_number;
            $network = $request->network;

            // Validate network prefix
            if (!$this->validateNetworkPrefix($phoneNumber, $network)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This phone number does not match the selected network.',
                ], 400);
            }

            // Check for recent OTP requests (rate limiting - 1 per minute)
            $recentOtp = MobileMoneyOtp::where('user_id', $user->id)
                ->where('phone_number', $phoneNumber)
                ->where('created_at', '>', now()->subMinute())
                ->first();

            if ($recentOtp) {
                $waitTime = 60 - now()->diffInSeconds($recentOtp->created_at);
                return response()->json([
                    'success' => false,
                    'message' => "Please wait {$waitTime} seconds before requesting another OTP.",
                    'data' => [
                        'wait_seconds' => $waitTime,
                    ]
                ], 429);
            }

            DB::beginTransaction();

            try {
                // Generate 6-digit OTP
                $otpCode = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

                // Create OTP record
                $otp = MobileMoneyOtp::create([
                    'user_id' => $user->id,
                    'phone_number' => $phoneNumber,
                    'network' => $network,
                    'otp_code' => $otpCode,
                    'expires_at' => now()->addMinutes(5),
                    'verified' => false,
                    'attempts' => 0,
                ]);

                // Send SMS using your existing mNotify service
                $smsResult = $this->smsService->sendOtp($phoneNumber, $otpCode);

                if (!$smsResult['success']) {
                    DB::rollBack();
                    
                    Log::error('Failed to send OTP SMS', [
                        'phone' => $phoneNumber,
                        'error' => $smsResult
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to send OTP. Please try again.',
                    ], 500);
                }

                DB::commit();

                Log::info('OTP sent successfully', [
                    'user_id' => $user->id,
                    'phone' => $phoneNumber,
                    'otp_id' => $otp->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent successfully to ' . $phoneNumber,
                    'data' => [
                        'otp_id' => $otp->id,
                        'phone_number' => $phoneNumber,
                        'network' => $network,
                        'expires_at' => $otp->expires_at->toIso8601String(),
                        'expires_in_seconds' => 300, // 5 minutes
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Send OTP error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending OTP.',
            ], 500);
        }
    }

    /**
     * Verify OTP code
     * POST /api/mobile/payments/verify-otp
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone_number' => 'required|string|size:10',
                'otp_code' => 'required|string|size:6|regex:/^[0-9]{6}$/',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $phoneNumber = $request->phone_number;
            $otpCode = $request->otp_code;

            // Find the most recent unverified OTP for this phone number
            $otp = MobileMoneyOtp::where('user_id', $user->id)
                ->where('phone_number', $phoneNumber)
                ->where('verified', false)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$otp) {
                return response()->json([
                    'success' => false,
                    'message' => 'No OTP found for this phone number. Please request a new one.',
                ], 404);
            }

            // Check if OTP is expired
            if ($otp->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP has expired. Please request a new one.',
                    'data' => [
                        'expired' => true,
                    ]
                ], 400);
            }

            // Check attempts limit
            if (!$otp->canRetry()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many failed attempts. Please request a new OTP.',
                    'data' => [
                        'max_attempts_reached' => true,
                    ]
                ], 400);
            }

            // Verify OTP code
            if ($otp->otp_code !== $otpCode) {
                $otp->incrementAttempts();

                $remainingAttempts = 3 - $otp->attempts;

                return response()->json([
                    'success' => false,
                    'message' => "Invalid OTP code. {$remainingAttempts} attempts remaining.",
                    'data' => [
                        'attempts_remaining' => $remainingAttempts,
                    ]
                ], 400);
            }

            // Mark OTP as verified
            $otp->markAsVerified();

            Log::info('OTP verified successfully', [
                'user_id' => $user->id,
                'phone' => $phoneNumber,
                'otp_id' => $otp->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Phone number verified successfully.',
                'data' => [
                    'verified' => true,
                    'phone_number' => $phoneNumber,
                    'network' => $otp->network,
                    'verified_at' => $otp->verified_at->toIso8601String(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Verify OTP error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while verifying OTP.',
            ], 500);
        }
    }

    /**
     * Resend OTP
     * POST /api/mobile/payments/resend-otp
     */
    public function resendOtp(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone_number' => 'required|string|size:10',
                'network' => 'required|string|in:MTN Mobile Money,Vodafone Cash,AirtelTigo Money',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $phoneNumber = $request->phone_number;
            $network = $request->network;

            // Check for recent OTP requests (rate limiting - 1 per minute)
            $recentOtp = MobileMoneyOtp::where('user_id', $user->id)
                ->where('phone_number', $phoneNumber)
                ->where('created_at', '>', now()->subMinute())
                ->first();

            if ($recentOtp) {
                $waitTime = 60 - now()->diffInSeconds($recentOtp->created_at);
                return response()->json([
                    'success' => false,
                    'message' => "Please wait {$waitTime} seconds before requesting another OTP.",
                    'data' => [
                        'wait_seconds' => $waitTime,
                    ]
                ], 429);
            }

            DB::beginTransaction();

            try {
                // Generate new 6-digit OTP
                $otpCode = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

                // Invalidate any previous unverified OTPs for this phone
                MobileMoneyOtp::where('user_id', $user->id)
                    ->where('phone_number', $phoneNumber)
                    ->where('verified', false)
                    ->update(['expires_at' => now()]);

                // Create new OTP record
                $otp = MobileMoneyOtp::create([
                    'user_id' => $user->id,
                    'phone_number' => $phoneNumber,
                    'network' => $network,
                    'otp_code' => $otpCode,
                    'expires_at' => now()->addMinutes(5),
                    'verified' => false,
                    'attempts' => 0,
                ]);

                // Send SMS
                $smsResult = $this->smsService->sendOtp($phoneNumber, $otpCode);

                if (!$smsResult['success']) {
                    DB::rollBack();
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to resend OTP. Please try again.',
                    ], 500);
                }

                DB::commit();

                Log::info('OTP resent successfully', [
                    'user_id' => $user->id,
                    'phone' => $phoneNumber,
                    'otp_id' => $otp->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'OTP resent successfully.',
                    'data' => [
                        'otp_id' => $otp->id,
                        'phone_number' => $phoneNumber,
                        'expires_at' => $otp->expires_at->toIso8601String(),
                        'expires_in_seconds' => 300,
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Resend OTP error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while resending OTP.',
            ], 500);
        }
    }

    /**
     * Check if phone number is already verified
     * GET /api/mobile/payments/check-verification
     */
    public function checkVerification(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone_number' => 'required|string|size:10',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $phoneNumber = $request->phone_number;

            // Check for verified OTP within last 30 minutes
            $verifiedOtp = MobileMoneyOtp::where('user_id', $user->id)
                ->where('phone_number', $phoneNumber)
                ->where('verified', true)
                ->where('verified_at', '>', now()->subMinutes(30))
                ->latest('verified_at')
                ->first();

            if ($verifiedOtp) {
                return response()->json([
                    'success' => true,
                    'message' => 'Phone number is verified.',
                    'data' => [
                        'verified' => true,
                        'phone_number' => $phoneNumber,
                        'network' => $verifiedOtp->network,
                        'verified_at' => $verifiedOtp->verified_at->toIso8601String(),
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Phone number not verified.',
                'data' => [
                    'verified' => false,
                    'phone_number' => $phoneNumber,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Check verification error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking verification.',
            ], 500);
        }
    }
}