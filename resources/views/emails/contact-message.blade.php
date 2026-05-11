<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Contact Message</title>
</head>

<body style="margin:0; padding:0; background:#f1f5f9; font-family:Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9; padding:30px 15px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0"
                    style="max-width:650px; background:#ffffff; border-radius:18px; overflow:hidden; border:1px solid #e2e8f0;">

                    <tr>
                        <td style="background:#0f172a; padding:24px 28px;">
                            <h1 style="margin:0; color:#ffffff; font-size:22px;">
                                New Contact Message
                            </h1>
                            <p style="margin:8px 0 0; color:#cbd5e1; font-size:14px;">
                                Someone submitted the contact form from your website.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:10px 0; color:#64748b; font-size:14px;">Name</td>
                                    <td style="padding:10px 0; color:#0f172a; font-size:14px; font-weight:bold;">
                                        {{ $contactMessage->name }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:10px 0; color:#64748b; font-size:14px;">Email</td>
                                    <td style="padding:10px 0; color:#0f172a; font-size:14px; font-weight:bold;">
                                        {{ $contactMessage->email }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:10px 0; color:#64748b; font-size:14px;">Phone</td>
                                    <td style="padding:10px 0; color:#0f172a; font-size:14px; font-weight:bold;">
                                        {{ $contactMessage->phone ?: 'N/A' }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:10px 0; color:#64748b; font-size:14px;">Subject</td>
                                    <td style="padding:10px 0; color:#0f172a; font-size:14px; font-weight:bold;">
                                        {{ $contactMessage->subject }}
                                    </td>
                                </tr>
                            </table>

                            <div style="margin-top:22px; padding:18px; background:#f8fafc; border-radius:14px; border:1px solid #e2e8f0;">
                                <p style="margin:0 0 10px; color:#475569; font-size:13px; font-weight:bold;">
                                    Message
                                </p>

                                <p style="margin:0; color:#0f172a; font-size:15px; line-height:1.7; white-space:pre-line;">
                                    {{ $contactMessage->message }}
                                </p>
                            </div>

                            <p style="margin:22px 0 0; color:#94a3b8; font-size:12px;">
                                Submitted at {{ $contactMessage->created_at?->format('d M Y, h:i A') }}
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>