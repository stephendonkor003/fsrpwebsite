<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registration acknowledgement - {{ $eventTitle }}</title>
    <style>
        @media only screen and (max-width: 620px) {
            .mail-canvas { width: 100% !important; table-layout: fixed !important; }
            .mail-canvas-pad { padding: 20px 12px !important; }
            .mail-frame { width: 100% !important; max-width: 100% !important; border-radius: 12px !important; }
            .mail-shell { width: 100% !important; max-width: 100% !important; table-layout: fixed !important; }
            .mail-shell table { width: 100% !important; max-width: 100% !important; table-layout: fixed !important; }
            .mail-shell td, .mail-shell p, .mail-shell h1, .mail-shell span, .mail-shell strong { max-width: 100% !important; box-sizing: border-box !important; overflow-wrap: anywhere !important; }
            .mail-pad { padding-left: 22px !important; padding-right: 22px !important; }
            .mail-brand-table, .mail-brand-table tbody, .mail-brand-table tr { display: block !important; width: 100% !important; }
            .mail-brand-logo, .mail-brand-copy { display: block !important; width: 100% !important; }
            .mail-brand-logo { padding-bottom: 18px !important; }
            .mail-brand-copy h1 { font-size: 24px !important; overflow-wrap: anywhere !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#f5f0e5;color:#173b2c;font-family:Arial,'Segoe UI',sans-serif;line-height:1.6;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">Your registration has been acknowledged and your professionally designed PDF copy is attached.</div>
    <table class="mail-canvas" role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;table-layout:fixed;background:#f5f0e5;">
        <tr>
            <td class="mail-canvas-pad" align="center" style="padding:30px 14px;">
                <div class="mail-frame" style="width:100%;max-width:640px;background:#ffffff;border:1px solid #d9e3da;border-radius:18px;overflow:hidden;box-shadow:0 12px 32px rgba(22,59,44,.10);">
                <table class="mail-shell" role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;table-layout:fixed;background:#ffffff;">
                    <tr>
                        <td class="mail-pad" style="padding:30px 34px;background:#063f2d;color:#ffffff;border-bottom:6px solid #bf9226;">
                            <table class="mail-brand-table" role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td class="mail-brand-logo" width="150" valign="middle">
                                        <img src="{{ $message->embed(public_path('images/brand/african-union-logo.png')) }}" width="126" alt="African Union" style="display:block;width:126px;max-width:100%;height:auto;border:0;">
                                    </td>
                                    <td class="mail-brand-copy" valign="top">
                                        <p style="margin:0 0 6px;color:#f0cf72;font-size:11px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;">African Union Commission &bull; ARBE</p>
                                        <h1 style="margin:0;font-size:27px;line-height:1.18;">Registration acknowledgement</h1>
                                        <p style="margin:9px 0 0;color:#d8e8de;font-size:15px;">{{ $eventTitle }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="mail-pad" style="padding:16px 34px;background:#fff8e8;color:#705719;border-bottom:1px solid #efe2be;font-size:13px;font-weight:700;text-align:center;">{{ $eventTheme }}</td>
                    </tr>
                    <tr>
                        <td class="mail-pad" style="padding:32px 34px 22px;">
                            <p style="margin:0 0 18px;"><span style="display:inline-block;padding:6px 11px;background:#e8f4ec;border:1px solid #cde2d4;border-radius:999px;color:#087443;font-size:11px;font-weight:800;letter-spacing:.8px;text-transform:uppercase;">Email confirmed &bull; Registration received</span></p>
                            <p style="margin:0 0 17px;font-size:17px;">Dear {{ $delegateName }},</p>
                            <p style="margin:0 0 17px;">Thank you for confirming your official email. This message formally acknowledges receipt of your delegate registration for the {{ $eventTitle }}.</p>
                            <p style="margin:0 0 24px;color:#52685d;">Your professionally designed PDF registration copy is attached for your private records. It contains the information and profile photo submitted with your registration.</p>

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
                                        <span style="display:block;color:#d8e8de;font-size:12px;">Professionally formatted for your records. Keep it secure because it contains personal and identity information.</span>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 18px;padding:15px 17px;color:#655323;background:#fff8e6;border-left:4px solid #bf9226;font-size:13px;"><strong>Privacy notice:</strong> Do not forward the attached PDF. Store it securely and delete it when it is no longer needed.</p>
                            <p style="margin:0;color:#52685d;font-size:13px;">This acknowledgement is not an accreditation or a confirmation of visa, travel, accommodation or programme arrangements. The organisers will communicate those separately.</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="mail-pad" style="padding:23px 34px 28px;background:#f4f7f4;color:#64756b;font-size:12px;border-top:1px solid #e2e9e3;">
                            <p style="margin:0 0 5px;color:#234d39;font-weight:800;letter-spacing:.5px;">OFFICE OF THE COMMISSIONER - ARBE</p>
                            <p style="margin:0 0 10px;">African Union Commission &bull; Inaugural Seed Investment Summit</p>
                            <p style="margin:0;">This is an automated registration acknowledgement. Please retain it with the attached PDF for your records.</p>
                        </td>
                    </tr>
                </table>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
