<?php

namespace App\Services;

use App\Models\User;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmNotificationService
{
    protected ?string $fcmServerKey;
    protected string $fcmUrl;

    public function __construct()
    {
        $this->fcmServerKey = config('services.fcm.server_key');
        $this->fcmUrl = 'https://fcm.googleapis.com/fcm/send';
        
        // Validate FCM configuration
        if (empty($this->fcmServerKey)) {
            \Log::warning('FCM Server Key not configured. Push notifications will not be sent.');
        }
    }
    
    /**
     * Check if FCM is configured
     */
    protected function isFcmConfigured(): bool
    {
        return !empty($this->fcmServerKey);
    }

    /**
     * Send notification to a user
     */
    public function sendToUser(User $user, array $notificationData): array
    {
        // Check if FCM is configured
        if (!$this->isFcmConfigured()) {
            // Still create the notification log for tracking
            $notificationLog = NotificationLog::createLog([
                'user_id' => $user->id,
                'user_type' => $user->user_type ?? 'patient',
                'type' => $notificationData['type'],
                'title' => $notificationData['title'],
                'body' => $notificationData['body'],
                'data' => $notificationData['data'] ?? [],
                'notifiable_type' => $notificationData['notifiable_type'] ?? null,
                'notifiable_id' => $notificationData['notifiable_id'] ?? null,
                'priority' => $notificationData['priority'] ?? 'normal',
            ]);
            
            $notificationLog->markAsFailed('FCM Server Key not configured');
            
            return [
                'success' => false,
                'message' => 'FCM is not configured. Please set FCM_SERVER_KEY in your .env file',
                'notification_log_id' => $notificationLog->id,
            ];
        }
        
        // Check if user has FCM token
        if (empty($user->fcm_token)) {
            return [
                'success' => false,
                'message' => 'User has no FCM token registered',
            ];
        }

        // Check user's notification preferences
        if (!$this->shouldSendNotification($user, $notificationData['type'])) {
            return [
                'success' => false,
                'message' => 'User has disabled this notification type',
            ];
        }

        // Check quiet hours
        if ($this->isQuietHours($user)) {
            return [
                'success' => false,
                'message' => 'User is in quiet hours',
                'schedule_later' => true,
            ];
        }

        // Create notification log
        $notificationLog = NotificationLog::createLog([
            'user_id' => $user->id,
            'user_type' => $user->user_type ?? 'patient',
            'type' => $notificationData['type'],
            'title' => $notificationData['title'],
            'body' => $notificationData['body'],
            'data' => $notificationData['data'] ?? [],
            'notifiable_type' => $notificationData['notifiable_type'] ?? null,
            'notifiable_id' => $notificationData['notifiable_id'] ?? null,
            'priority' => $notificationData['priority'] ?? 'normal',
        ]);

        // Send via FCM
        $result = $this->sendFcmMessage($user->fcm_token, $notificationData, $notificationLog);

        return $result;
    }

    /**
     * Send notification to multiple users
     */
    public function sendToMultipleUsers(array $users, array $notificationData): array
    {
        $results = [
            'total' => count($users),
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'details' => [],
        ];

        foreach ($users as $user) {
            $result = $this->sendToUser($user, $notificationData);
            
            if ($result['success']) {
                $results['success']++;
            } elseif (isset($result['schedule_later'])) {
                $results['skipped']++;
            } else {
                $results['failed']++;
            }

            $results['details'][] = [
                'user_id' => $user->id,
                'result' => $result,
            ];
        }

        return $results;
    }

    /**
     * Send FCM message
     */
    protected function sendFcmMessage(string $fcmToken, array $notificationData, NotificationLog $log): array
    {
        try {
            $payload = [
                'to' => $fcmToken,
                'notification' => [
                    'title' => $notificationData['title'],
                    'body' => $notificationData['body'],
                    'sound' => 'default',
                    'badge' => '1',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
                'data' => array_merge($notificationData['data'] ?? [], [
                    'notification_id' => $log->id,
                    'type' => $notificationData['type'],
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]),
                'priority' => $this->mapPriority($notificationData['priority'] ?? 'normal'),
            ];

            // Add Android-specific config
            $payload['android'] = [
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'care_notifications',
                    'sound' => 'default',
                    'color' => '#007AFF',
                ],
            ];

            // Add iOS-specific config
            $payload['apns'] = [
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'badge' => 1,
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['success']) && $responseData['success'] > 0) {
                // Mark as sent
                $log->update([
                    'sent_via_push' => true,
                    'fcm_message_id' => $responseData['results'][0]['message_id'] ?? null,
                    'fcm_response' => $responseData,
                ]);
                $log->markAsSent([
                    'fcm_message_id' => $responseData['results'][0]['message_id'] ?? null,
                    'fcm_response' => $responseData,
                ]);

                Log::info('FCM notification sent successfully', [
                    'notification_log_id' => $log->id,
                    'user_id' => $log->user_id,
                    'type' => $notificationData['type'],
                ]);

                return [
                    'success' => true,
                    'message' => 'Notification sent successfully',
                    'notification_log_id' => $log->id,
                    'fcm_response' => $responseData,
                ];
            }

            // Handle failure
            $errorMessage = $responseData['results'][0]['error'] ?? 'Unknown FCM error';
            $log->markAsFailed($errorMessage);

            Log::error('FCM notification failed', [
                'notification_log_id' => $log->id,
                'error' => $errorMessage,
                'response' => $responseData,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send notification: ' . $errorMessage,
                'notification_log_id' => $log->id,
            ];

        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());

            Log::error('FCM notification exception', [
                'notification_log_id' => $log->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'notification_log_id' => $log->id,
            ];
        }
    }

    /**
     * Check if notification should be sent based on user preferences
     */
    protected function shouldSendNotification(User $user, string $notificationType): bool
    {
        $preferences = $user->notificationPreference;

        if (!$preferences) {
            return true; // Send by default if no preferences set
        }

        // Check if all notifications are disabled
        if (!$preferences->all_notifications) {
            return false;
        }

        // Map notification types to preference fields
        $preferenceMap = [
            NotificationLog::TYPE_APPOINTMENT_REMINDER => 'appointment_reminders',
            NotificationLog::TYPE_MEDICATION_REMINDER => 'medication_reminders',
            NotificationLog::TYPE_VITALS_REMINDER => 'vitals_tracking',
            NotificationLog::TYPE_CARE_PLAN_UPDATE => 'careplan_updates',
            NotificationLog::TYPE_PAYMENT_REMINDER => 'all_notifications',
            NotificationLog::TYPE_NURSE_ASSIGNED => 'new_patient_assignment',
            NotificationLog::TYPE_ASSESSMENT_SCHEDULED => 'appointment_reminders',
        ];

        $preferenceField = $preferenceMap[$notificationType] ?? 'all_notifications';

        return $preferences->$preferenceField ?? true;
    }

    /**
     * Check if user is in quiet hours
     */
    protected function isQuietHours(User $user): bool
    {
        $preferences = $user->notificationPreference;

        if (!$preferences || !$preferences->quiet_hours_enabled) {
            return false;
        }

        $now = now();
        $start = $preferences->quiet_hours_start;
        $end = $preferences->quiet_hours_end;

        if (!$start || !$end) {
            return false;
        }

        $currentTime = $now->format('H:i');
        $startTime = $start->format('H:i');
        $endTime = $end->format('H:i');

        // Handle overnight quiet hours (e.g., 22:00 to 07:00)
        if ($startTime > $endTime) {
            return $currentTime >= $startTime || $currentTime <= $endTime;
        }

        return $currentTime >= $startTime && $currentTime <= $endTime;
    }

    /**
     * Map priority to FCM priority
     */
    protected function mapPriority(string $priority): string
    {
        return match($priority) {
            'urgent', 'high' => 'high',
            default => 'normal',
        };
    }

    /**
     * Send topic notification (for broadcast messages)
     */
    public function sendToTopic(string $topic, array $notificationData): array
    {
        if (!$this->isFcmConfigured()) {
            return [
                'success' => false,
                'message' => 'FCM is not configured',
            ];
        }
        
        try {
            $payload = [
                'to' => '/topics/' . $topic,
                'notification' => [
                    'title' => $notificationData['title'],
                    'body' => $notificationData['body'],
                    'sound' => 'default',
                ],
                'data' => $notificationData['data'] ?? [],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            return [
                'success' => $response->successful(),
                'response' => $response->json(),
            ];

        } catch (\Exception $e) {
            Log::error('FCM topic notification failed', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}