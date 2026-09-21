<?php

namespace App\Support;

use App\Models\EventRegistration;
use Carbon\CarbonInterface;

class SeedSummitRegistrationSummary
{
    /**
     * @return array<int, array{title: string, items: array<int, array{label: string, value: string}>}>
     */
    public function for(EventRegistration $registration): array
    {
        return [
            [
                'title' => 'Registration',
                'items' => [
                    $this->item('Reference', $registration->public_id),
                    $this->item('Event', $registration->event_title),
                    $this->item('Event dates', $this->eventDates($registration)),
                    $this->item('Venue', $registration->event_venue),
                    $this->item('Submitted', $this->dateTime($registration->created_at)),
                ],
            ],
            [
                'title' => 'Personal Information',
                'items' => [
                    $this->item('Title', $registration->title),
                    $this->item('First Name', $registration->first_name),
                    $this->item('Surname', $registration->surname),
                    $this->item('Gender', $registration->gender),
                    $this->item('Date of Birth', $this->date($registration->date_of_birth)),
                    $this->item('Nationality', $registration->nationality),
                ],
            ],
            [
                'title' => 'Identification',
                'items' => [
                    $this->item('National ID Number', $registration->national_id_number),
                    $this->item('Passport Number', $registration->passport_number),
                    $this->item('Passport Expiry Date', $this->date($registration->passport_expiry_date)),
                    $this->item('Issuing Country', $registration->issuing_country),
                    $this->item('Visa Required', $this->yesNo($registration->visa_required)),
                    $this->item('Delegate Profile Photo', $registration->passport_photo_original_name),
                    $this->item('Passport Scan', $registration->passport_scan_original_name),
                ],
            ],
            [
                'title' => 'Professional Information',
                'items' => [
                    $this->item('Organisation', $registration->organisation),
                    $this->item('Member State', $registration->member_state),
                    $this->item('Delegation Capacity', $registration->delegation_capacity),
                    $this->item('Years in Service', $registration->years_in_service),
                    $this->item('Areas of Expertise', $registration->areas_of_expertise),
                ],
            ],
            [
                'title' => 'Contact',
                'items' => [
                    $this->item('Mobile Number', $registration->mobile_number),
                    $this->item('Alternative Phone', $registration->alternative_phone),
                    $this->item('Official Email', $registration->official_email),
                    $this->item('Personal Email', $registration->personal_email),
                    $this->item('Emergency Contact', $registration->emergency_contact),
                ],
            ],
            [
                'title' => 'Logistics',
                'items' => [
                    $this->item('Arrival Date', $this->date($registration->arrival_date)),
                    $this->item('Departure Date', $this->date($registration->departure_date)),
                    $this->item('Dietary Requirements', $registration->dietary_requirements),
                    $this->item('Other Dietary Needs', $registration->other_dietary_needs),
                    $this->item('Dinner Attendance', $this->yesNo($registration->dinner_attendance)),
                ],
            ],
            [
                'title' => 'Declaration',
                'items' => [
                    $this->item('Data Protection Declaration', 'Accepted '.$this->dateTime($registration->data_protection_accepted_at)),
                    $this->item('Attendance Confirmation', 'Confirmed '.$this->dateTime($registration->attendance_confirmed_at)),
                    $this->item('Declaration Version', $registration->consent_version),
                ],
            ],
        ];
    }

    /** @return array{label: string, value: string} */
    private function item(string $label, mixed $value): array
    {
        $display = is_scalar($value) ? trim((string) $value) : '';

        return ['label' => $label, 'value' => $display !== '' ? $display : 'Not provided'];
    }

    private function date(mixed $value): string
    {
        return $value instanceof CarbonInterface ? $value->format('d M Y') : '';
    }

    private function dateTime(mixed $value): string
    {
        return $value instanceof CarbonInterface ? $value->format('d M Y, H:i T') : '';
    }

    private function yesNo(?bool $value): string
    {
        return match ($value) {
            true => 'Yes',
            false => 'No',
            null => '',
        };
    }

    private function eventDates(EventRegistration $registration): string
    {
        if (! ($registration->event_start_at instanceof CarbonInterface)) {
            return '';
        }

        if (! ($registration->event_end_at instanceof CarbonInterface)) {
            return $registration->event_start_at->format('d M Y');
        }

        return $registration->event_start_at->format('d M').' - '.$registration->event_end_at->format('d M Y');
    }
}
