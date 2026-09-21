<?php

namespace App\Mail;

use App\Models\EventRegistration;
use Carbon\CarbonInterface;
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
            subject: 'Registration received - Confirm your email - Inaugural Seed Investment Summit',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $verificationExpiryDays = max(1, (int) config('seed_summit.email_verification_days', 7));
        $expiresAt = now()->addDays($verificationExpiryDays);
        $parameters = [
            'locale' => $this->registration->locale,
            'registration' => $this->registration,
        ];
        $eventTitle = $this->registration->event_title;
        $eventTheme = (string) config('seed_summit.theme');
        $eventDate = $this->eventDate();
        $eventVenue = $this->registration->event_venue
            ?: 'Palazzo Convention Centre, Ezulwini, Eswatini';
        $registrationReference = $this->registration->public_id;

        return new Content(
            view: 'mail.seed-summit-registration-confirmation',
            text: 'mail.seed-summit-registration-confirmation-text',
            with: [
                'eventTitle' => $eventTitle,
                'eventTheme' => $eventTheme,
                'eventDate' => $eventDate,
                'eventVenue' => $eventVenue,
                'registrationReference' => $registrationReference,
                'eventTitleText' => $this->plainText($eventTitle),
                'eventThemeText' => $this->plainText($eventTheme),
                'eventDateText' => $this->plainText($eventDate),
                'eventVenueText' => $this->plainText($eventVenue),
                'registrationReferenceText' => $this->plainText($registrationReference),
                'verificationExpiryDays' => $verificationExpiryDays,
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

    private function eventDate(): string
    {
        $startsAt = $this->registration->event_start_at;
        $endsAt = $this->registration->event_end_at;

        if (! $startsAt instanceof CarbonInterface) {
            return '5-7 October 2026';
        }

        if (! $endsAt instanceof CarbonInterface) {
            return $startsAt->format('j F Y');
        }

        if ($startsAt->isSameDay($endsAt)) {
            return $startsAt->format('j F Y');
        }

        if ($startsAt->year === $endsAt->year && $startsAt->month === $endsAt->month) {
            return $startsAt->format('j').'-'.$endsAt->format('j F Y');
        }

        if ($startsAt->year === $endsAt->year) {
            return $startsAt->format('j F').' - '.$endsAt->format('j F Y');
        }

        return $startsAt->format('j F Y').' - '.$endsAt->format('j F Y');
    }

    private function plainText(string $value): string
    {
        $plainText = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', strip_tags($value));

        return trim(is_string($plainText) ? $plainText : '');
    }
}
