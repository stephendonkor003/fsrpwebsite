<?php

namespace Tests\Unit\Support;

use App\Models\EventRegistration;
use App\Support\AdminRegistrationExport;
use App\Support\AdminRegistrationReportPdf;
use Carbon\CarbonImmutable;
use Generator;
use Tests\TestCase;

class AdminRegistrationExportTest extends TestCase
{
    public function test_csv_contains_explicit_full_record_columns_and_neutralises_spreadsheet_formulas(): void
    {
        $registration = $this->registration([
            'organisation' => '=HYPERLINK("https://evil.example")',
            'areas_of_expertise' => '  +SUM(1,1)',
            'alternative_phone' => "\t@dangerous",
        ]);
        $export = app(AdminRegistrationExport::class);
        $stream = fopen('php://temp', 'w+b');

        $this->assertIsResource($stream);
        $export->writeCsv([$registration], $stream);
        rewind($stream);

        $this->assertSame("\xEF\xBB\xBF", fread($stream, 3));
        $headers = fgetcsv($stream, null, ',', '"', '');
        $row = fgetcsv($stream, null, ',', '"', '');
        fclose($stream);

        $this->assertSame($export->headers(), $headers);
        $this->assertIsArray($row);
        $this->assertSame(count($headers), count($row));
        $this->assertSame(
            "'=HYPERLINK(\"https://evil.example\")",
            $row[array_search('Organisation', $headers, true)],
        );
        $this->assertSame(
            "'  +SUM(1,1)",
            $row[array_search('Areas of Expertise', $headers, true)],
        );
        $this->assertSame(
            "'\t@dangerous",
            $row[array_search('Alternative Phone', $headers, true)],
        );
        $this->assertSame('Verified', $row[array_search('Official Email Verification', $headers, true)]);
        $this->assertSame('Yes', $row[array_search('Visa Required', $headers, true)]);
        $this->assertSame('2026-09-23', $row[array_search('Arrival Date', $headers, true)]);
    }

    public function test_spreadsheet_xml_is_excel_compatible_unicode_safe_and_never_emits_formulas(): void
    {
        $registration = $this->registration([
            'first_name' => 'أحمد ሰላም',
            'organisation' => '=1+1 <unsafe>',
        ]);
        $export = app(AdminRegistrationExport::class);
        $stream = fopen('php://temp', 'w+b');

        $this->assertIsResource($stream);
        $export->writeSpreadsheetXml([$registration], $stream, 'Seed/Summit:*?[] Report');
        rewind($stream);
        $xml = stream_get_contents($stream);
        fclose($stream);

        $this->assertIsString($xml);
        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"', $xml);
        $this->assertStringContainsString('ss:Name="Seed Summit Report"', $xml);
        $this->assertStringContainsString('أحمد ሰላም', $xml);
        $this->assertStringContainsString('&apos;=1+1 &lt;unsafe&gt;', $xml);
        $this->assertStringNotContainsString('ss:Formula=', $xml);
        $this->assertSame(
            substr_count($xml, '<Cell>'),
            count($export->headers()) * 2,
        );
    }

    public function test_pdf_report_is_branded_unicode_landscape_and_paginates_tabular_records(): void
    {
        $pdf = app(AdminRegistrationReportPdf::class)->render(
            $this->registrationGenerator(12),
            'Seed Summit delegate report',
            "Member state: Cote d'Ivoire",
            CarbonImmutable::parse('2026-09-23 10:30:00', 'UTC'),
        );

        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('/MediaBox [0 0 842 595]', $pdf);
        $this->assertStringContainsString('/Count 2', $pdf);
        $this->assertStringContainsString('/BaseFont /DejaVuSans', $pdf);
        $this->assertStringContainsString('/BaseFont /AbyssinicaSIL', $pdf);
        $this->assertStringContainsString(
            '/ActualText <FEFF'.$this->utf16Hex('Seed Summit delegate report').'>',
            $pdf,
        );
        $this->assertStringContainsString(
            '/ActualText <FEFF'.$this->utf16Hex("Filters: Member state: Cote d'Ivoire").'>',
            $pdf,
        );
        $this->assertStringContainsString(
            '/ActualText <FEFF'.$this->utf16Hex('Page 1 of 2').'>',
            $pdf,
        );
        $this->assertStringContainsString(
            '/ActualText <FEFF'.$this->utf16Hex('Page 2 of 2').'>',
            $pdf,
        );
        $this->assertStringContainsString(
            '/ActualText <FEFF'.$this->utf16Hex('Dr أحمد ሰላም').'>',
            $pdf,
        );
        $this->assertStringContainsString(
            '/ActualText <FEFF'.$this->utf16Hex(
                'Confidential: contains delegate personal data. Store securely and share only when authorised.',
            ).'>',
            $pdf,
        );
    }

    public function test_empty_pdf_report_still_has_a_complete_single_page(): void
    {
        $pdf = app(AdminRegistrationReportPdf::class)->render(
            [],
            generatedAt: CarbonImmutable::parse('2026-09-23 10:30:00', 'UTC'),
        );

        $this->assertStringContainsString('/Count 1', $pdf);
        $this->assertStringContainsString(
            '/ActualText <FEFF'.$this->utf16Hex('No registrations match the selected filters.').'>',
            $pdf,
        );
        $this->assertStringEndsWith('%%EOF', $pdf);
    }

    /** @return Generator<int, EventRegistration> */
    private function registrationGenerator(int $count): Generator
    {
        for ($index = 1; $index <= $count; $index++) {
            yield $this->registration([
                'public_id' => sprintf('10000000-0000-4000-8000-%012d', $index),
                'first_name' => $index === 1 ? 'أحمد' : 'Ama '.$index,
                'surname' => $index === 1 ? 'ሰላም' : 'Mensah',
                'official_email' => 'delegate'.$index.'@example.test',
            ]);
        }
    }

    /** @param array<string, mixed> $overrides */
    private function registration(array $overrides = []): EventRegistration
    {
        $email = (string) ($overrides['official_email'] ?? 'ama.mensah@example.test');
        $emailHash = EventRegistration::emailHash($email);
        $attributes = array_merge([
            'public_id' => '10000000-0000-4000-8000-000000000001',
            'event_slug' => 'inaugural-seed-investment-summit',
            'event_title' => 'Inaugural Seed Investment Summit',
            'event_venue' => 'Ezulwini, Kingdom of Eswatini',
            'event_start_at' => CarbonImmutable::parse('2026-10-20 08:00:00', 'UTC'),
            'event_end_at' => CarbonImmutable::parse('2026-10-22 17:00:00', 'UTC'),
            'locale' => 'en',
            'title' => 'Dr',
            'first_name' => 'Ama',
            'surname' => 'Mensah',
            'gender' => 'Female',
            'date_of_birth' => '1988-06-12',
            'nationality' => 'Ghana',
            'national_id_number' => 'GHA-100200',
            'passport_number' => 'P1234567',
            'passport_expiry_date' => '2029-12-31',
            'issuing_country' => 'Ghana',
            'visa_required' => true,
            'passport_photo_original_name' => 'delegate-photo.jpg',
            'passport_scan_original_name' => 'passport.pdf',
            'organisation' => 'African Union Commission',
            'member_state' => 'Ghana',
            'delegation_capacity' => 'Delegate',
            'years_in_service' => 8,
            'areas_of_expertise' => 'Seed systems',
            'mobile_number' => '+233200000000',
            'alternative_phone' => '+233211111111',
            'official_email' => $email,
            'official_email_hash' => $emailHash,
            'verified_email_hash' => $emailHash,
            'official_email_verified_at' => CarbonImmutable::parse('2026-09-23 10:00:00', 'UTC'),
            'personal_email' => 'ama@example.test',
            'emergency_contact' => 'Kojo +233244444444',
            'arrival_date' => '2026-09-23',
            'departure_date' => '2026-10-23',
            'dietary_requirements' => 'Halal',
            'other_dietary_needs' => null,
            'dinner_attendance' => true,
            'confirmation_email_status' => EventRegistration::EMAIL_SENT,
            'receipt_email_status' => EventRegistration::EMAIL_SENT,
            'data_protection_accepted_at' => CarbonImmutable::parse('2026-09-23 09:45:00', 'UTC'),
            'attendance_confirmed_at' => CarbonImmutable::parse('2026-09-23 09:45:00', 'UTC'),
            'consent_version' => '2026-09-21-v2',
            'consent_text_hash' => str_repeat('a', 64),
        ], $overrides);
        $attributes['official_email_hash'] = EventRegistration::emailHash((string) $attributes['official_email']);
        $attributes['verified_email_hash'] = $attributes['official_email_hash'];
        $registration = new EventRegistration($attributes);
        $registration->setAttribute('created_at', CarbonImmutable::parse('2026-09-23 09:45:00', 'UTC'));

        return $registration;
    }

    private function utf16Hex(string $value): string
    {
        return strtoupper(bin2hex(mb_convert_encoding($value, 'UTF-16BE', 'UTF-8')));
    }
}
