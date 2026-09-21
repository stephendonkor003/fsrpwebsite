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

class SeedSummitRegistrationReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly EventRegistration $registration,
        private readonly string $pdfContents,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Registration acknowledgement - Inaugural Seed Investment Summit - '
                .$this->registration->public_id,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.seed-summit-registration-receipt',
            text: 'mail.seed-summit-registration-receipt-text',
            with: $this->viewData(),
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn (): string => $this->pdfContents,
                'seed-summit-registration-'.$this->registration->public_id.'.pdf',
            )->withMime('application/pdf'),
        ];
    }

    /** @return array<string, string> */
    private function viewData(): array
    {
        $delegateName = $this->registration->fullName() ?: 'Delegate';
        $eventTitle = $this->registration->event_title;
        $eventTheme = (string) config('seed_summit.theme');
        $eventDate = $this->eventDate();
        $eventVenue = $this->registration->event_venue
            ?: 'Palazzo Convention Centre, Ezulwini, Eswatini';
        $registrationReference = $this->registration->public_id;

        return [
            'delegateName' => $delegateName,
            'eventTitle' => $eventTitle,
            'eventTheme' => $eventTheme,
            'eventDate' => $eventDate,
            'eventVenue' => $eventVenue,
            'registrationReference' => $registrationReference,
            'delegateNameText' => $this->plainText($delegateName),
            'eventTitleText' => $this->plainText($eventTitle),
            'eventThemeText' => $this->plainText($eventTheme),
            'eventDateText' => $this->plainText($eventDate),
            'eventVenueText' => $this->plainText($eventVenue),
            'registrationReferenceText' => $this->plainText($registrationReference),
        ];
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
