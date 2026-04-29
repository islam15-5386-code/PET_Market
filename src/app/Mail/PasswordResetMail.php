<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $token,
        public readonly string $email,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reset Your Password — Pet Marketplace');
    }

    public function content(): Content
    {
        $resetUrl = config('app.frontend_url')
            . '/reset-password?token=' . $this->token
            . '&email=' . urlencode($this->email);

        return new Content(
            view: 'emails.password-reset',
            with: ['resetUrl' => $resetUrl, 'expiresIn' => '60 minutes']
        );
    }
}
