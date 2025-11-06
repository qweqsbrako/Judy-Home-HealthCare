<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationPendingNotification extends Notification
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
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🎉 Welcome! Your Account is Under Review')
            ->greeting("Hello {$notifiable->first_name}!")
            ->line('Thank you for registering as a healthcare professional with our platform.')
            ->line('Your account has been successfully created and is currently under review by our administrative team.')
            ->line('**What happens next?**')
            ->line('• Our team will verify your credentials and license information')
            ->line('• This process typically takes 24-48 hours')
            ->line('• You will receive an email and SMS notification once your account is approved')
            ->line('• After approval, you can immediately sign in and start using the platform')
            ->line('')
            ->line('**Your Registration Details:**')
            ->line("• **Name:** {$notifiable->first_name} {$notifiable->last_name}")
            ->line("• **Email:** {$notifiable->email}")
            ->line("• **Role:** " . ucfirst($notifiable->role))
            ->line("• **License Number:** {$notifiable->license_number}")
            ->line("• **Specialization:** " . ucwords(str_replace('_', ' ', $notifiable->specialization)))
            ->line('')
            ->line('We verify all healthcare professionals to ensure patient safety and maintain the highest standards of care.')
            ->line('If you have any questions or concerns, please don\'t hesitate to contact our support team.')
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
            //
        ];
    }
}