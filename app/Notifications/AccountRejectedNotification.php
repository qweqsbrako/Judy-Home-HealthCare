<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Services\SmsService;

class AccountRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    protected $rejectionReason;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $rejectionReason)
    {
        $this->rejectionReason = $rejectionReason;
        
        // Set queue name
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Always send email
        $channels = ['mail'];

        // Send SMS if phone number exists
        if (!empty($notifiable->phone)) {
            try {
                $smsService = app(SmsService::class);
                
                // Create rejection message
                $message = "Dear {$notifiable->first_name},\n\n";
                $message .= "We regret to inform you that your Judy Home Care account application has been rejected.\n\n";
                $message .= "Reason: {$this->rejectionReason}\n\n";
                $message .= "If you believe this is an error or would like to appeal this decision, please contact our support team.\n\n";
                $message .= "Thank you for your understanding.";

                // Send SMS
                $smsResult = $smsService->send($notifiable->phone, $message);

                if ($smsResult['success'] ?? false) {
                    \Log::info('Account rejection SMS sent successfully', [
                        'user_id' => $notifiable->id,
                        'phone' => $notifiable->phone
                    ]);
                } else {
                    \Log::warning('Account rejection SMS failed', [
                        'user_id' => $notifiable->id,
                        'phone' => $notifiable->phone,
                        'error' => $smsResult['message'] ?? 'Unknown error'
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to send rejection SMS', [
                    'user_id' => $notifiable->id,
                    'phone' => $notifiable->phone,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $supportUrl = config('app.url') . '/support';

        return (new MailMessage)
            ->subject('❌ Account Application Rejected - Judy Home Care')
            ->greeting("Hello {$notifiable->first_name},")
            ->line('We regret to inform you that your account application for Judy Home Care has been rejected.')
            ->line('')
            ->line('**Rejection Reason:**')
            ->line($this->rejectionReason)
            ->line('')
            ->line('**What happens next?**')
            ->line('• Your account will not be activated at this time')
            ->line('• You may reapply if you address the issues mentioned above')
            ->line('• Contact our support team if you need clarification or wish to appeal')
            ->line('')
            ->action('Contact Support', $supportUrl)
            ->line('We appreciate your understanding and interest in Judy Home Care.')
            ->line('If you believe this decision was made in error, please reach out to our support team.')
            ->salutation('Best regards, The Judy Home Care Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Your account application has been rejected',
            'type' => 'account_rejection',
            'user_id' => $notifiable->id,
            'rejection_reason' => $this->rejectionReason,
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Exception $exception)
    {
        \Log::error('Account rejection notification failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}