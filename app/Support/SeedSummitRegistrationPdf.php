<?php

namespace App\Support;

use App\Models\EventRegistration;

class SeedSummitRegistrationPdf
{
    private const BODY_TEXT_WIDTH = 499.0;

    /** @var array<int, array{0: int, 1: ?int, 2: ?int, 3: ?int}> */
    private const ARABIC_FORMS = [
        0x0621 => [0xFE80, null, null, null], 0x0622 => [0xFE81, 0xFE82, null, null],
        0x0623 => [0xFE83, 0xFE84, null, null], 0x0624 => [0xFE85, 0xFE86, null, null],
        0x0625 => [0xFE87, 0xFE88, null, null], 0x0626 => [0xFE89, 0xFE8A, 0xFE8B, 0xFE8C],
        0x0627 => [0xFE8D, 0xFE8E, null, null], 0x0628 => [0xFE8F, 0xFE90, 0xFE91, 0xFE92],
        0x0629 => [0xFE93, 0xFE94, null, null], 0x062A => [0xFE95, 0xFE96, 0xFE97, 0xFE98],
        0x062B => [0xFE99, 0xFE9A, 0xFE9B, 0xFE9C], 0x062C => [0xFE9D, 0xFE9E, 0xFE9F, 0xFEA0],
        0x062D => [0xFEA1, 0xFEA2, 0xFEA3, 0xFEA4], 0x062E => [0xFEA5, 0xFEA6, 0xFEA7, 0xFEA8],
        0x062F => [0xFEA9, 0xFEAA, null, null], 0x0630 => [0xFEAB, 0xFEAC, null, null],
        0x0631 => [0xFEAD, 0xFEAE, null, null], 0x0632 => [0xFEAF, 0xFEB0, null, null],
        0x0633 => [0xFEB1, 0xFEB2, 0xFEB3, 0xFEB4], 0x0634 => [0xFEB5, 0xFEB6, 0xFEB7, 0xFEB8],
        0x0635 => [0xFEB9, 0xFEBA, 0xFEBB, 0xFEBC], 0x0636 => [0xFEBD, 0xFEBE, 0xFEBF, 0xFEC0],
        0x0637 => [0xFEC1, 0xFEC2, 0xFEC3, 0xFEC4], 0x0638 => [0xFEC5, 0xFEC6, 0xFEC7, 0xFEC8],
        0x0639 => [0xFEC9, 0xFECA, 0xFECB, 0xFECC], 0x063A => [0xFECD, 0xFECE, 0xFECF, 0xFED0],
        0x0640 => [0x0640, 0x0640, 0x0640, 0x0640], 0x0641 => [0xFED1, 0xFED2, 0xFED3, 0xFED4],
        0x0642 => [0xFED5, 0xFED6, 0xFED7, 0xFED8], 0x0643 => [0xFED9, 0xFEDA, 0xFEDB, 0xFEDC],
        0x0644 => [0xFEDD, 0xFEDE, 0xFEDF, 0xFEE0], 0x0645 => [0xFEE1, 0xFEE2, 0xFEE3, 0xFEE4],
        0x0646 => [0xFEE5, 0xFEE6, 0xFEE7, 0xFEE8], 0x0647 => [0xFEE9, 0xFEEA, 0xFEEB, 0xFEEC],
        0x0648 => [0xFEED, 0xFEEE, null, null], 0x0649 => [0xFEEF, 0xFEF0, null, null],
        0x064A => [0xFEF1, 0xFEF2, 0xFEF3, 0xFEF4], 0x067E => [0xFB56, 0xFB57, 0xFB58, 0xFB59],
        0x0686 => [0xFB7A, 0xFB7B, 0xFB7C, 0xFB7D], 0x0698 => [0xFB8A, 0xFB8B, null, null],
        0x06A9 => [0xFB8E, 0xFB8F, 0xFB90, 0xFB91], 0x06AF => [0xFB92, 0xFB93, 0xFB94, 0xFB95],
        0x06C1 => [0xFBA6, 0xFBA7, 0xFBA8, 0xFBA9], 0x06BE => [0xFBAA, 0xFBAB, 0xFBAC, 0xFBAD],
        0x06CC => [0xFBFC, 0xFBFD, 0xFBFE, 0xFBFF], 0x06D2 => [0xFBAE, 0xFBAF, null, null],
    ];

    /** @var array{primary: array<int, int>, fallback: array<int, int>} */
    private array $usedCodePoints = ['primary' => [], 'fallback' => []];

    private readonly UnicodePdfFont $fallbackFont;

    public function __construct(
        private readonly SeedSummitRegistrationSummary $summary,
        private readonly UnicodePdfFont $font,
    ) {
        $this->fallbackFont = new UnicodePdfFont(
            'fonts/AbyssinicaSIL-Regular.ttf',
            'AbyssinicaSIL',
        );
    }

    public function render(EventRegistration $registration): string
    {
        $this->usedCodePoints = ['primary' => [], 'fallback' => []];
        $documentLines = [];

        foreach ($this->summary->for($registration) as $section) {
            $documentLines[] = ['text' => $section['title'], 'font' => 'F2', 'size' => 11, 'leading' => 19];

            foreach ($section['items'] as $item) {
                foreach ($this->wrap($item['label'].': '.$item['value'], self::BODY_TEXT_WIDTH, 9) as $line) {
                    $documentLines[] = [
                        'text' => $line,
                        'font' => 'F1',
                        'size' => 9,
                        'leading' => 13,
                    ];
                }
            }

            $documentLines[] = ['text' => '', 'font' => 'F1', 'size' => 9, 'leading' => 8];
        }

        $pages = array_chunk($documentLines, 48);

        if ($pages === []) {
            $pages = [[]];
        }

        return $this->buildPdf($pages, $registration);
    }

    /**
     * @param  array<int, array<int, array{text: string, font: string, size: int, leading: int}>>  $pages
     */
    private function buildPdf(array $pages, EventRegistration $registration): string
    {
        $pageCount = count($pages);
        $primaryFontId = 3 + ($pageCount * 2);
        $fallbackFontId = $primaryFontId + 6;
        $lastObjectId = $fallbackFontId + 5;
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
        ];
        $pageReferences = [];

        foreach ($pages as $index => $lines) {
            $pageId = 3 + ($index * 2);
            $contentId = $pageId + 1;
            $pageReferences[] = $pageId.' 0 R';
            $stream = $this->pageStream($lines, $registration, $index + 1, $pageCount);
            $objects[$pageId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 %d 0 R /F2 %d 0 R /F3 %d 0 R >> >> /Contents %d 0 R >>',
                $primaryFontId,
                $primaryFontId,
                $fallbackFontId,
                $contentId,
            );
            $objects[$contentId] = '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream";
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $pageReferences).'] /Count '.$pageCount.' >>';
        $this->addFontObjects(
            $objects,
            $primaryFontId,
            $this->font,
            $this->usedCodePoints['primary'],
        );
        $this->addFontObjects(
            $objects,
            $fallbackFontId,
            $this->fallbackFont,
            $this->usedCodePoints['fallback'],
        );
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];

        foreach ($objects as $objectId => $object) {
            $offsets[$objectId] = strlen($pdf);
            $pdf .= "{$objectId} 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $objectCount = $lastObjectId + 1;
        $pdf .= "xref\n0 {$objectCount}\n0000000000 65535 f \n";

        for ($objectId = 1; $objectId < $objectCount; $objectId++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$objectId]);
        }

        $pdf .= "trailer\n<< /Size {$objectCount} /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    /**
     * @param  array<int, array{text: string, font: string, size: int, leading: int}>  $lines
     */
    private function pageStream(array $lines, EventRegistration $registration, int $page, int $pageCount): string
    {
        $commands = [
            'BT',
            '/F2 17 Tf',
            '1 0 0 1 48 800 Tm',
            '<'.$this->pdfText('Inaugural Seed Investment Summit Registration').'> Tj',
            '/F1 9 Tf',
            '1 0 0 1 48 780 Tm',
            '<'.$this->pdfText('Acknowledgement and delegate record').'> Tj',
            '/F1 8 Tf',
            '1 0 0 1 400 800 Tm',
            '<'.$this->pdfText('Ref: '.$registration->public_id).'> Tj',
        ];
        $y = 752;

        foreach ($lines as $line) {
            array_push(
                $commands,
                ...$this->lineCommands($line['text'], $line['font'], $line['size'], 48, $y),
            );
            $y -= $line['leading'];
        }

        $commands[] = '/F1 8 Tf';
        $commands[] = '1 0 0 1 48 28 Tm';
        $commands[] = '<'.$this->pdfText('African Union Commission - Inaugural Seed Investment Summit').'> Tj';
        $commands[] = '1 0 0 1 500 28 Tm';
        $commands[] = '<'.$this->pdfText("Page {$page} of {$pageCount}").'> Tj';
        $commands[] = 'ET';

        return implode("\n", $commands);
    }

    /** @return array<int, string> */
    private function lineCommands(
        string $value,
        string $primaryFontResource,
        int $fontSize,
        int $x,
        int $y,
    ): array {
        $commands = [
            '/Span << /ActualText <'.$this->actualText($value).'> >> BDC',
            "1 0 0 1 {$x} {$y} Tm",
        ];

        foreach ($this->fontRuns($value) as $run) {
            $fontResource = $run['font'] === 'fallback' ? 'F3' : $primaryFontResource;
            $commands[] = '/'.$fontResource.' '.$fontSize.' Tf';
            $commands[] = '<'.$this->encodeGlyphs($run['glyphs'], $run['font']).'> Tj';
        }

        $commands[] = 'EMC';

        return $commands;
    }

    /** @return array<int, string> */
    private function wrap(string $value, float $maximumWidth, int $fontSize): array
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
        $words = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($words) || $words === []) {
            return [''];
        }

        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = $line === '' ? $word : $line.' '.$word;

            if ($this->textWidth($candidate, $fontSize) <= $maximumWidth) {
                $line = $candidate;

                continue;
            }

            if ($line !== '') {
                $lines[] = $line;
                $line = '';
            }

            while ($word !== '') {
                $prefix = $lines === [] ? '' : '    ';
                [$chunk, $word] = $this->fittingPrefix($word, $prefix, $fontSize, $maximumWidth);

                if ($word === '') {
                    $line = $prefix.$chunk;
                } else {
                    $lines[] = $prefix.$chunk;
                }
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    /** @return array{0: string, 1: string} */
    private function fittingPrefix(string $word, string $prefix, int $fontSize, float $maximumWidth): array
    {
        $characters = $this->textClusters($word);

        if ($characters === []) {
            return ['', ''];
        }

        $chunk = '';
        $consumed = 0;

        foreach ($characters as $character) {
            $candidate = $chunk.$character;

            if ($chunk !== '' && $this->textWidth($prefix.$candidate, $fontSize) > $maximumWidth) {
                break;
            }

            $chunk = $candidate;
            $consumed++;
        }

        return [$chunk, implode('', array_slice($characters, $consumed))];
    }

    private function textWidth(string $value, int $fontSize): float
    {
        $width = 0;

        foreach ($this->visualGlyphs($value) as $glyph) {
            $font = $this->fontFor($this->fontKeyForGlyph($glyph));
            $width += $font->width($glyph['cid']);
        }

        return ($width * $fontSize) / 1000;
    }

    private function pdfText(string $value, string $fontKey = 'primary'): string
    {
        $value = str_replace(["\r", "\n"], ['', ' '], $value);

        return $this->encodeGlyphs($this->visualGlyphs($value), $fontKey);
    }

    /**
     * @param  array<int, array{cid: int, unicode: int}>  $glyphs
     */
    private function encodeGlyphs(array $glyphs, string $fontKey): string
    {
        $encoded = '';

        foreach ($glyphs as $glyph) {
            $this->usedCodePoints[$fontKey][$glyph['cid']] = $glyph['unicode'];
            $encoded .= pack('n', $glyph['cid']);
        }

        return strtoupper(bin2hex($encoded));
    }

    private function actualText(string $value): string
    {
        $value = str_replace(["\r", "\n"], ['', ' '], $value);

        return strtoupper(bin2hex("\xFE\xFF".mb_convert_encoding($value, 'UTF-16BE', 'UTF-8')));
    }

    /**
     * @return array<int, array{font: string, glyphs: array<int, array{cid: int, unicode: int}>}>
     */
    private function fontRuns(string $value): array
    {
        $runs = [];

        foreach ($this->visualGlyphs($value) as $glyph) {
            $fontKey = $this->fontKeyForGlyph($glyph);
            $lastRun = array_key_last($runs);

            if ($lastRun === null || $runs[$lastRun]['font'] !== $fontKey) {
                $runs[] = ['font' => $fontKey, 'glyphs' => [$glyph]];

                continue;
            }

            $runs[$lastRun]['glyphs'][] = $glyph;
        }

        return $runs;
    }

    /** @param array{cid: int, unicode: int} $glyph */
    private function fontKeyForGlyph(array $glyph): string
    {
        if ($this->font->glyphId($glyph['cid']) !== 0) {
            return 'primary';
        }

        if ($this->fallbackFont->glyphId($glyph['cid']) !== 0) {
            return 'fallback';
        }

        return 'primary';
    }

    private function fontFor(string $fontKey): UnicodePdfFont
    {
        return $fontKey === 'fallback' ? $this->fallbackFont : $this->font;
    }

    /**
     * @return array<int, array{cid: int, unicode: int}>
     */
    private function visualGlyphs(string $value): array
    {
        $codePoints = $this->font->codePoints($value);
        $glyphs = [];

        foreach ($codePoints as $index => $codePoint) {
            $cid = $this->arabicPresentationForm($codePoints, $index);

            if ($this->font->glyphId($cid) === 0 && $this->fallbackFont->glyphId($cid) === 0) {
                $cid = $codePoint;
            }

            $glyphs[] = [
                'cid' => $cid,
                'unicode' => $codePoint,
                'direction' => $this->glyphDirection($codePoint),
            ];
        }

        $clusters = [];

        foreach ($glyphs as $glyph) {
            if ($this->isCombiningMark($glyph['unicode']) && $clusters !== []) {
                $clusters[array_key_last($clusters)]['glyphs'][] = $glyph;

                continue;
            }

            $clusters[] = [
                'direction' => $glyph['direction'],
                'glyphs' => [$glyph],
            ];
        }

        $visual = [];
        $clusterCount = count($clusters);

        for ($index = 0; $index < $clusterCount;) {
            if ($clusters[$index]['direction'] !== 'rtl') {
                array_push($visual, ...$clusters[$index]['glyphs']);
                $index++;

                continue;
            }

            $end = $index;

            while ($end + 1 < $clusterCount) {
                $nextDirection = $clusters[$end + 1]['direction'];

                if ($nextDirection === 'rtl' || $nextDirection === 'number') {
                    $end++;

                    continue;
                }

                if ($nextDirection === 'neutral'
                    && in_array($this->nextStrongDirection($clusters, $end + 2), ['rtl', 'number'], true)) {
                    $end++;

                    continue;
                }

                break;
            }

            $units = [];

            for ($runIndex = $index; $runIndex <= $end; $runIndex++) {
                if ($clusters[$runIndex]['direction'] !== 'number') {
                    $units[] = [$clusters[$runIndex]];

                    continue;
                }

                $numberUnit = [$clusters[$runIndex]];

                while ($runIndex + 1 <= $end && $clusters[$runIndex + 1]['direction'] === 'number') {
                    $numberUnit[] = $clusters[++$runIndex];
                }

                $units[] = $numberUnit;
            }

            foreach (array_reverse($units) as $unit) {
                foreach ($unit as $cluster) {
                    array_push($visual, ...$cluster['glyphs']);
                }
            }

            $index = $end + 1;
        }

        return array_map(
            static fn (array $glyph): array => ['cid' => $glyph['cid'], 'unicode' => $glyph['unicode']],
            $visual,
        );
    }

    /**
     * @param  array<int, array{direction: string, glyphs: array<int, array{cid: int, unicode: int, direction: string}>}>  $clusters
     */
    private function nextStrongDirection(array $clusters, int $index): ?string
    {
        for ($clusterCount = count($clusters); $index < $clusterCount; $index++) {
            if ($clusters[$index]['direction'] !== 'neutral') {
                return $clusters[$index]['direction'];
            }
        }

        return null;
    }

    /** @param array<int, int> $codePoints */
    private function arabicPresentationForm(array $codePoints, int $index): int
    {
        $codePoint = $codePoints[$index];
        $forms = self::ARABIC_FORMS[$codePoint] ?? null;

        if ($forms === null) {
            return $codePoint;
        }

        $previous = $this->joiningNeighbour($codePoints, $index, -1);
        $next = $this->joiningNeighbour($codePoints, $index, 1);
        $joinsPrevious = $forms[1] !== null && $previous !== null && (self::ARABIC_FORMS[$previous][2] ?? null) !== null;
        $joinsNext = $forms[2] !== null && $next !== null && (self::ARABIC_FORMS[$next][1] ?? null) !== null;
        $formIndex = match (true) {
            $joinsPrevious && $joinsNext => 3,
            $joinsPrevious => 1,
            $joinsNext => 2,
            default => 0,
        };

        return $forms[$formIndex] ?? $forms[0];
    }

    /** @param array<int, int> $codePoints */
    private function joiningNeighbour(array $codePoints, int $index, int $direction): ?int
    {
        for ($neighbour = $index + $direction; isset($codePoints[$neighbour]); $neighbour += $direction) {
            if ($this->isCombiningMark($codePoints[$neighbour])) {
                continue;
            }

            return $codePoints[$neighbour];
        }

        return null;
    }

    private function glyphDirection(int $codePoint): string
    {
        if (($codePoint >= 0x0030 && $codePoint <= 0x0039)
            || ($codePoint >= 0x0660 && $codePoint <= 0x0669)
            || ($codePoint >= 0x06F0 && $codePoint <= 0x06F9)) {
            return 'number';
        }

        if ($codePoint === 0x20 || preg_match('/^[\p{P}\p{S}]$/u', mb_chr($codePoint, 'UTF-8')) === 1) {
            return 'neutral';
        }

        if ($this->isArabicCodePoint($codePoint)) {
            return 'rtl';
        }

        return 'ltr';
    }

    /** @return array<int, string> */
    private function textClusters(string $value): array
    {
        $characters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($characters)) {
            return [];
        }

        $clusters = [];

        foreach ($characters as $character) {
            $codePoint = $this->font->codePoints($character)[0] ?? null;

            if ($codePoint !== null && $this->isCombiningMark($codePoint) && $clusters !== []) {
                $clusters[array_key_last($clusters)] .= $character;

                continue;
            }

            $clusters[] = $character;
        }

        return $clusters;
    }

    private function isArabicCodePoint(int $codePoint): bool
    {
        return isset(self::ARABIC_FORMS[$codePoint])
            || ($codePoint >= 0x0600 && $codePoint <= 0x06FF && ! $this->isCombiningMark($codePoint))
            || ($codePoint >= 0x0750 && $codePoint <= 0x077F)
            || ($codePoint >= 0x08A0 && $codePoint <= 0x08FF && ! $this->isCombiningMark($codePoint))
            || ($codePoint >= 0xFB50 && $codePoint <= 0xFDFF)
            || ($codePoint >= 0xFE70 && $codePoint <= 0xFEFF);
    }

    private function isCombiningMark(int $codePoint): bool
    {
        return ($codePoint >= 0x0610 && $codePoint <= 0x061A)
            || ($codePoint >= 0x064B && $codePoint <= 0x065F)
            || $codePoint === 0x0670
            || ($codePoint >= 0x06D6 && $codePoint <= 0x06ED)
            || ($codePoint >= 0x08D3 && $codePoint <= 0x08FF);
    }

    /**
     * @param  array<int, string>  $objects
     * @param  array<int, int>  $codePointMap
     */
    private function addFontObjects(
        array &$objects,
        int $fontId,
        UnicodePdfFont $font,
        array $codePointMap,
    ): void {
        ksort($codePointMap, SORT_NUMERIC);
        $codePoints = array_keys($codePointMap);
        $cidFontId = $fontId + 1;
        $fontDescriptorId = $fontId + 2;
        $fontFileId = $fontId + 3;
        $cidToGlyphMapId = $fontId + 4;
        $toUnicodeId = $fontId + 5;
        $widths = implode(' ', array_map(
            fn (int $codePoint): string => $codePoint.' ['.$font->width($codePoint).']',
            $codePoints,
        ));
        $boundingBox = implode(' ', $font->boundingBox());
        $fontData = $font->data();
        $cidToGlyphMap = $this->cidToGlyphMap($font, $codePoints);
        $toUnicode = $this->toUnicodeMap($font, $codePointMap);
        $fontName = $font->baseFontName();
        $objects[$fontId] = "<< /Type /Font /Subtype /Type0 /BaseFont /{$fontName} /Encoding /Identity-H /DescendantFonts [{$cidFontId} 0 R] /ToUnicode {$toUnicodeId} 0 R >>";
        $objects[$cidFontId] = "<< /Type /Font /Subtype /CIDFontType2 /BaseFont /{$fontName} /CIDSystemInfo << /Registry (Adobe) /Ordering (Identity) /Supplement 0 >> /FontDescriptor {$fontDescriptorId} 0 R /DW 600 /W [{$widths}] /CIDToGIDMap {$cidToGlyphMapId} 0 R >>";
        $objects[$fontDescriptorId] = "<< /Type /FontDescriptor /FontName /{$fontName} /Flags 32 /FontBBox [{$boundingBox}] /ItalicAngle 0 /Ascent {$font->ascent()} /Descent {$font->descent()} /CapHeight {$font->ascent()} /StemV 80 /FontFile2 {$fontFileId} 0 R >>";
        $objects[$fontFileId] = '<< /Length '.strlen($fontData).' /Length1 '.strlen($fontData).">>\nstream\n{$fontData}\nendstream";
        $objects[$cidToGlyphMapId] = '<< /Length '.strlen($cidToGlyphMap).">>\nstream\n{$cidToGlyphMap}\nendstream";
        $objects[$toUnicodeId] = '<< /Length '.strlen($toUnicode).">>\nstream\n{$toUnicode}\nendstream";
    }

    /**
     * @param  array<int, int>  $codePoints
     */
    private function cidToGlyphMap(UnicodePdfFont $font, array $codePoints): string
    {
        $maximum = max($codePoints ?: [0]);
        $map = str_repeat("\0\0", $maximum + 1);

        foreach ($codePoints as $codePoint) {
            $map = substr_replace(
                $map,
                pack('n', $font->glyphId($codePoint) & 0xFFFF),
                $codePoint * 2,
                2,
            );
        }

        return $map;
    }

    /**
     * @param  array<int, int>  $codePointMap
     */
    private function toUnicodeMap(UnicodePdfFont $font, array $codePointMap): string
    {
        $lines = [
            '/CIDInit /ProcSet findresource begin',
            '12 dict begin',
            'begincmap',
            '/CIDSystemInfo << /Registry (Adobe) /Ordering (UCS) /Supplement 0 >> def',
            '/CMapName /'.$font->baseFontName().'-UCS def',
            '/CMapType 2 def',
            '1 begincodespacerange',
            '<0000> <FFFF>',
            'endcodespacerange',
        ];

        foreach (array_chunk($codePointMap, 100, true) as $chunk) {
            $lines[] = count($chunk).' beginbfchar';

            foreach ($chunk as $cid => $unicode) {
                $lines[] = sprintf('<%04X> <%04X>', $cid, $unicode);
            }

            $lines[] = 'endbfchar';
        }

        $lines[] = 'endcmap';
        $lines[] = 'CMapName currentdict /CMap defineresource pop';
        $lines[] = 'end';
        $lines[] = 'end';

        return implode("\n", $lines);
    }
}
