<?php

namespace App\Mail;

use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class SeedSummitRegistrationConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly EventRegistration $registration) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Seed Investment Summit registration acknowledgement - '.$this->registration->public_id,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $expiresAt = now()->addDays(max(1, (int) config('seed_summit.email_verification_days', 7)));
        $parameters = [
            'locale' => $this->registration->locale,
            'registration' => $this->registration,
        ];

        return new Content(
            view: 'mail.seed-summit-registration-confirmation',
            text: 'mail.seed-summit-registration-confirmation-text',
            with: [
                'event' => $this->registration->event,
                'registration' => $this->registration,
                'verificationUrl' => URL::temporarySignedRoute(
                    'seed-summit.registrations.verify-email.show',
                    $expiresAt,
                    $parameters,
                ),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
