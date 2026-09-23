<?php

namespace App\Console\Commands;

use App\Models\EventRegistration;
use App\Support\EventRegistrationSearchIndex;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

#[Signature('registrations:rebuild-search-index {--chunk=200 : Number of registrations to process per database chunk}')]
#[Description('Rebuild the privacy-preserving search index for event registrations')]
final class RebuildEventRegistrationSearchIndex extends Command
{
    public function handle(EventRegistrationSearchIndex $searchIndex): int
    {
        if (! Schema::hasTable('event_registration_search_tokens')) {
            $this->error('Run the event registration search-token migration before rebuilding the index.');

            return self::FAILURE;
        }

        $chunk = (int) $this->option('chunk');

        if ($chunk < 1 || $chunk > 1000) {
            $this->error('The --chunk option must be between 1 and 1000.');

            return self::INVALID;
        }

        $total = EventRegistration::query()->count();

        if ($total === 0) {
            $this->info('No event registrations need indexing.');

            return self::SUCCESS;
        }

        $progress = $this->output->createProgressBar($total);
        $progress->start();

        EventRegistration::query()
            ->select('id')
            ->orderBy('id')
            ->chunkById($chunk, function (Collection $registrations) use ($searchIndex, $progress): void {
                foreach ($registrations as $registration) {
                    $searchIndex->synchronize($registration);
                    $progress->advance();
                }
            });

        $progress->finish();
        $this->newLine(2);
        $this->info("Rebuilt the private search index for {$total} event registrations.");

        return self::SUCCESS;
    }
}
