<?php

namespace App\Jobs;

use App\Mail\SeedSummitRegistrationConfirmation;
use App\Models\EventRegistration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendSeedSummitRegistrationConfirmation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public readonly EventRegistration $registration)
    {
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $leaseSeconds = max(1, (int) config('seed_summit.email_send_lease_seconds', 75));
        $claim = DB::transaction(function () use ($leaseSeconds): array {
            $registration = EventRegistration::query()
                ->lockForUpdate()
                ->find($this->registration->getKey());

            if ($registration === null || $registration->confirmation_email_status === EventRegistration::EMAIL_SENT) {
                return ['status' => 'complete'];
            }

            if ($registration->confirmation_email_status === EventRegistration::EMAIL_SENDING
                && $registration->confirmation_email_queued_at?->isAfter(now()->subSeconds($leaseSeconds))) {
                $leaseExpiresAt = $registration->confirmation_email_queued_at->addSeconds($leaseSeconds);

                return [
                    'status' => 'busy',
                    'delay' => max(1, now()->diffInSeconds($leaseExpiresAt, false)),
                ];
            }

            $registration->update([
                'confirmation_email_status' => EventRegistration::EMAIL_SENDING,
                'confirmation_email_queued_at' => now(),
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
            Mail::to($registration->official_email)->send(
                (new SeedSummitRegistrationConfirmation($registration))->locale($registration->locale),
            );
        } catch (Throwable $exception) {
            EventRegistration::query()->whereKey($registration->getKey())->update([
                'confirmation_email_status' => EventRegistration::EMAIL_QUEUED,
            ]);

            throw $exception;
        }

        $registration->update([
            'confirmation_email_status' => EventRegistration::EMAIL_SENT,
            'confirmation_email_sent_at' => now(),
            'confirmation_email_failed_at' => null,
        ]);
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function failed(Throwable $exception): void
    {
        EventRegistration::query()->whereKey($this->registration->getKey())->update([
            'confirmation_email_status' => EventRegistration::EMAIL_FAILED,
            'confirmation_email_failed_at' => now(),
        ]);
    }
}
