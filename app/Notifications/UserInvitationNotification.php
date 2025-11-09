<?php

namespace App\Notifications;

use App\Models\User;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification implements ShouldQueue
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

    protected $temporaryPassword;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $temporaryPassword)
    {
        $this->temporaryPassword = $temporaryPassword;
        
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
                
                // Create welcome message
                $message = "Welcome to Judy Home Care!\n\n";
                $message .= "Dear {$notifiable->first_name},\n\n";
                $message .= "Your account has been created successfully.\n\n";
                $message .= "Email: {$notifiable->email}\n";
                $message .= "Temporary Password: {$this->temporaryPassword}\n\n";
                $message .= "Please login and change your password immediately.\n\n";
                $message .= "Login at: " . config('app.url') . "/login\n\n";
                $message .= "- Judy Home Care Team";

                // Send SMS
                $smsResult = $smsService->send($notifiable->phone, $message);

                if ($smsResult['success'] ?? false) {
                    \Log::info('User invitation SMS sent successfully', [
                        'user_id' => $notifiable->id,
                        'phone' => $notifiable->phone
                    ]);
                } else {
                    \Log::warning('User invitation SMS failed', [
                        'user_id' => $notifiable->id,
                        'phone' => $notifiable->phone,
                        'error' => $smsResult['message'] ?? 'Unknown error'
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to send user invitation SMS', [
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
        $loginUrl = config('app.frontend_url', config('app.url')) . '/login';
        $supportEmail = config('mail.support.address', 'support@judyhomecare.com');
        
        return (new MailMessage)
            ->subject('Welcome to Judy Home HealthCare - Complete Your Registration')
            ->greeting("Welcome, {$notifiable->first_name}!")
            ->line('Your account has been successfully created at Judy Home Care.')
            ->line('')
            ->line('**Your Login Credentials:**')
            ->line("• **Email:** {$notifiable->email}")
            ->line("• **Temporary Password:** `{$this->temporaryPassword}`")
            ->line('')
            ->line('🔐 **Security Notice:**')
            ->line('• This is a temporary password that must be changed after your first login')
            ->line('• Please keep this information secure and do not share it with anyone')
            ->line('• You will be prompted to create a new password upon signing in')
            ->line('')
            ->action('Complete Your Registration', $loginUrl)
            ->line('')
            ->line('**Getting Started:**')
            ->line('1. Click the button above or visit the login page')
            ->line('2. Enter your email and temporary password')
            ->line('3. Follow the prompts to create your new password')
            ->line('4. Complete your profile setup')
            ->line('')
            ->line("Need help? Contact our support team at {$supportEmail}")
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
            'message' => 'Welcome to Judy Home Care',
            'type' => 'user_invitation',
            'user_id' => $notifiable->id,
            'user_role' => $notifiable->role,
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Exception $exception)
    {
        \Log::error('User invitation notification failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}