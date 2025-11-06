<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Services\SmsService;

class AccountApprovedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     * Send both email and SMS
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
                
                // Create approval message
                $message = "Dear {$notifiable->first_name},\n\n";
                $message .= "Great news! Your Judy Home Care healthcare professional account has been approved.\n\n";
                $message .= "You can now sign in and start using the platform.\n\n";
                $message .= "Welcome to our healthcare community!";
                
                // Send SMS using the correct method signature
                $smsResult = $smsService->send($notifiable->phone, $message);
                
                if ($smsResult['success'] ?? false) {
                    \Log::info('Account approval SMS sent successfully', [
                        'user_id' => $notifiable->id,
                        'phone' => $notifiable->phone
                    ]);
                } else {
                    \Log::warning('Account approval SMS failed', [
                        'user_id' => $notifiable->id,
                        'phone' => $notifiable->phone,
                        'error' => $smsResult['message'] ?? 'Unknown error'
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to send approval SMS', [
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
        
        return (new MailMessage)
            ->subject('✅ Your Account Has Been Approved!')
            ->greeting("Congratulations {$notifiable->first_name}!")
            ->line('Great news! Your healthcare professional account has been approved by our administrative team.')
            ->line('You can now sign in and start using the platform to provide excellent healthcare services.')
            ->line('')
            ->line('**Account Details:**')
            ->line("• **Name:** {$notifiable->first_name} {$notifiable->last_name}")
            ->line("• **Email:** {$notifiable->email}")
            ->line("• **Role:** " . ucfirst($notifiable->role))
            ->line("• **Specialization:** " . ucwords(str_replace('_', ' ', $notifiable->specialization ?? 'N/A')))
            ->line("• **Status:** ✓ Verified & Active")
            ->line('')
            ->action('Sign In Now', $loginUrl)
            ->line('Welcome to our healthcare community! We\'re excited to have you on board.')
            ->line('If you have any questions or need assistance getting started, our support team is here to help.')
            ->salutation('Best regards, The Healthcare Management Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Your account has been approved',
            'type' => 'account_approval',
            'user_id' => $notifiable->id,
        ];
    }
}