<?php

namespace App\Jobs;

use App\Mail\SeedSummitRegistrationReceipt;
use App\Models\EventRegistration;
use App\Support\SeedSummitRegistrationPdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendSeedSummitRegistrationReceipt implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 170;

    public function __construct(public readonly EventRegistration $registration)
    {
        $this->afterCommit();
    }

    public function handle(SeedSummitRegistrationPdf $pdf): void
    {
        $leaseSeconds = max(200, (int) config('seed_summit.email_send_lease_seconds', 200));
        $claim = DB::transaction(function () use ($leaseSeconds): array {
            $registration = EventRegistration::query()
                ->lockForUpdate()
                ->find($this->registration->getKey());

            if ($registration === null
                || ! $registration->hasVerifiedOfficialEmail()
                || $registration->receipt_email_status === EventRegistration::EMAIL_SENT) {
                return ['status' => 'complete'];
            }

            if ($registration->receipt_email_status === EventRegistration::EMAIL_SENDING
                && $registration->receipt_email_queued_at?->isAfter(now()->subSeconds($leaseSeconds))) {
                $leaseExpiresAt = $registration->receipt_email_queued_at->addSeconds($leaseSeconds);

                return [
                    'status' => 'busy',
                    'delay' => max(1, now()->diffInSeconds($leaseExpiresAt, false)),
                ];
            }

            $registration->update([
                'receipt_email_status' => EventRegistration::EMAIL_SENDING,
                'receipt_email_queued_at' => now(),
            ]);

            return ['status' => 'claimed', 'registration' => $registration];
        });

        if ($claim['status'] === 'complete') {
            return;
        }

        if ($claim['status'] === 'busy') {
            $this->release($claim['delay']);

            return;
        }

        /** @var EventRegistration $registration */
        $registration = $claim['registration'];

        try {
            $pdfContents = $pdf->render($registration);

            Mail::to($registration->official_email)->send(
                new SeedSummitRegistrationReceipt($registration, $pdfContents),
            );
        } catch (Throwable $exception) {
            EventRegistration::query()->whereKey($registration->getKey())->update([
                'receipt_email_status' => EventRegistration::EMAIL_QUEUED,
            ]);

            throw $exception;
        }

        $registration->update([
            'receipt_email_status' => EventRegistration::EMAIL_SENT,
            'receipt_email_sent_at' => now(),
            'receipt_email_failed_at' => null,
        ]);
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function failed(Throwable $exception): void
    {
        EventRegistration::query()
            ->whereKey($this->registration->getKey())
            ->whereIn('receipt_email_status', [
                EventRegistration::EMAIL_QUEUED,
                EventRegistration::EMAIL_SENDING,
            ])
            ->update([
                'receipt_email_status' => EventRegistration::EMAIL_FAILED,
                'receipt_email_failed_at' => now(),
            ]);
    }
}
