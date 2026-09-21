<?php

namespace App\Support;

use RuntimeException;

final class UnicodePdfFont
{
    private string $baseFontName;

    private string $fontData;

    /** @var array<string, array{offset: int, length: int}> */
    private array $tables = [];

    private int $unitsPerEm;

    private int $ascent;

    private int $descent;

    /** @var array{0: int, 1: int, 2: int, 3: int} */
    private array $boundingBox;

    private int $numberOfHorizontalMetrics;

    private int $cmapOffset;

    private int $cmapFormat;

    public function __construct(
        string $relativePath = 'fonts/DejaVuSans.ttf',
        string $baseFontName = 'DejaVuSans',
    ) {
        $path = resource_path($relativePath);
        $contents = @file_get_contents($path);

        if (! is_string($contents) || strlen($contents) < 12) {
            throw new RuntimeException('The Unicode PDF font could not be loaded.');
        }

        $this->fontData = $contents;
        $this->baseFontName = preg_replace('/[^A-Za-z0-9_-]/', '', $baseFontName) ?: 'EmbeddedFont';
        $this->readTableDirectory();

        $head = $this->table('head');
        $hhea = $this->table('hhea');
        $this->table('hmtx');
        $this->table('maxp');
        $this->unitsPerEm = $this->unsignedShort($head['offset'] + 18);
        $this->ascent = $this->signedShort($hhea['offset'] + 4);
        $this->descent = $this->signedShort($hhea['offset'] + 6);
        $this->boundingBox = [
            $this->signedShort($head['offset'] + 36),
            $this->signedShort($head['offset'] + 38),
            $this->signedShort($head['offset'] + 40),
            $this->signedShort($head['offset'] + 42),
        ];
        $this->numberOfHorizontalMetrics = $this->unsignedShort($hhea['offset'] + 34);

        if ($this->unitsPerEm < 1 || $this->numberOfHorizontalMetrics < 1) {
            throw new RuntimeException('The Unicode PDF font has invalid metrics.');
        }

        $this->selectCharacterMap();
    }

    public function data(): string
    {
        return $this->fontData;
    }

    public function baseFontName(): string
    {
        return $this->baseFontName;
    }

    public function ascent(): int
    {
        return $this->scale($this->ascent);
    }

    public function descent(): int
    {
        return $this->scale($this->descent);
    }

    /** @return array{0: int, 1: int, 2: int, 3: int} */
    public function boundingBox(): array
    {
        return array_map($this->scale(...), $this->boundingBox);
    }

    public function glyphId(int $codePoint): int
    {
        if ($codePoint < 0 || $codePoint > 0xFFFF) {
            return 0;
        }

        return $this->cmapFormat === 12
            ? $this->glyphFromFormatTwelve($codePoint)
            : $this->glyphFromFormatFour($codePoint);
    }

    public function width(int $codePoint): int
    {
        $glyphId = $this->glyphId($codePoint);
        $hmtx = $this->table('hmtx');
        $metricIndex = min($glyphId, $this->numberOfHorizontalMetrics - 1);
        $advanceWidth = $this->unsignedShort($hmtx['offset'] + ($metricIndex * 4));

        return max(1, $this->scale($advanceWidth));
    }

    /** @return array<int, int> */
    public function codePoints(string $value): array
    {
        $characters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($characters)) {
            return [];
        }

        return array_map(static function (string $character): int {
            $codePoint = mb_ord($character, 'UTF-8');

            return $codePoint <= 0xFFFF ? $codePoint : 0xFFFD;
        }, $characters);
    }

    public function encodedHex(string $value): string
    {
        $encoded = '';

        foreach ($this->codePoints($value) as $codePoint) {
            $encoded .= pack('n', $codePoint);
        }

        return strtoupper(bin2hex($encoded));
    }

    private function readTableDirectory(): void
    {
        $tableCount = $this->unsignedShort(4);

        for ($index = 0; $index < $tableCount; $index++) {
            $recordOffset = 12 + ($index * 16);
            $tag = substr($this->fontData, $recordOffset, 4);
            $offset = $this->unsignedLong($recordOffset + 8);
            $length = $this->unsignedLong($recordOffset + 12);

            if ($tag === '' || $offset < 0 || $length < 1 || $offset + $length > strlen($this->fontData)) {
                throw new RuntimeException('The Unicode PDF font table directory is invalid.');
            }

            $this->tables[$tag] = ['offset' => $offset, 'length' => $length];
        }
    }

    private function selectCharacterMap(): void
    {
        $cmap = $this->table('cmap');
        $subtableCount = $this->unsignedShort($cmap['offset'] + 2);
        $selected = null;
        $selectedScore = -1;

        for ($index = 0; $index < $subtableCount; $index++) {
            $recordOffset = $cmap['offset'] + 4 + ($index * 8);
            $platformId = $this->unsignedShort($recordOffset);
            $encodingId = $this->unsignedShort($recordOffset + 2);
            $subtableOffset = $cmap['offset'] + $this->unsignedLong($recordOffset + 4);
            $format = $this->unsignedShort($subtableOffset);
            $score = match (true) {
                $format === 12 && $platformId === 3 && $encodingId === 10 => 40,
                $format === 12 => 30,
                $format === 4 && $platformId === 3 => 20,
                $format === 4 => 10,
                default => -1,
            };

            if ($score > $selectedScore) {
                $selected = ['offset' => $subtableOffset, 'format' => $format];
                $selectedScore = $score;
            }
        }

        if (! is_array($selected)) {
            throw new RuntimeException('The Unicode PDF font has no supported character map.');
        }

        $this->cmapOffset = $selected['offset'];
        $this->cmapFormat = $selected['format'];
    }

    private function glyphFromFormatTwelve(int $codePoint): int
    {
        $groupCount = $this->unsignedLong($this->cmapOffset + 12);
        $low = 0;
        $high = $groupCount - 1;

        while ($low <= $high) {
            $middle = intdiv($low + $high, 2);
            $groupOffset = $this->cmapOffset + 16 + ($middle * 12);
            $start = $this->unsignedLong($groupOffset);
            $end = $this->unsignedLong($groupOffset + 4);

            if ($codePoint < $start) {
                $high = $middle - 1;
            } elseif ($codePoint > $end) {
                $low = $middle + 1;
            } else {
                return $this->unsignedLong($groupOffset + 8) + ($codePoint - $start);
            }
        }

        return 0;
    }

    private function glyphFromFormatFour(int $codePoint): int
    {
        $segmentCount = intdiv($this->unsignedShort($this->cmapOffset + 6), 2);
        $endCodesOffset = $this->cmapOffset + 14;
        $startCodesOffset = $endCodesOffset + ($segmentCount * 2) + 2;
        $deltasOffset = $startCodesOffset + ($segmentCount * 2);
        $rangeOffsetsOffset = $deltasOffset + ($segmentCount * 2);

        for ($index = 0; $index < $segmentCount; $index++) {
            $end = $this->unsignedShort($endCodesOffset + ($index * 2));

            if ($codePoint > $end) {
                continue;
            }

            $start = $this->unsignedShort($startCodesOffset + ($index * 2));

            if ($codePoint < $start) {
                return 0;
            }

            $delta = $this->signedShort($deltasOffset + ($index * 2));
            $rangeOffsetAddress = $rangeOffsetsOffset + ($index * 2);
            $rangeOffset = $this->unsignedShort($rangeOffsetAddress);

            if ($rangeOffset === 0) {
                return ($codePoint + $delta) & 0xFFFF;
            }

            $glyphAddress = $rangeOffsetAddress + $rangeOffset + (($codePoint - $start) * 2);
            $glyph = $this->unsignedShort($glyphAddress);

            return $glyph === 0 ? 0 : ($glyph + $delta) & 0xFFFF;
        }

        return 0;
    }

    /** @return array{offset: int, length: int} */
    private function table(string $tag): array
    {
        if (! isset($this->tables[$tag])) {
            throw new RuntimeException("The Unicode PDF font is missing its {$tag} table.");
        }

        return $this->tables[$tag];
    }

    private function scale(int $value): int
    {
        return (int) round(($value * 1000) / $this->unitsPerEm);
    }

    private function unsignedShort(int $offset): int
    {
        $value = unpack('nvalue', substr($this->fontData, $offset, 2));

        if (! is_array($value)) {
            throw new RuntimeException('The Unicode PDF font could not be read.');
        }

        return $value['value'];
    }

    private function signedShort(int $offset): int
    {
        $value = $this->unsignedShort($offset);

        return $value > 0x7FFF ? $value - 0x10000 : $value;
    }

    private function unsignedLong(int $offset): int
    {
        $value = unpack('Nvalue', substr($this->fontData, $offset, 4));

        if (! is_array($value)) {
            throw new RuntimeException('The Unicode PDF font could not be read.');
        }

        return $value['value'];
    }
}
