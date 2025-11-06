<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class TwoFactorAuthNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $otp;
    protected $expiresAt;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $otp, Carbon $expiresAt)
    {
        $this->otp = $otp;
        $this->expiresAt = $expiresAt;
    }

    /**
     * Get the notification's delivery channels.
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
        $appName = config('app.name');
        $expiresIn = (int) ceil(now()->diffInMinutes($this->expiresAt, false));

        return (new MailMessage)
            ->subject('Two-Factor Authentication Code - ' . $appName)
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('You are receiving this email because you are enabling two-factor authentication for your account.')
            ->line('Your verification code is:')
            ->line('**' . $this->otp . '**')
            ->line('This code will expire in ' . $expiresIn . ' minutes.')
            ->line('If you did not request this code, please ignore this email and ensure your account is secure.')
            ->line('For security reasons, never share this code with anyone.')
            ->salutation('Best regards, The ' . $appName . ' Team');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'otp' => $this->otp,
            'expires_at' => $this->expiresAt->toDateTimeString()
        ];
    }
}