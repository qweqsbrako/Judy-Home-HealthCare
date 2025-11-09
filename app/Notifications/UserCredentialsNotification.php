<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Services\SmsService;

class UserCredentialsNotification extends Notification implements ShouldQueue
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

    protected $password;
    protected $isTemporary;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $password, bool $isTemporary = false)
    {
        $this->password = $password;
        $this->isTemporary = $isTemporary;
        
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
                
                // Create credentials message
                $message = "Dear {$notifiable->first_name},\n\n";
                $message .= "Your Judy Home Care account has been created.\n\n";
                $message .= "Email: {$notifiable->email}\n";
                $message .= "Password: {$this->password}\n\n";
                
                if ($this->isTemporary) {
                    $message .= "This is a temporary password. Please change it after your first login.\n\n";
                }
                
                $message .= "Login at: " . config('app.url') . "/login\n\n";
                $message .= "Keep this information secure!";

                // Send SMS
                $smsResult = $smsService->send($notifiable->phone, $message);

                if ($smsResult['success'] ?? false) {
                    \Log::info('User credentials SMS sent successfully', [
                        'user_id' => $notifiable->id,
                        'phone' => $notifiable->phone
                    ]);
                } else {
                    \Log::warning('User credentials SMS failed', [
                        'user_id' => $notifiable->id,
                        'phone' => $notifiable->phone,
                        'error' => $smsResult['message'] ?? 'Unknown error'
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to send credentials SMS', [
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
            ->subject('🎉 Welcome to Judy Home Care - Your Account Credentials')
            ->greeting("Welcome {$notifiable->first_name}!")
            ->line('Your healthcare professional account has been successfully created.')
            ->line('Below are your login credentials:')
            ->line('')
            ->line('**Login Credentials:**')
            ->line("• **Email:** {$notifiable->email}")
            ->line("• **Password:** `{$this->password}`")
            ->line("• **Role:** " . ucfirst($notifiable->role))
            ->line('');

        if ($this->isTemporary) {
            $mailMessage
                ->line('⚠️ **Important:** This is a temporary password.')
                ->line('For security purposes, you will be required to change your password after your first login.')
                ->line('');
        }

        $mailMessage
            ->action('Sign In Now', $loginUrl)
            ->line('**Security Tips:**')
            ->line('• Keep your password confidential')
            ->line('• Do not share your credentials with anyone')
            ->line('• Change your password regularly')
            ->line('• Use a strong, unique password')
            ->line('')
            ->line('If you did not request this account or have any questions, please contact our support team immediately.')
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
            'message' => 'Your account credentials',
            'type' => 'user_credentials',
            'user_id' => $notifiable->id,
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Exception $exception)
    {
        \Log::error('User credentials notification failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}