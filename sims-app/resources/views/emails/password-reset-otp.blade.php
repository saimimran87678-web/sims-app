<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Password Reset Code - {{ $instituteName }}</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td, a { font-family: Arial, Helvetica, sans-serif !important; }
    </style>
    <![endif]-->
    <style type="text/css">
        /* Client-specific Resets */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; height: 100% !important; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        
        /* Mobile Responsive */
        @media screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                margin: auto !important;
            }
            .content-padding {
                padding: 24px 20px !important;
            }
            .otp-box {
                font-size: 32px !important;
                letter-spacing: 6px !important;
                padding: 14px 16px !important;
            }
            .institute-header-title {
                font-size: 18px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; -webkit-font-smoothing: antialiased;">
    <!-- Hidden Preheader Preview Text -->
    <div style="display: none; font-size: 1px; color: #f1f5f9; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all;">
        Your password reset code for {{ $instituteName }} is {{ $otp }}. Valid for {{ $validMinutes ?? 15 }} minutes.
    </div>

    <!-- Outer Wrapper -->
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9; table-layout: fixed;">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <!-- Main Container Card -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="email-container" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);">
                    
                    <!-- Decorative Top Accent Bar -->
                    <tr>
                        <td height="6" style="background: linear-gradient(90deg, #1e3a5f 0%, #2563eb 50%, #0d9488 100%); font-size: 0; line-height: 0;">&nbsp;</td>
                    </tr>

                    <!-- Header with Institute Branding -->
                    <tr>
                        <td align="center" style="padding: 32px 32px 20px 32px; background-color: #ffffff; border-bottom: 1px solid #f1f5f9;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center">
                                        @if(!empty($logoUrl))
                                            <div style="margin-bottom: 16px;">
                                                <img src="{{ $logoUrl }}" alt="{{ $instituteName }}" width="64" height="64" style="width: 64px; height: 64px; object-fit: contain; border-radius: 12px; display: block; margin: 0 auto; border: 1px solid #e2e8f0; padding: 4px; background: #ffffff;">
                                            </div>
                                        @else
                                            <!-- Elegant Emblem Icon -->
                                            <div style="margin-bottom: 16px; width: 56px; height: 56px; line-height: 56px; border-radius: 14px; background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%); color: #ffffff; font-size: 24px; font-weight: 800; display: inline-block; text-align: center; box-shadow: 0 4px 10px rgba(30, 58, 95, 0.2);">
                                                {{ strtoupper(substr($instituteShortName ?: $instituteName, 0, 1)) }}
                                            </div>
                                        @endif

                                        <!-- Institute Formal / Full Name -->
                                        <h1 class="institute-header-title" style="margin: 0; font-size: 20px; font-weight: 800; color: #0f172a; line-height: 1.35; letter-spacing: -0.01em;">
                                            {{ $instituteName }}
                                        </h1>

                                        <!-- Sub-badge -->
                                        <div style="margin-top: 8px;">
                                            <span style="display: inline-block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #2563eb; background-color: #eff6ff; border: 1px solid #dbeafe; padding: 4px 12px; border-radius: 9999px;">
                                                Account Security &bull; Password Reset
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Body Content -->
                    <tr>
                        <td class="content-padding" style="padding: 36px 36px 28px 36px; background-color: #ffffff;">
                            
                            <!-- Greeting -->
                            <p style="margin: 0 0 16px 0; font-size: 16px; font-weight: 600; color: #1e293b;">
                                @if(!empty($userName))
                                    Hello {{ $userName }},
                                @else
                                    Hello,
                                @endif
                            </p>

                            <!-- Intro Message -->
                            <p style="margin: 0 0 24px 0; font-size: 15px; color: #475569; line-height: 1.6;">
                                We received a request to reset the password for your account associated with <strong>{{ $instituteName }}</strong>. Use the following 6-digit One-Time Password (OTP) code to proceed:
                            </p>

                            <!-- OTP Box Section -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 28px 0;">
                                <tr>
                                    <td align="center">
                                        <div style="background-color: #f8fafc; border: 2px dashed #93c5fd; border-radius: 12px; padding: 20px 24px; text-align: center; display: inline-block; min-width: 260px;">
                                            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #64748b; margin-bottom: 8px;">
                                                One-Time Password (OTP)
                                            </div>
                                            <div class="otp-box" style="font-family: 'SF Mono', Consolas, Monaco, 'Courier New', monospace; font-size: 38px; font-weight: 800; letter-spacing: 10px; color: #1e3a5f; margin: 0; line-height: 1.1; user-select: all;">
                                                {{ $otp }}
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Expiry Timer Pill -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 28px;">
                                <tr>
                                    <td align="center">
                                        <table border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="background-color: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 8px 16px; text-align: center;">
                                                    <span style="font-size: 13px; font-weight: 600; color: #b45309;">
                                                        &#9201;&nbsp; This code is single-use and expires in <strong>{{ $validMinutes ?? 15 }} minutes</strong>.
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Security Warning Card -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 10px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #3b82f6; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 14px 16px;">
                                        <p style="margin: 0 0 6px 0; font-size: 13px; font-weight: 700; color: #1e293b;">
                                            &#128274; Security Notice:
                                        </p>
                                        <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.55;">
                                            Never share this verification code with anyone. Official staff or system administrators will never ask for your code. If you did not initiate this request, you can safely disregard this email; your existing password will remain unchanged.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer & Branding Section -->
                    <tr>
                        <td align="center" style="padding: 28px 32px; background-color: #f8fafc; border-top: 1px solid #e2e8f0;">
                            
                            <!-- Powered by Signature -->
                            <p style="margin: 0 0 6px 0; font-size: 13px; font-weight: 600; color: #334155;">
                                Powered by <span style="color: #1e3a5f; font-weight: 800;">Adminova</span> Information Management System
                            </p>

                            <!-- Formal Institute Name in Footer -->
                            <p style="margin: 0 0 12px 0; font-size: 12px; color: #64748b;">
                                {{ $instituteName }}
                            </p>

                            <!-- Copyright & Disclaimer -->
                            <p style="margin: 0 0 6px 0; font-size: 11px; color: #94a3b8; line-height: 1.4;">
                                &copy; {{ date('Y') }} Adminova. All rights reserved.
                            </p>
                            <p style="margin: 0; font-size: 11px; color: #94a3b8; line-height: 1.4;">
                                This is an automated security transmission. Replies to this email are not monitored.
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- /Main Container Card -->
            </td>
        </tr>
    </table>
</body>
</html>
