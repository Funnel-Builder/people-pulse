<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clock-Out Reminder</title>
</head>

<body
    style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f5f9;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
        style="background-color: #f1f5f9; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0"
                    style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <tr>
                        <td
                            style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); padding: 32px 40px; text-align: center;">
                            <img src="{{ url('logos/logo-icon-peoplepulse-dark-mode.png') }}" alt="PeoplePulse"
                                style="height: 40px; margin-bottom: 16px;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 600;">Clock-Out Reminder
                            </h1>
                            <p style="color: rgba(255, 255, 255, 0.85); margin: 8px 0 0 0; font-size: 14px;">
                                {{ \Illuminate\Support\Carbon::parse($date)->format('F j, Y') }}
                            </p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 32px 40px;">
                            <p style="color: #334155; margin: 0 0 24px 0; font-size: 16px; line-height: 1.6;">
                                Hi <strong>{{ $employee->name }}</strong>,
                            </p>

                            <div
                                style="background-color: #f5f3ff; border: 1px solid #c4b5fd; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
                                <p style="color: #5b21b6; margin: 0; font-size: 15px; line-height: 1.6;">
                                    🕐 <strong>Friendly reminder:</strong> You clocked in at
                                    <strong>{{ $clockInTime }}</strong> but haven't clocked out yet.
                                    Don't forget to log your clock-out before leaving!
                                </p>
                            </div>

                            <p style="color: #64748b; margin: 0 0 24px 0; font-size: 14px; line-height: 1.6;">
                                If you've already clocked out, please disregard this message. If you're experiencing any
                                issues with the attendance system, please contact your administrator.
                            </p>

                            <!-- Dashboard Button -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <a href="{{ url('/dashboard') }}"
                                            style="display: inline-block; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 15px; font-weight: 600;">
                                            Go to Dashboard
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 24px 40px; border-top: 1px solid #e2e8f0;">
                            <p style="color: #94a3b8; margin: 0; font-size: 12px; text-align: center;">
                                This is an automated reminder from <strong
                                    style="color: #64748b;">PeoplePulse</strong>.<br>
                                Sent on {{ now()->format('F j, Y \a\t g:i A') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>