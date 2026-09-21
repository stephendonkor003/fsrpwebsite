<?php

namespace App\Services\Mail;

use App\Jobs\SendSeedSummitRegistrationConfirmation;
use App\Jobs\SendSeedSummitRegistrationReceipt;
use App\Models\EventRegistration;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SeedSummitRegistrationMailDispatcher
{
    public function queueAcknowledgement(
        EventRegistration $registration,
        bool $retryFailed = true,
    ): string {
        return $this->queue($registration, 'confirmation', $retryFailed);
    }

    public function queueReceipt(
        EventRegistration $registration,
        bool $retryFailed = true,
    ): string {
        return $this->queue($registration, 'receipt', $retryFailed);
    }

    public function canQueueAcknowledgement(
        EventRegistration $registration,
        bool $retryFailed = true,
    ): bool {
        return $this->canQueue($registration, 'confirmation', $retryFailed);
    }

    public function canQueueReceipt(
        EventRegistration $registration,
        bool $retryFailed = true,
    ): bool {
        return $registration->hasVerifiedOfficialEmail()
            && $this->canQueue($registration, 'receipt', $retryFailed);
    }

    public function staleBefore(): CarbonInterface
    {
        return now()->subSeconds($this->staleSeconds());
    }

    private function queue(
        EventRegistration $registration,
        string $type,
        bool $retryFailed,
    ): string {
        $statusColumn = $type.'_email_status';
        $queuedAtColumn = $type.'_email_queued_at';
        $failedAtColumn = $type.'_email_failed_at';
        $claimedAt = now()->startOfSecond();
        $claim = DB::transaction(function () use (
            $registration,
            $type,
            $retryFailed,
            $statusColumn,
            $queuedAtColumn,
            $failedAtColumn,
            $claimedAt,
        ): array {
            $lockedRegistration = EventRegistration::query()
                ->lockForUpdate()
                ->findOrFail($registration->getKey());

            if (($type === 'receipt' && ! $lockedRegistration->hasVerifiedOfficialEmail())
                || ! $this->canQueue($lockedRegistration, $type, $retryFailed)) {
                return [
                    'dispatch' => false,
                    'registration' => $lockedRegistration,
                ];
            }

            $lockedRegistration->update([
                $statusColumn => EventRegistration::EMAIL_QUEUED,
                $queuedAtColumn => $claimedAt,
                $failedAtColumn => null,
            ]);

            return [
                'dispatch' => true,
                'registration' => $lockedRegistration,
            ];
        });

        /** @var EventRegistration $claimedRegistration */
        $claimedRegistration = $claim['registration'];

        if (! $claim['dispatch']) {
            return (string) $claimedRegistration->getAttribute($statusColumn);
        }

        try {
            Bus::dispatch($type === 'receipt'
                ? new SendSeedSummitRegistrationReceipt($claimedRegistration)
                : new SendSeedSummitRegistrationConfirmation($claimedRegistration));
        } catch (Throwable $exception) {
            EventRegistration::query()
                ->whereKey($claimedRegistration->getKey())
                ->where($statusColumn, EventRegistration::EMAIL_QUEUED)
                ->where($queuedAtColumn, $claimedAt)
                ->update([
                    $statusColumn => EventRegistration::EMAIL_FAILED,
                    $failedAtColumn => now(),
                ]);
            Log::error("Seed Summit {$type} email could not be queued.", [
                'exception' => $exception::class,
                'registration_reference' => $claimedRegistration->public_id,
            ]);
        }

        return (string) EventRegistration::query()
            ->whereKey($claimedRegistration->getKey())
            ->value($statusColumn);
    }

    private function canQueue(
        EventRegistration $registration,
        string $type,
        bool $retryFailed,
    ): bool {
        $status = (string) $registration->getAttribute($type.'_email_status');
        $queuedAt = $registration->getAttribute($type.'_email_queued_at');

        if ($status === EventRegistration::EMAIL_PENDING) {
            return true;
        }

        if ($retryFailed && $status === EventRegistration::EMAIL_FAILED) {
            return true;
        }

        return in_array($status, [
            EventRegistration::EMAIL_QUEUED,
            EventRegistration::EMAIL_SENDING,
        ], true) && (
            ! $queuedAt instanceof CarbonInterface
            || $queuedAt->lessThanOrEqualTo($this->staleBefore())
        );
    }

    private function staleSeconds(): int
    {
        return max(
            (int) config('seed_summit.email_send_lease_seconds', 200) + 1,
            (int) config('seed_summit.email_dispatch_stale_seconds', 300),
        );
    }
}
