<?php

namespace App\Http\Controllers;

use App\Models\EventResource;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResourceDownloadController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(string $locale, string $resource): StreamedResponse
    {
        abort_unless(filter_var($resource, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false, 404);

        $document = EventResource::published()->findOrFail($resource);

        return $this->download($document);
    }

    public function admin(string $resource): StreamedResponse
    {
        abort_unless(filter_var($resource, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false, 404);

        return $this->download(EventResource::findOrFail($resource));
    }

    private function download(EventResource $document): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_filename, [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
