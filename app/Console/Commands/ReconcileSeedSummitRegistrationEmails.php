<?php

namespace App\Console\Commands;

use App\Models\EventRegistration;
use App\Services\Mail\SeedSummitRegistrationMailDispatcher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

#[Signature('seed-summit:reconcile-registration-emails {--limit=100 : Maximum records to inspect for each email type}')]
#[Description('Recover Seed Summit registration emails left pending by an interrupted request or queue worker')]
final class ReconcileSeedSummitRegistrationEmails extends Command
{
    public function handle(SeedSummitRegistrationMailDispatcher $dispatcher): int
    {
        $limit = min(500, max(1, (int) $this->option('limit')));
        $acknowledgements = $this->candidates('confirmation', $dispatcher)
            ->limit($limit)
            ->get();
        $receipts = $this->candidates('receipt', $dispatcher)
            ->whereNotNull('official_email_verified_at')
            ->whereColumn('verified_email_hash', 'official_email_hash')
            ->limit($limit)
            ->get();

        foreach ($acknowledgements as $registration) {
            $dispatcher->queueAcknowledgement($registration, retryFailed: false);
        }

        foreach ($receipts as $registration) {
            $dispatcher->queueReceipt($registration, retryFailed: false);
        }

        $this->info(sprintf(
            'Inspected %d acknowledgement and %d verified receipt email records.',
            $acknowledgements->count(),
            $receipts->count(),
        ));

        return self::SUCCESS;
    }

    /** @return Builder<EventRegistration> */
    private function candidates(
        string $type,
        SeedSummitRegistrationMailDispatcher $dispatcher,
    ): Builder {
        $statusColumn = $type.'_email_status';
        $queuedAtColumn = $type.'_email_queued_at';

        return EventRegistration::query()
            ->where('event_slug', config('seed_summit.event_slug'))
            ->where(function (Builder $query) use (
                $statusColumn,
                $queuedAtColumn,
                $dispatcher,
            ): void {
                $query
                    ->where($statusColumn, EventRegistration::EMAIL_PENDING)
                    ->orWhere(function (Builder $stale) use (
                        $statusColumn,
                        $queuedAtColumn,
                        $dispatcher,
                    ): void {
                        $stale
                            ->whereIn($statusColumn, [
                                EventRegistration::EMAIL_QUEUED,
                                EventRegistration::EMAIL_SENDING,
                            ])
                            ->where(function (Builder $lease) use (
                                $queuedAtColumn,
                                $dispatcher,
                            ): void {
                                $lease
                                    ->whereNull($queuedAtColumn)
                                    ->orWhere($queuedAtColumn, '<=', $dispatcher->staleBefore());
                            });
                    });
            })
            ->orderBy('id');
    }
}
