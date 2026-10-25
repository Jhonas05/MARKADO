<?php

namespace App\Mail;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InstitutionalEmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        #[\SensitiveParameter]
        public readonly string $verificationToken,
        public readonly CarbonImmutable $expiresAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verify your MARKADO institutional email');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.institutional-email-verification');
    }
}
