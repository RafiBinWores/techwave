<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email</title>
</head>
<body style="margin:0; padding:0; background:#020617; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#020617; padding:40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; background:#0f172a; border:1px solid rgba(255,255,255,0.08); border-radius:28px; overflow:hidden;">
                    <tr>
                        <td style="padding:40px 36px 16px; text-align:center;">
                            <div style="display:inline-block; padding:8px 14px; border-radius:999px; background:rgba(56,189,248,0.12); color:#7dd3fc; font-size:12px; font-weight:700; letter-spacing:0.5px;">
                                ACCOUNT SECURITY
                            </div>

                            <h1 style="margin:18px 0 12px; color:#ffffff; font-size:30px; line-height:1.25;">
                                Verify your email address
                            </h1>

                            <p style="margin:0; color:#cbd5e1; font-size:15px; line-height:1.8;">
                                Hi {{ $user->name ?? 'there' }},<br>
                                thanks for registering. Please verify your email address to complete your account setup.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:12px 36px 28px; text-align:center;">
                            <a href="{{ $verificationUrl }}"
                               style="display:inline-block; background:linear-gradient(90deg,#2563eb 0%,#38bdf8 100%); color:#ffffff; text-decoration:none; font-size:15px; font-weight:700; padding:16px 28px; border-radius:999px;">
                                Verify Email Address
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 36px 22px;">
                            <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:18px; padding:18px;">
                                <p style="margin:0 0 8px; color:#ffffff; font-size:14px; font-weight:700;">
                                    Did not create this account?
                                </p>
                                <p style="margin:0; color:#94a3b8; font-size:14px; line-height:1.7;">
                                    If this was not you, you can safely ignore this email.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 36px 34px;">
                            <p style="margin:0 0 10px; color:#94a3b8; font-size:13px; line-height:1.7;">
                                If the button does not work, copy and paste this link into your browser:
                            </p>
                            <p style="margin:0; word-break:break-all; color:#7dd3fc; font-size:13px; line-height:1.7;">
                                {{ $verificationUrl }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:18px 36px; background:rgba(255,255,255,0.03); text-align:center;">
                            <p style="margin:0; color:#64748b; font-size:12px;">
                                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>