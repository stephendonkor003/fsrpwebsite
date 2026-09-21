@php
    $eventValue = static function (string $key, mixed $fallback = null) use ($event): mixed {
        $value = null;

        if (is_object($event) && method_exists($event, 'translate')) {
            $value = $event->translate($key);
        }

        if ($value === null || $value === '') {
            $value = data_get($event, $key);
        }

        if (is_array($value)) {
            $value = $value['en'] ?? $value['value'] ?? null;
        }

        return $value === null || $value === '' ? $fallback : $value;
    };
    $registrationValue = static fn (string $key, mixed $fallback = null): mixed => data_get($registration, $key, $fallback);
    $delegateName = trim(implode(' ', array_filter([
        $registrationValue('title'),
        $registrationValue('first_name'),
        $registrationValue('surname'),
    ])));
    $delegateName = $delegateName !== '' ? $delegateName : 'Delegate';
    $eventTitle = (string) $eventValue('title', 'Inaugural Seed Investment Summit');
    $eventDate = (string) $eventValue('date_display', '5–7 October 2026');
    $eventVenue = (string) $eventValue('venue', 'Ezulwini, Eswatini');
    $registrationReference = $registrationValue('public_id', $registrationValue('reference_code', $registrationValue('registration_number', $registrationValue('uuid', $registrationValue('id')))));
@endphp
REGISTRATION RECEIVED
{{ $eventTitle }}

Dear {{ $delegateName }},

Thank you for registering. We have received your delegate registration for the {{ $eventTitle }}.

This message confirms receipt only. Please retain it for your records and follow any further accreditation or logistics instructions sent by the organisers.

@if($registrationReference)
Registration reference: {{ $registrationReference }}
@endif
Date: {{ $eventDate }}
Venue: {{ $eventVenue }}
@if($registrationValue('organisation'))
Organisation: {{ $registrationValue('organisation') }}
@endif
@if($registrationValue('member_state'))
Member State: {{ $registrationValue('member_state') }}
@endif
@if($registrationValue('delegation_capacity'))
Delegation Capacity: {{ $registrationValue('delegation_capacity') }}
@endif

Confirm official email: {{ $verificationUrl }}

For your privacy, the full registration record and PDF are not included in this email. Return to the browser used to register if you still need to download the receipt.

SEED SUMMIT
Office of the Commissioner — ARBE
African Union Commission
