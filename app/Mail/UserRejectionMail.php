<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class UserRejectionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $rejectionReason;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $rejectionReason)
    {
        $this->user = $user;
        $this->rejectionReason = $rejectionReason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Application Rejected - Judy Home Care Portal',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.user-rejection',
            with: [
                'user' => $this->user,
                'rejectionReason' => $this->rejectionReason,
                'supportEmail' => config('mail.support_email', 'support@judyhomecare.com'),
                'supportPhone' => config('app.support_phone', '+233 XX XXX XXXX'),
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