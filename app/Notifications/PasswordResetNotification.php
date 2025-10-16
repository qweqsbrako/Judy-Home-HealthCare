<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class PasswordResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The password reset URL.
     */
    public string $resetUrl;

    /**
     * The reset token.
     */
    public string $token;

    /**
     * Token expiration time.
     */
    public Carbon $expiresAt;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $resetUrl, string $token, Carbon $expiresAt)
    {
        $this->resetUrl = $resetUrl;
        $this->token = $token;
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

        return (new MailMessage)
            ->subject('Reset Your Password - ' . $appName)
            ->view('emails.password-reset', [
                'user_name' => $notifiable->full_name,
                'reset_url' => $this->resetUrl,
                'expires_at' => $this->expiresAt->format('F j, Y \a\t g:i A'),
                'app_name' => $appName,
                'expiration_time' => $expirationTime
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'password_reset',
            'message' => 'Password reset link sent to your email',
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
        return ['password-reset', 'security', 'authentication'];
    }
}