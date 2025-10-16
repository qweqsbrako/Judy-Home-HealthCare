<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class UserVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $verificationNotes;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $verificationNotes = null)
    {
        $this->user = $user;
        $this->verificationNotes = $verificationNotes;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Account Verified - Welcome to Judy Home HealthCare Portal',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.user-verification',
            with: [
                'user' => $this->user,
                'verificationNotes' => $this->verificationNotes,
                'loginUrl' => config('app.url') . '/login',
                'supportEmail' => config('mail.support_email', 'support@judyhomecare.com'),
                'role' => ucfirst($this->user->role)
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}