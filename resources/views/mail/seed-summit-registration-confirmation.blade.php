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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registration acknowledgement — {{ $eventTitle }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f1e9;color:#1d3429;font-family:Arial,'Segoe UI',sans-serif;line-height:1.6;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#f4f1e9;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="640" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:640px;background:#ffffff;border:1px solid #dce6dd;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="padding:30px 36px;background:#004c2f;color:#ffffff;border-bottom:5px solid #c59a28;">
                            <p style="margin:0 0 10px;color:#f1cc68;font-size:12px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;">African Union · CAADP</p>
                            <h1 style="margin:0;font-size:28px;line-height:1.2;">Registration received</h1>
                            <p style="margin:10px 0 0;color:#d9e8df;font-size:15px;">{{ $eventTitle }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:34px 36px 18px;">
                            <p style="margin:0 0 18px;font-size:17px;">Dear {{ $delegateName }},</p>
                            <p style="margin:0 0 18px;">Thank you for registering. We have received your delegate registration for the {{ $eventTitle }}.</p>
                            <p style="margin:0 0 24px;color:#5d6f64;">This message confirms receipt only. Please retain it for your records and follow any further accreditation or logistics instructions sent by the organisers.</p>

                            @if($registrationReference)
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;background:#edf5ee;border:1px solid #d3e3d7;border-radius:9px;">
                                    <tr>
                                        <td style="padding:15px 18px;color:#52665a;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Registration reference</td>
                                        <td align="right" style="padding:15px 18px;color:#004c2f;font-size:16px;font-weight:700;word-break:break-all;overflow-wrap:anywhere;">{{ $registrationReference }}</td>
                                    </tr>
                                </table>
                            @endif

                            <h2 style="margin:0 0 14px;color:#004c2f;font-size:19px;">Event details</h2>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 26px;border-collapse:collapse;">
                                <tr>
                                    <td style="width:130px;padding:9px 0;color:#687a70;font-size:13px;border-bottom:1px solid #e5ebe6;">Date</td>
                                    <td style="padding:9px 0;color:#1d3429;font-size:14px;font-weight:700;border-bottom:1px solid #e5ebe6;">{{ $eventDate }}</td>
                                </tr>
                                <tr>
                                    <td style="width:130px;padding:9px 0;color:#687a70;font-size:13px;border-bottom:1px solid #e5ebe6;">Venue</td>
                                    <td style="padding:9px 0;color:#1d3429;font-size:14px;font-weight:700;border-bottom:1px solid #e5ebe6;">{{ $eventVenue }}</td>
                                </tr>
                                @if($registrationValue('organisation'))
                                    <tr>
                                        <td style="width:130px;padding:9px 0;color:#687a70;font-size:13px;border-bottom:1px solid #e5ebe6;">Organisation</td>
                                        <td style="padding:9px 0;color:#1d3429;font-size:14px;font-weight:700;border-bottom:1px solid #e5ebe6;">{{ $registrationValue('organisation') }}</td>
                                    </tr>
                                @endif
                                @if($registrationValue('member_state'))
                                    <tr>
                                        <td style="width:130px;padding:9px 0;color:#687a70;font-size:13px;border-bottom:1px solid #e5ebe6;">Member State</td>
                                        <td style="padding:9px 0;color:#1d3429;font-size:14px;font-weight:700;border-bottom:1px solid #e5ebe6;">{{ $registrationValue('member_state') }}</td>
                                    </tr>
                                @endif
                                @if($registrationValue('delegation_capacity'))
                                    <tr>
                                        <td style="width:130px;padding:9px 0;color:#687a70;font-size:13px;">Capacity</td>
                                        <td style="padding:9px 0;color:#1d3429;font-size:14px;font-weight:700;">{{ $registrationValue('delegation_capacity') }}</td>
                                    </tr>
                                @endif
                            </table>

                            <p style="margin:0 0 18px;">Please confirm that you control the official email address used for this registration.</p>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 18px;">
                                <tr>
                                    <td style="padding:0 10px 10px 0;">
                                        <a href="{{ $verificationUrl }}" style="display:inline-block;padding:13px 20px;color:#ffffff;background:#006b3f;border-radius:24px;font-size:14px;font-weight:700;text-decoration:none;">Confirm official email</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;padding:15px 17px;color:#665528;background:#fff8e5;border-left:4px solid #c59a28;font-size:13px;">For your privacy, the full registration record and PDF are not included in this email. Return to the browser used to register if you still need to download the receipt.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 36px 30px;color:#65756b;font-size:13px;">
                            <p style="margin:0 0 5px;font-weight:700;color:#315343;">SEED SUMMIT</p>
                            <p style="margin:0;">Office of the Commissioner — ARBE · African Union Commission</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
