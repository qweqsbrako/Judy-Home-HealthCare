<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;

class TwoFactorCodeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $code;
    public string $method;

    public function __construct(string $code, string $method = 'email')
    {
        $this->code = $code;
        $this->method = $method;
    }

    public function via(object $notifiable): array
    {
        return match($this->method) {
            'email' => ['mail'],
            'sms' => ['sms'],
            'voice' => ['voice'], 
            default => ['mail']
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = Config::get('app.name', 'Judy Home Care');

        return (new MailMessage)
            ->subject('Your Security Code - ' . $appName)
            ->view('emails.two-factor-code', [
                'user_name' => $notifiable->full_name,
                'verification_code' => $this->code,
                'method' => $this->method,
                'app_name' => $appName
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'two_factor_code',
            'message' => 'Two-factor authentication code sent',
            'method' => $this->method,
            'user_id' => $notifiable->id,
            'sent_at' => now()
        ];
    }
}