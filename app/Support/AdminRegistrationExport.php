<?php

namespace App\Support;

use App\Models\EventRegistration;
use DateTimeInterface;
use RuntimeException;

final class AdminRegistrationExport
{
    /** @var array<string, string> */
    private const COLUMNS = [
        'public_id' => 'Registration Reference',
        'created_at' => 'Submitted At',
        'event_title' => 'Event',
        'event_venue' => 'Event Venue',
        'event_start_at' => 'Event Start',
        'event_end_at' => 'Event End',
        'locale' => 'Language',
        'full_name' => 'Full Name',
        'title' => 'Title',
        'first_name' => 'First Name',
        'surname' => 'Surname',
        'gender' => 'Gender',
        'date_of_birth' => 'Date of Birth',
        'nationality' => 'Nationality',
        'national_id_number' => 'National ID Number',
        'passport_number' => 'Passport Number',
        'passport_expiry_date' => 'Passport Expiry Date',
        'issuing_country' => 'Issuing Country',
        'visa_required' => 'Visa Required',
        'passport_photo_original_name' => 'Profile Photo Filename',
        'passport_scan_original_name' => 'Passport Scan Filename',
        'organisation' => 'Organisation',
        'member_state' => 'Member State',
        'delegation_capacity' => 'Delegation Capacity',
        'years_in_service' => 'Years in Service',
        'areas_of_expertise' => 'Areas of Expertise',
        'mobile_number' => 'Mobile Number',
        'alternative_phone' => 'Alternative Phone',
        'official_email' => 'Official Email',
        'official_email_verification' => 'Official Email Verification',
        'official_email_verified_at' => 'Official Email Verified At',
        'personal_email' => 'Personal Email',
        'emergency_contact' => 'Emergency Contact',
        'arrival_date' => 'Arrival Date',
        'departure_date' => 'Departure Date',
        'dietary_requirements' => 'Dietary Requirements',
        'other_dietary_needs' => 'Other Dietary Needs',
        'dinner_attendance' => 'Dinner Attendance',
        'confirmation_email_status' => 'Verification Email Status',
        'receipt_email_status' => 'Receipt Email Status',
        'data_protection_accepted_at' => 'Data Protection Accepted At',
        'attendance_confirmed_at' => 'Attendance Confirmed At',
        'consent_version' => 'Consent Version',
        'consent_text_hash' => 'Consent Text Hash',
    ];

    /** @return array<string, string> */
    public function columns(): array
    {
        return self::COLUMNS;
    }

    /** @return array<int, string> */
    public function headers(): array
    {
        return array_values(self::COLUMNS);
    }

    /** @return array<int, string> */
    public function row(EventRegistration $registration): array
    {
        $values = [
            'public_id' => $registration->public_id,
            'created_at' => $this->dateTime($registration->created_at),
            'event_title' => $registration->event_title,
            'event_venue' => $registration->event_venue,
            'event_start_at' => $this->dateTime($registration->event_start_at),
            'event_end_at' => $this->dateTime($registration->event_end_at),
            'locale' => $registration->locale,
            'full_name' => $registration->fullName(),
            'title' => $registration->title,
            'first_name' => $registration->first_name,
            'surname' => $registration->surname,
            'gender' => $registration->gender,
            'date_of_birth' => $this->date($registration->date_of_birth),
            'nationality' => $registration->nationality,
            'national_id_number' => $registration->national_id_number,
            'passport_number' => $registration->passport_number,
            'passport_expiry_date' => $this->date($registration->passport_expiry_date),
            'issuing_country' => $registration->issuing_country,
            'visa_required' => $this->yesNo($registration->visa_required),
            'passport_photo_original_name' => $registration->passport_photo_original_name,
            'passport_scan_original_name' => $registration->passport_scan_original_name,
            'organisation' => $registration->organisation,
            'member_state' => $registration->member_state,
            'delegation_capacity' => $registration->delegation_capacity,
            'years_in_service' => $registration->years_in_service,
            'areas_of_expertise' => $registration->areas_of_expertise,
            'mobile_number' => $registration->mobile_number,
            'alternative_phone' => $registration->alternative_phone,
            'official_email' => $registration->official_email,
            'official_email_verification' => $registration->hasVerifiedOfficialEmail()
                ? 'Verified'
                : 'Unverified',
            'official_email_verified_at' => $this->dateTime($registration->official_email_verified_at),
            'personal_email' => $registration->personal_email,
            'emergency_contact' => $registration->emergency_contact,
            'arrival_date' => $this->date($registration->arrival_date),
            'departure_date' => $this->date($registration->departure_date),
            'dietary_requirements' => $registration->dietary_requirements,
            'other_dietary_needs' => $registration->other_dietary_needs,
            'dinner_attendance' => $this->yesNo($registration->dinner_attendance),
            'confirmation_email_status' => $this->status($registration->confirmation_email_status),
            'receipt_email_status' => $this->status($registration->receipt_email_status),
            'data_protection_accepted_at' => $this->dateTime($registration->data_protection_accepted_at),
            'attendance_confirmed_at' => $this->dateTime($registration->attendance_confirmed_at),
            'consent_version' => $registration->consent_version,
            'consent_text_hash' => $registration->consent_text_hash,
        ];

        return array_map(
            fn (string $column): string => $this->stringValue($values[$column] ?? null),
            array_keys(self::COLUMNS),
        );
    }

    /**
     * @param  iterable<EventRegistration>  $registrations
     * @param  resource  $stream
     */
    public function writeCsv(iterable $registrations, mixed $stream): void
    {
        $this->ensureStream($stream);
        $this->write($stream, "\xEF\xBB\xBF");
        $this->writeCsvRow($stream, $this->headers());

        foreach ($registrations as $registration) {
            $this->writeCsvRow($stream, array_map(
                $this->spreadsheetSafeValue(...),
                $this->row($registration),
            ));
        }
    }

    /**
     * @param  iterable<EventRegistration>  $registrations
     * @param  resource  $stream
     */
    public function writeSpreadsheetXml(
        iterable $registrations,
        mixed $stream,
        string $worksheetTitle = 'Registrations',
    ): void {
        $this->ensureStream($stream);
        $worksheetTitle = $this->worksheetTitle($worksheetTitle);

        $this->write($stream, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Top" ss:WrapText="1"/><Font ss:FontName="Aptos" ss:Size="10"/></Style>
  <Style ss:ID="Header"><Alignment ss:Vertical="Center" ss:WrapText="1"/><Font ss:FontName="Aptos" ss:Size="10" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#0B5D46" ss:Pattern="Solid"/></Style>
 </Styles>
XML);
        $this->write(
            $stream,
            "\n <Worksheet ss:Name=\"".$this->xml($worksheetTitle)."\">\n  <Table>\n",
        );
        $this->write($stream, '   <Row ss:StyleID="Header">'.$this->spreadsheetCells($this->headers())."</Row>\n");

        foreach ($registrations as $registration) {
            $this->write(
                $stream,
                '   <Row>'.$this->spreadsheetCells($this->row($registration))."</Row>\n",
            );
        }

        $this->write($stream, <<<'XML'
  </Table>
  <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
   <FreezePanes/><FrozenNoSplit/><SplitHorizontal>1</SplitHorizontal><TopRowBottomPane>1</TopRowBottomPane>
   <ProtectObjects>False</ProtectObjects><ProtectScenarios>False</ProtectScenarios>
  </WorksheetOptions>
 </Worksheet>
</Workbook>
XML);
    }

    /** @param resource $stream */
    private function ensureStream(mixed $stream): void
    {
        if (! is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new RuntimeException('The registration export requires a writable stream resource.');
        }

        $metadata = stream_get_meta_data($stream);
        $mode = (string) ($metadata['mode'] ?? '');

        if (! strpbrk($mode, 'waxc+')) {
            throw new RuntimeException('The registration export stream is not writable.');
        }
    }

    /**
     * @param  resource  $stream
     * @param  array<int, string>  $values
     */
    private function writeCsvRow(mixed $stream, array $values): void
    {
        if (fputcsv($stream, $values, ',', '"', '', "\r\n") === false) {
            throw new RuntimeException('The registration CSV export could not be written.');
        }
    }

    /** @param resource $stream */
    private function write(mixed $stream, string $contents): void
    {
        $remaining = $contents;

        while ($remaining !== '') {
            $written = fwrite($stream, $remaining);

            if ($written === false || $written === 0) {
                throw new RuntimeException('The registration export could not be written.');
            }

            $remaining = substr($remaining, $written);
        }
    }

    /** @param array<int, string> $values */
    private function spreadsheetCells(array $values): string
    {
        return implode('', array_map(
            fn (string $value): string => '<Cell><Data ss:Type="String">'
                .$this->xml($this->spreadsheetSafeValue($value))
                .'</Data></Cell>',
            $values,
        ));
    }

    private function spreadsheetSafeValue(string $value): string
    {
        $value = str_replace("\0", '', $value);

        if (preg_match('/^[\t\r]/u', $value) === 1
            || preg_match('/^\s*[=+\-@]/u', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }

    private function xml(string $value): string
    {
        $value = preg_replace(
            '/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u',
            '',
            $value,
        ) ?? '';

        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function worksheetTitle(string $title): string
    {
        $title = preg_replace('/[:\\\\\/?*\[\]]/u', ' ', trim($title)) ?? '';
        $title = preg_replace('/\s+/u', ' ', $title) ?? '';
        $title = mb_substr($title, 0, 31, 'UTF-8');

        return $title !== '' ? $title : 'Registrations';
    }

    private function date(mixed $value): string
    {
        return $value instanceof DateTimeInterface ? $value->format('Y-m-d') : '';
    }

    private function dateTime(mixed $value): string
    {
        return $value instanceof DateTimeInterface ? $value->format('Y-m-d H:i:s T') : '';
    }

    private function yesNo(?bool $value): string
    {
        return match ($value) {
            true => 'Yes',
            false => 'No',
            null => 'Not provided',
        };
    }

    private function status(mixed $value): string
    {
        $status = $this->stringValue($value);

        return $status === '' ? '' : ucfirst(str_replace('_', ' ', $status));
    }

    private function stringValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return '';
    }
}
