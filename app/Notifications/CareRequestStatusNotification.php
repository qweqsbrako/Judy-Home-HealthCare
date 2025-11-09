<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\CareRequest;
use App\Models\CarePayment;
use App\Services\SmsService;
use App\Services\FcmNotificationService;
use App\Models\NotificationLog;

class CareRequestStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = 60;

    protected CareRequest $careRequest;
    protected ?CarePayment $payment;
    protected string $status;
    protected bool $isAdminCreated;

    public function __construct(CareRequest $careRequest, ?CarePayment $payment, string $status, bool $isAdminCreated = false)
    {
        $this->careRequest = $careRequest;
        $this->payment = $payment;
        $this->status = $status;
        $this->isAdminCreated = $isAdminCreated;
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if (!empty($notifiable->phone)) {
            $this->sendSms($notifiable);
        }

        $this->sendFcmNotification($notifiable);

        return $channels;
    }

    protected function sendSms(object $notifiable): void
    {
        try {
            $smsService = app(SmsService::class);
            $message = $this->buildSmsMessage($notifiable);

            $smsResult = $smsService->send($notifiable->phone, $message);

            if ($smsResult['success'] ?? false) {
                \Log::info('Care request SMS sent', [
                    'care_request_id' => $this->careRequest->id,
                    'status' => $this->status
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send SMS', [
                'care_request_id' => $this->careRequest->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function buildSmsMessage(object $notifiable): string
    {
        $careType = ucwords(str_replace('_', ' ', $this->careRequest->care_type));
        $message = "Dear {$notifiable->first_name},\n\n";

        switch ($this->status) {
            case 'pending_payment':
                $amount = number_format($this->payment->total_amount, 2);
                $message .= "Your care request has been created.\n\n";
                $message .= "Request ID: #{$this->careRequest->id}\n";
                $message .= "Care Type: {$careType}\n";
                $message .= "Assessment Fee: GHS {$amount}\n\n";
                $message .= "Please pay within 30 minutes.\n";
                break;

            case 'payment_received':
                $message .= "Payment received! Thank you.\n\n";
                $message .= "A qualified nurse will be assigned to you shortly.\n";
                break;

            case 'nurse_assigned':
                $nurseName = $this->careRequest->assignedNurse 
                    ? $this->careRequest->assignedNurse->first_name . ' ' . $this->careRequest->assignedNurse->last_name
                    : 'your nurse';
                $message .= "Nurse assigned!\n\n";
                $message .= "Nurse: {$nurseName}\n";
                $message .= "They will contact you to schedule an assessment.\n";
                break;

            case 'assessment_scheduled':
                $scheduledAt = $this->careRequest->assessment_scheduled_at 
                    ? $this->careRequest->assessment_scheduled_at->format('M d, Y \a\t g:i A')
                    : 'soon';
                $message .= "Assessment scheduled!\n\n";
                $message .= "Date: {$scheduledAt}\n";
                $message .= "Location: {$this->careRequest->service_address}\n";
                break;

            case 'assessment_completed':
            case 'under_review':
                $message .= "Assessment completed!\n\n";
                $message .= "Our care team is reviewing your assessment.\n";
                $message .= "We'll contact you soon with your care plan.\n";
                break;

            case 'care_plan_created':
            case 'awaiting_care_payment':
                $amount = $this->payment ? number_format($this->payment->total_amount, 2) : '0.00';
                $message .= "Your care plan is ready!\n\n";
                $message .= "Care Fee: GHS {$amount}\n";
                $message .= "Please review and pay to start care.\n";
                break;

            case 'care_payment_received':
                $message .= "Payment received! Thank you.\n\n";
                $message .= "Your care services will begin soon.\n";
                break;

            case 'care_active':
                $message .= "Care services started!\n\n";
                $message .= "Your nurse will be visiting according to your schedule.\n";
                break;
        }

        $message .= "\nLogin: " . config('app.url') . "/care-requests/{$this->careRequest->id}";
        $message .= "\n\nJudy Home Care";

        return $message;
    }

    protected function sendFcmNotification(object $notifiable): void
    {
        try {
            $fcmService = app(FcmNotificationService::class);
            [$title, $body] = $this->getFcmTitleAndBody();

            $notificationData = [
                'type' => $this->getFcmNotificationType(),
                'title' => $title,
                'body' => $body,
                'priority' => $this->getFcmPriority(),
                'data' => [
                    'care_request_id' => $this->careRequest->id,
                    'status' => $this->status,
                    'payment_id' => $this->payment?->id,
                    'is_admin_created' => $this->isAdminCreated,
                    'click_action' => 'OPEN_CARE_REQUEST',
                ],
                'notifiable_type' => CareRequest::class,
                'notifiable_id' => $this->careRequest->id,
            ];

            $fcmService->sendToUser($notifiable, $notificationData);
        } catch (\Exception $e) {
            \Log::error('Failed to send FCM', [
                'care_request_id' => $this->careRequest->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function getFcmTitleAndBody(): array
    {
        $careType = ucwords(str_replace('_', ' ', $this->careRequest->care_type));

        return match($this->status) {
            'pending_payment' => [
                '🏥 Care Request Created',
                "Your {$careType} request has been created. Please pay the assessment fee to proceed."
            ],
            'payment_received' => [
                '✅ Payment Received',
                "Thank you! A nurse will be assigned to you shortly."
            ],
            'nurse_assigned' => [
                '👨‍⚕️ Nurse Assigned',
                "A qualified nurse has been assigned to your care. They will contact you soon."
            ],
            'assessment_scheduled' => [
                '📅 Assessment Scheduled',
                "Your home assessment has been scheduled. Check details in the app."
            ],
            'assessment_completed', 'under_review' => [
                '✓ Assessment Complete',
                "Your assessment is complete and being reviewed by our care team."
            ],
            'care_plan_created', 'awaiting_care_payment' => [
                '📋 Care Plan Ready',
                "Your personalized care plan is ready. Please review and pay to start care."
            ],
            'care_payment_received' => [
                '✅ Payment Received',
                "Thank you! Your care services will begin soon."
            ],
            'care_active' => [
                '🎉 Care Started',
                "Your care services have started! Your nurse will visit according to schedule."
            ],
            default => ['📱 Care Request Update', 'Your care request has been updated.']
        };
    }

    protected function getFcmNotificationType(): string
    {
        return match($this->status) {
            'pending_payment' => NotificationLog::TYPE_CARE_REQUEST_CREATED,
            'nurse_assigned' => NotificationLog::TYPE_NURSE_ASSIGNED,
            'assessment_scheduled' => NotificationLog::TYPE_ASSESSMENT_SCHEDULED,
            'payment_received', 'care_payment_received' => NotificationLog::TYPE_PAYMENT_RECEIVED,
            'care_active' => NotificationLog::TYPE_CARE_STARTED,
            default => 'care_request_update'
        };
    }

    protected function getFcmPriority(): string
    {
        return match($this->status) {
            'pending_payment', 'awaiting_care_payment' => 'high',
            'assessment_scheduled', 'care_active' => 'urgent',
            default => 'normal'
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $careType = ucwords(str_replace('_', ' ', $this->careRequest->care_type));
        $subject = $this->getEmailSubject();

        $mailMessage = (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->first_name},");

        $this->buildEmailContent($mailMessage, $careType);

        return $mailMessage->salutation('Best regards, The Judy Home Care Team');
    }

    protected function getEmailSubject(): string
    {
        return match($this->status) {
            'pending_payment' => '🏥 Care Request Created - Payment Required',
            'payment_received' => '✅ Payment Received - Nurse Assignment',
            'nurse_assigned' => '👨‍⚕️ Nurse Assigned to Your Care',
            'assessment_scheduled' => '📅 Assessment Scheduled',
            'assessment_completed', 'under_review' => '✓ Assessment Complete - Under Review',
            'care_plan_created', 'awaiting_care_payment' => '📋 Care Plan Ready - Payment Required',
            'care_payment_received' => '✅ Payment Received - Care Starting Soon',
            'care_active' => '🎉 Care Services Started',
            default => '📱 Care Request Update'
        } . ' - Judy Home Care';
    }

    protected function buildEmailContent(MailMessage $mailMessage, string $careType): void
    {
        $requestUrl = config('app.url') . "/care-requests/{$this->careRequest->id}";

        $mailMessage->line("**Request ID:** #{$this->careRequest->id}")
                   ->line("**Care Type:** {$careType}")
                   ->line('');

        switch ($this->status) {
            case 'pending_payment':
                $amount = number_format($this->payment->total_amount, 2);
                $mailMessage
                    ->line('Your care request has been created and is awaiting payment.')
                    ->line("**Assessment Fee:** GHS {$amount}")
                    ->line("**Payment Expires:** " . $this->payment->expires_at->format('M d, Y \a\t g:i A'))
                    ->action('Pay Now', $requestUrl);
                break;

            case 'payment_received':
                $mailMessage
                    ->line('Thank you for your payment!')
                    ->line('A qualified nurse will be assigned to you shortly.')
                    ->action('View Request', $requestUrl);
                break;

            case 'nurse_assigned':
                $nurseName = $this->careRequest->assignedNurse 
                    ? $this->careRequest->assignedNurse->first_name . ' ' . $this->careRequest->assignedNurse->last_name
                    : 'your nurse';
                $mailMessage
                    ->line("A qualified nurse has been assigned to your care.")
                    ->line("**Nurse:** {$nurseName}")
                    ->line('They will contact you soon to schedule your home assessment.')
                    ->action('View Details', $requestUrl);
                break;

            case 'assessment_scheduled':
                $scheduledAt = $this->careRequest->assessment_scheduled_at 
                    ? $this->careRequest->assessment_scheduled_at->format('l, F d, Y \a\t g:i A')
                    : 'To be confirmed';
                $mailMessage
                    ->line('Your home assessment has been scheduled!')
                    ->line("**Date & Time:** {$scheduledAt}")
                    ->line("**Location:** {$this->careRequest->service_address}")
                    ->action('View Details', $requestUrl);
                break;

            case 'assessment_completed':
            case 'under_review':
                $mailMessage
                    ->line('Your home assessment has been completed.')
                    ->line('Our care team is now reviewing your assessment and will create a personalized care plan for you.')
                    ->action('View Status', $requestUrl);
                break;

            case 'care_plan_created':
            case 'awaiting_care_payment':
                $amount = $this->payment ? number_format($this->payment->total_amount, 2) : '0.00';
                $mailMessage
                    ->line('Your personalized care plan is ready!')
                    ->line("**Care Fee:** GHS {$amount}")
                    ->line('Please review your care plan and complete payment to begin services.')
                    ->action('View Care Plan & Pay', $requestUrl);
                break;

            case 'care_payment_received':
                $mailMessage
                    ->line('Thank you for your payment!')
                    ->line('Your care services will begin soon according to your schedule.')
                    ->action('View Care Plan', $requestUrl);
                break;

            case 'care_active':
                $mailMessage
                    ->line('Your care services have started!')
                    ->line('Your nurse will visit you according to your care schedule.')
                    ->action('View Schedule', $requestUrl);
                break;
        }
    }

    public function toArray(object $notifiable): array
    {
        return [
            'care_request_id' => $this->careRequest->id,
            'status' => $this->status,
            'payment_id' => $this->payment?->id,
            'is_admin_created' => $this->isAdminCreated,
            'type' => 'care_request_status_update',
        ];
    }

    public function failed(\Exception $exception)
    {
        \Log::error('Care request notification failed', [
            'care_request_id' => $this->careRequest->id,
            'status' => $this->status,
            'error' => $exception->getMessage()
        ]);
    }
}