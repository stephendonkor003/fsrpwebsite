<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registration confirmed - {{ $eventTitle }}</title>
</head>
<body style="margin:0;padding:0;background:#f5f0e5;color:#173b2c;font-family:Arial,'Segoe UI',sans-serif;line-height:1.6;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">Your official email is confirmed and your complete registration PDF is attached.</div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#f5f0e5;">
        <tr>
            <td align="center" style="padding:30px 14px;">
                <table role="presentation" width="640" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:640px;background:#ffffff;border:1px solid #d9e3da;border-radius:18px;overflow:hidden;box-shadow:0 12px 32px rgba(22,59,44,.10);">
                    <tr>
                        <td style="padding:30px 34px;background:#063f2d;color:#ffffff;border-bottom:6px solid #bf9226;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="150" valign="middle">
                                        <img src="{{ $message->embed(public_path('images/fsrp/african-union-logo.png')) }}" width="126" alt="African Union" style="display:block;width:126px;max-width:100%;height:auto;border:0;">
                                    </td>
                                    <td valign="top">
                                        <p style="margin:0 0 6px;color:#f0cf72;font-size:11px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;">African Union &bull; CAADP</p>
                                        <h1 style="margin:0;font-size:27px;line-height:1.18;">Email confirmed</h1>
                                        <p style="margin:9px 0 0;color:#d8e8de;font-size:15px;">{{ $eventTitle }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 34px;background:#fff8e8;color:#705719;border-bottom:1px solid #efe2be;font-size:13px;font-weight:700;text-align:center;">{{ $eventTheme }}</td>
                    </tr>
                    <tr>
                        <td style="padding:32px 34px 22px;">
                            <p style="margin:0 0 17px;font-size:17px;">Dear {{ $delegateName }},</p>
                            <p style="margin:0 0 17px;">Your official email has been confirmed. Thank you for registering for the {{ $eventTitle }}.</p>
                            <p style="margin:0 0 24px;color:#52685d;">A PDF copy of the complete information submitted with your registration is attached to this email for your records.</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;background:#edf5ef;border:1px solid #d2e3d7;border-radius:10px;">
                                <tr>
                                    <td style="padding:14px 16px;color:#5d7066;font-size:11px;text-transform:uppercase;letter-spacing:1px;">Registration reference</td>
                                    <td align="right" style="padding:14px 16px;color:#06472f;font-size:14px;font-weight:800;word-break:break-all;overflow-wrap:anywhere;">{{ $registrationReference }}</td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;border-collapse:collapse;">
                                <tr>
                                    <td style="width:105px;padding:9px 0;color:#6a7b72;font-size:13px;border-bottom:1px solid #e4ebe6;">Date</td>
                                    <td style="padding:9px 0;color:#173b2c;font-size:14px;font-weight:700;border-bottom:1px solid #e4ebe6;">{{ $eventDate }}</td>
                                </tr>
                                <tr>
                                    <td style="width:105px;padding:9px 0;color:#6a7b72;font-size:13px;">Venue</td>
                                    <td style="padding:9px 0;color:#173b2c;font-size:14px;font-weight:700;">{{ $eventVenue }}</td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 20px;background:#063f2d;border-radius:11px;color:#ffffff;">
                                <tr>
                                    <td width="52" style="padding:17px 0 17px 18px;color:#f0cf72;font-size:19px;font-weight:800;text-align:center;">PDF</td>
                                    <td style="padding:17px 18px;">
                                        <strong style="display:block;font-size:15px;">Complete registration copy attached</strong>
                                        <span style="display:block;color:#d8e8de;font-size:12px;">Keep this document secure; it contains personal and identity information.</span>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 18px;padding:15px 17px;color:#655323;background:#fff8e6;border-left:4px solid #bf9226;font-size:13px;"><strong>Privacy notice:</strong> Do not forward the attached PDF. Store it securely and delete it when it is no longer needed.</p>
                            <p style="margin:0;color:#52685d;font-size:13px;">This confirms receipt of your registration record. Any accreditation, visa, travel, accommodation, or programme arrangements will be communicated separately by the organisers.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:23px 34px 28px;background:#f4f7f4;color:#64756b;font-size:12px;border-top:1px solid #e2e9e3;">
                            <p style="margin:0 0 5px;color:#234d39;font-weight:800;letter-spacing:.5px;">OFFICE OF THE COMMISSIONER - ARBE</p>
                            <p style="margin:0 0 10px;">African Union Commission &bull; Inaugural Seed Investment Summit</p>
                            <p style="margin:0;">This is an automated confirmation message.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
