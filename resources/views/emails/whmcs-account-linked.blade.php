<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Account Linked</title>
</head>
<body style="margin:0; padding:0; background:#020617; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#020617; padding:40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; background:#0f172a; border:1px solid rgba(255,255,255,0.08); border-radius:28px; overflow:hidden;">
                    <tr>
                        <td style="padding:40px 36px 16px; text-align:center;">
                            <div style="display:inline-block; padding:8px 14px; border-radius:999px; background:rgba(52,211,153,0.12); color:#6ee7b7; font-size:12px; font-weight:700; letter-spacing:0.5px;">
                                ACCOUNT SECURITY
                            </div>

                            <h1 style="margin:18px 0 12px; color:#ffffff; font-size:30px; line-height:1.25;">
                                Billing account linked
                            </h1>

                            <p style="margin:0; color:#cbd5e1; font-size:15px; line-height:1.8;">
                                Hi {{ $user->name }},<br>
                                your WHMCS billing account (<strong style="color:#ffffff;">{{ $whmcsEmail }}</strong>) has been linked to your portal account by an administrator.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 36px 22px;">
                            <div style="background:rgba(52,211,153,0.06); border:1px solid rgba(52,211,153,0.2); border-radius:18px; padding:18px;">
                                <p style="margin:0 0 8px; color:#ffffff; font-size:14px; font-weight:700;">
                                    What does this mean?
                                </p>
                                <p style="margin:0; color:#94a3b8; font-size:14px; line-height:1.7;">
                                    You can now view your WHMCS invoices and billing information directly from your portal. Your billing services are now accessible from your account.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 36px 22px; text-align:center;">
                            <a href="{{ route('account.link-whmcs') }}" style="display:inline-block; background:#3b82f6; color:#ffffff; font-size:14px; font-weight:700; text-decoration:none; padding:12px 28px; border-radius:14px;">
                                View Billing Account
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 36px 22px;">
                            <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:18px; padding:18px;">
                                <p style="margin:0 0 8px; color:#ffffff; font-size:14px; font-weight:700;">
                                    Did not expect this?
                                </p>
                                <p style="margin:0; color:#94a3b8; font-size:14px; line-height:1.7;">
                                    If you did not request this change, please contact our support team immediately.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:18px 36px; background:rgba(255,255,255,0.03); text-align:center;">
                            <p style="margin:0; color:#64748b; font-size:12px;">
                                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
