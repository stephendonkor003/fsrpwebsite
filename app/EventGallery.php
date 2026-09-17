<?php

namespace App;

use FilesystemIterator;

final class EventGallery
{
    private const MEDIA_DIRECTORY = 'media/events';

    /** @var array<string, 'image'|'video'> */
    private const MEDIA_TYPES = [
        'jpeg' => 'image',
        'jpg' => 'image',
        'mp4' => 'video',
        'png' => 'image',
        'webm' => 'video',
        'webp' => 'image',
    ];

    /**
     * @return array<int, array{
     *     day: int,
     *     date: string,
     *     count: int,
     *     items: array<int, array{type: 'image'|'video', url: string, sequence: int, filename: string}>
     * }>
     */
    public function forEvent(string $eventSlug): array
    {
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $eventSlug)) {
            return [];
        }

        $eventDirectory = public_path(self::MEDIA_DIRECTORY.DIRECTORY_SEPARATOR.$eventSlug);
        $dayDirectories = glob($eventDirectory.DIRECTORY_SEPARATOR.'????-??-??-day-??', GLOB_ONLYDIR) ?: [];

        usort($dayDirectories, fn (string $first, string $second): int => strnatcasecmp(basename($first), basename($second)));

        $days = [];
        $seenDayNumbers = [];

        foreach ($dayDirectories as $dayDirectory) {
            if (! preg_match('/^(?<date>\d{4}-\d{2}-\d{2})-day-(?<day>\d{2})$/', basename($dayDirectory), $matches)) {
                continue;
            }

            [$year, $month, $dayOfMonth] = array_map('intval', explode('-', $matches['date']));
            $dayNumber = (int) $matches['day'];

            if (! checkdate($month, $dayOfMonth, $year) || $dayNumber < 1 || isset($seenDayNumbers[$dayNumber])) {
                continue;
            }

            $items = $this->items($dayDirectory);

            if ($items === []) {
                continue;
            }

            $seenDayNumbers[$dayNumber] = true;
            $days[] = [
                'day' => $dayNumber,
                'date' => $matches['date'],
                'count' => count($items),
                'items' => $items,
            ];
        }

        return $days;
    }

    /**
     * @return array<int, array{type: 'image'|'video', url: string, sequence: int, filename: string}>
     */
    private function items(string $dayDirectory): array
    {
        $files = [];

        foreach (['photos', 'videos'] as $mediaDirectory) {
            $directory = $dayDirectory.DIRECTORY_SEPARATOR.$mediaDirectory;

            if (! is_dir($directory)) {
                continue;
            }

            foreach (new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS) as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $extension = strtolower($file->getExtension());
                $type = self::MEDIA_TYPES[$extension] ?? null;

                if ($type === null) {
                    continue;
                }

                $files[] = [
                    'type' => $type,
                    'url' => '/'.str_replace('\\', '/', ltrim(str_replace(public_path(), '', $file->getPathname()), '\\/')),
                    'filename' => $file->getFilename(),
                ];
            }
        }

        usort($files, fn (array $first, array $second): int => strnatcasecmp($first['filename'], $second['filename']));

        $items = array_map(function (array $file, int $index): array {
            preg_match('/-(?<sequence>\d+)\.[^.]+$/', $file['filename'], $sequenceMatch);

            return [
                ...$file,
                'sequence' => (int) ($sequenceMatch['sequence'] ?? $index + 1),
            ];
        }, $files, array_keys($files));

        usort($items, fn (array $first, array $second): int => [$first['sequence'], $first['filename']] <=> [$second['sequence'], $second['filename']]);

        return array_values($items);
    }
}
