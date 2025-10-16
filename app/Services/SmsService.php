<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $apiKey;
    protected $apiUrl;
    protected $sender;

    public function __construct()
    {
        $this->apiKey = config('services.mnotify.api_key');
        $this->apiUrl = config('services.mnotify.api_url', 'https://api.mnotify.com/api/sms/quick');
        $this->sender = config('services.mnotify.sender', 'Judy Care');
    }

    /**
     * Send SMS to single or multiple recipients
     *
     * @param string|array $recipients Phone number(s)
     * @param string $message SMS content
     * @param array $options Additional options
     * @return array Response with success status
     */
    public function send($recipients, string $message, array $options = []): array
    {
        try {
            // Ensure recipients is an array
            if (!is_array($recipients)) {
                $recipients = [$recipients];
            }

            // Clean and format phone numbers
            $recipients = array_map(function($phone) {
                return $this->formatPhoneNumber($phone);
            }, $recipients);

            // Prepare payload
            $payload = [
                'recipient' => $recipients,
                'sender' => $options['sender'] ?? $this->sender,
                'message' => $message,
                'is_schedule' => $options['is_schedule'] ?? false,
                'schedule_date' => $options['schedule_date'] ?? '',
            ];

            // Add SMS type if provided (for OTP, etc.)
            if (isset($options['sms_type'])) {
                $payload['sms_type'] = $options['sms_type'];
            }

            Log::info('Sending SMS', [
                'all' => $payload,
                'recipients' => $recipients,
                'sender' => $payload['sender'],
                'message_length' => strlen($message)
            ]);

            // Send request to mNotify API
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '?key=' . $this->apiKey, $payload);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['code']) && $responseData['code'] === '2000') {
                Log::info('SMS sent successfully', [
                    'recipients' => $recipients,
                    'response' => $responseData
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'data' => $responseData
                ];
            }

            Log::error('SMS sending failed', [
                'recipients' => $recipients,
                'response' => $responseData,
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'message' => $responseData['message'] ?? 'Failed to send SMS',
                'error' => $responseData
            ];

        } catch (\Exception $e) {
            Log::error('SMS Service Exception', [
                'recipients' => $recipients,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'SMS service error: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send schedule reminder SMS
     *
     * @param string $phone Recipient phone number
     * @param array $scheduleData Schedule details
     * @return array Response
     */
    public function sendScheduleReminder(string $phone, array $scheduleData): array
    {
        $message = $this->formatScheduleReminderMessage($scheduleData);
        
        return $this->send($phone, $message);
    }

    /**
     * Format phone number to standard format
     * Converts various formats to format accepted by mNotify
     *
     * @param string $phone
     * @return string
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If number starts with country code (233), remove it
        if (strlen($phone) === 12 && substr($phone, 0, 3) === '233') {
            $phone = '0' . substr($phone, 3);
        }

        // Ensure it starts with 0
        if (strlen($phone) === 9) {
            $phone = '0' . $phone;
        }

        return $phone;
    }

    /**
     * Format schedule reminder message
     *
     * @param array $data
     * @return string
     */
    protected function formatScheduleReminderMessage(array $data): string
    {
        $nurseName = $data['nurse_name'] ?? 'Nurse';
        $date = $data['date'] ?? '';
        $time = $data['time'] ?? '';
        $patient = $data['patient_name'] ?? '';
        $location = $data['location'] ?? '';

        $message = "Judy Home Care Reminder\n\n";
        $message .= "Dear {$nurseName},\n\n";
        $message .= "You have a scheduled shift:\n";
        $message .= "📅 Date: {$date}\n";
        $message .= "⏰ Time: {$time}\n";
        
        if ($patient) {
            $message .= "👤 Patient: {$patient}\n";
        }
        
        if ($location) {
            $message .= "📍 Location: {$location}\n";
        }
        
        $message .= "\nPlease confirm your availability.\n";
        $message .= "Thank you!";

        return $message;
    }

    /**
     * Send OTP via SMS
     *
     * @param string $phone
     * @param string $otp
     * @return array
     */
    public function sendOtp(string $phone, string $otp): array
    {
        $message = "Your Judy Home Care verification code is: {$otp}\n\n";
        $message .= "This code will expire in 10 minutes.\n";
        $message .= "Do not share this code with anyone.";

        return $this->send($phone, $message, ['sms_type' => 'otp']);
    }

    /**
     * Send account verification SMS
     *
     * @param string $phone
     * @param string $name
     * @return array
     */
    public function sendAccountVerified(string $phone, string $name): array
    {
        $message = "Dear {$name},\n\n";
        $message .= "Your Judy Home Care account has been verified successfully!\n\n";
        $message .= "You can now login and access all features.\n\n";
        $message .= "Thank you for choosing Judy Home Care.";

        return $this->send($phone, $message);
    }

    /**
     * Check SMS balance
     *
     * @return array
     */
    public function checkBalance(): array
    {
        try {
            $response = Http::get(config('services.mnotify.api_url') . '/balance', [
                'key' => $this->apiKey
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'balance' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to check balance'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to check SMS balance', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error checking balance: ' . $e->getMessage()
            ];
        }
    }
}