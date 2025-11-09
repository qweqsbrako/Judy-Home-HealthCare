<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Services\SmsService;

class PasswordChangedNotification extends Notification implements ShouldQueue
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

    protected $newPassword;
    protected $isAdminReset;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $newPassword, bool $isAdminReset = true)
    {
        $this->newPassword = $newPassword;
        $this->isAdminReset = $isAdminReset;
        
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
                
                // Create password change message
                $message = "Dear {$notifiable->first_name},\n\n";
                
                if ($this->isAdminReset) {
                    $message .= "Your Judy Home Care account password has been reset by an administrator.\n\n";
                } else {
                    $message .= "Your Judy Home Care account password has been changed.\n\n";
                }
                
                $message .= "New Password: {$this->newPassword}\n\n";
                $message .= "Please login and change your password immediately.\n\n";
                $message .= "Login at: " . config('app.url') . "/login\n\n";
                
                if (!$this->isAdminReset) {
                    $message .= "If you didn't request this change, please contact support immediately.";
                }

                // Send SMS
                $smsResult = $smsService->send($notifiable->phone, $message);

                if ($smsResult['success'] ?? false) {
                    \Log::info('Password change SMS sent successfully', [
                        'user_id' => $notifiable->id,
                        'phone' => $notifiable->phone
                    ]);
                } else {
                    \Log::warning('Password change SMS failed', [
                        'user_id' => $notifiable->id,
                        'phone' => $notifiable->phone,
                        'error' => $smsResult['message'] ?? 'Unknown error'
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to send password change SMS', [
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
        $loginUrl = config('app.url') . '/login';
        
        $mailMessage = (new MailMessage)
            ->subject('🔐 Password Changed - Judy Home Care')
            ->greeting("Hello {$notifiable->first_name},");

        if ($this->isAdminReset) {
            $mailMessage
                ->line('Your account password has been reset by a system administrator.')
                ->line('This action was performed to ensure the security of your account.');
        } else {
            $mailMessage
                ->line('Your account password has been successfully changed.');
        }

        $mailMessage
            ->line('')
            ->line('**New Login Credentials:**')
            ->line("• **Email:** {$notifiable->email}")
            ->line("• **New Password:** `{$this->newPassword}`")
            ->line('')
            ->line('⚠️ **Important Security Notice:**')
            ->line('• You will be required to change this password after your next login')
            ->line('• Please sign in as soon as possible and set a new, secure password')
            ->line('• Do not share this password with anyone')
            ->line('')
            ->action('Sign In Now', $loginUrl);

        if (!$this->isAdminReset) {
            $mailMessage
                ->line('')
                ->line('**Did not request this change?**')
                ->line('If you did not request this password change, please contact our support team immediately as your account may be compromised.');
        }

        $mailMessage
            ->line('')
            ->line('For your security, this password should be changed immediately after logging in.')
            ->salutation('Best regards, The Judy Home Care Team');

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Your password has been changed',
            'type' => 'password_changed',
            'user_id' => $notifiable->id,
            'is_admin_reset' => $this->isAdminReset,
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Exception $exception)
    {
        \Log::error('Password change notification failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}