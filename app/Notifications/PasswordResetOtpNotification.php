<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class PasswordResetOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The OTP code.
     */
    public string $otp;

    /**
     * Token expiration time.
     */
    public Carbon $expiresAt;

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
        $appName = Config::get('app.name', 'Judy Home Care');
        $expirationTime = $this->expiresAt->diffForHumans();
        $expiresIn = $this->expiresAt->diffInMinutes(Carbon::now());

        // If custom template exists, use it
        if (view()->exists('emails.password-reset-otp')) {
            return (new MailMessage)
                ->subject('Password Reset Verification Code - ' . $appName)
                ->view('emails.password-reset-otp', [
                    'user_name' => $notifiable->first_name . ' ' . $notifiable->last_name,
                    'otp' => $this->otp,
                    'expires_at' => $this->expiresAt->format('F j, Y \a\t g:i A'),
                    'expires_in' => $expiresIn,
                    'app_name' => $appName
                ]);
        }

        // Fallback to default Laravel mail template
        return (new MailMessage)
            ->subject('Password Reset Verification Code - ' . $appName)
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->line('Your verification code is:')
            ->line('')
            ->line('**' . $this->otp . '**')
            ->line('')
            ->line('This code will expire ' . $expirationTime . ' (' . $this->expiresAt->format('F j, Y \a\t g:i A') . ').')
            ->line('If you did not request a password reset, no further action is required. Your account remains secure.')
            ->line('For security reasons, never share this code with anyone.')
            ->salutation('Best regards, ' . $appName . ' Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'password_reset_otp',
            'message' => 'Password reset OTP sent to your email',
            'expires_at' => $this->expiresAt,
            'user_id' => $notifiable->id
        ];
    }

    /**
     * Determine which queues should be used for each notification channel.
     *
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return [
            'mail' => 'notifications',
        ];
    }

    /**
     * Get the notification tags for monitoring.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['password-reset', 'otp', 'security', 'authentication'];
    }
}