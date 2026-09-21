<?php

namespace App\Actions;

use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class RegisterSeedSummitDelegate
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(
        Event $event,
        array $data,
        string $locale,
        ?UploadedFile $passportPhoto = null,
        ?UploadedFile $passportScan = null,
    ): EventRegistration {
        $publicId = (string) Str::uuid();
        $directory = 'event-registrations/'.$publicId;

        try {
            return DB::transaction(function () use (
                $event,
                $data,
                $locale,
                $passportPhoto,
                $passportScan,
                $publicId,
                $directory,
            ): EventRegistration {
                $acceptedAt = now();
                $registration = EventRegistration::query()->create(array_merge($data, [
                    'public_id' => $publicId,
                    'event_id' => $event->id,
                    'event_slug' => $event->slug,
                    'event_title' => $event->translate('title', $locale),
                    'event_venue' => $event->translate('venue', $locale),
                    'event_start_at' => $event->start_at,
                    'event_end_at' => $event->end_at,
                    'locale' => $locale,
                    'consent_version' => (string) config('seed_summit.consent_version'),
                    'consent_text_hash' => hash('sha256', (string) config('seed_summit.data_protection_notice')),
                    'data_protection_accepted_at' => $acceptedAt,
                    'attendance_confirmed_at' => $acceptedAt,
                    'confirmation_email_status' => EventRegistration::EMAIL_PENDING,
                ]));

                $fileAttributes = [];

                if ($passportPhoto instanceof UploadedFile) {
                    $fileAttributes['passport_photo_path'] = $this->storeUpload($passportPhoto, $directory);
                    $fileAttributes['passport_photo_original_name'] = $this->safeOriginalName($passportPhoto);
                }

                if ($passportScan instanceof UploadedFile) {
                    $fileAttributes['passport_scan_path'] = $this->storeUpload($passportScan, $directory);
                    $fileAttributes['passport_scan_original_name'] = $this->safeOriginalName($passportScan);
                }

                if ($fileAttributes !== []) {
                    $registration->update($fileAttributes);
                }

                return $registration->fresh();
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->deleteDirectory($directory);

            throw $exception;
        }
    }

    private function storeUpload(UploadedFile $file, string $directory): string
    {
        $contents = file_get_contents($file->getRealPath());

        if (! is_string($contents)) {
            throw new RuntimeException('The registration document could not be read.');
        }

        $path = $directory.'/'.Str::uuid().'.enc';
        $stored = Storage::disk('local')->put($path, Crypt::encryptString($contents));

        if (! $stored) {
            throw new RuntimeException('The registration document could not be stored.');
        }

        return $path;
    }

    private function safeOriginalName(UploadedFile $file): string
    {
        $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $name = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $name) ?? 'document';

        return Str::limit(trim($name), 255, '');
    }
}
