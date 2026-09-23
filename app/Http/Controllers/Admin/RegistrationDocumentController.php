<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventRegistration;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

class RegistrationDocumentController extends Controller
{
    public function __invoke(EventRegistration $registration, string $document): Response
    {
        $details = match ($document) {
            'photo' => [
                'path' => $registration->passport_photo_path,
                'name' => $registration->passport_photo_original_name,
                'types' => ['image/jpeg', 'image/png', 'image/webp'],
            ],
            'passport' => [
                'path' => $registration->passport_scan_path,
                'name' => $registration->passport_scan_original_name,
                'types' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
            ],
            default => abort(404),
        };
        $path = $details['path'];
        $expectedPrefix = 'event-registrations/'.$registration->public_id.'/';

        abort_unless(
            is_string($path)
            && Str::startsWith($path, $expectedPrefix)
            && ! str_contains($path, '..')
            && Storage::disk('local')->exists($path),
            404,
        );

        try {
            $contents = Crypt::decryptString(Storage::disk('local')->get($path));
        } catch (DecryptException) {
            abort(404);
        }

        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents) ?: 'application/octet-stream';
        abort_unless(in_array($mimeType, $details['types'], true), 415);

        $originalName = is_string($details['name']) && trim($details['name']) !== ''
            ? basename(str_replace('\\', '/', $details['name']))
            : $document;
        $fallbackName = preg_replace('/[^A-Za-z0-9._-]+/', '-', Str::ascii($originalName)) ?: $document;

        return response($contents, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => (string) strlen($contents),
            'Content-Disposition' => HeaderUtils::makeDisposition(
                $document === 'photo'
                    ? HeaderUtils::DISPOSITION_INLINE
                    : HeaderUtils::DISPOSITION_ATTACHMENT,
                $originalName,
                $fallbackName,
            ),
        ]);
    }
}
