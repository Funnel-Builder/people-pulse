<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Missed Clock-Out Report</title>
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
                            style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); padding: 32px 40px; text-align: center;">
                            <img src="{{ asset('logos/logo-icon-peoplepulse-dark-mode.png') }}" alt="PeoplePulse"
                                style="height: 40px; margin-bottom: 16px;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 600;">Missed Clock-Out
                                Report</h1>
                            <p style="color: rgba(255, 255, 255, 0.85); margin: 8px 0 0 0; font-size: 14px;">{{ $date }}
                            </p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 32px 40px;">
                            @if($employees->isEmpty())
                                <div style="text-align: center; padding: 40px 20px;">
                                    <div
                                        style="width: 64px; height: 64px; background-color: #dcfce7; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                                        <span style="font-size: 32px;">✓</span>
                                    </div>
                                    <h2 style="color: #166534; margin: 0 0 8px 0; font-size: 20px;">All Clear!</h2>
                                    <p style="color: #64748b; margin: 0; font-size: 14px;">All employees have clocked out
                                        properly today.</p>
                                </div>
                            @else
                                <p style="color: #334155; margin: 0 0 24px 0; font-size: 15px; line-height: 1.6;">
                                    The following <strong style="color: #dc2626;">{{ $employees->count() }}
                                        employee(s)</strong> clocked in but did not clock out today:
                                </p>

                                <!-- Employee Table -->
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                    style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                                    <thead>
                                        <tr style="background-color: #f8fafc;">
                                            <th
                                                style="padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0;">
                                                Employee</th>
                                            <th
                                                style="padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0;">
                                                ID</th>
                                            <th
                                                style="padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0;">
                                                Department</th>
                                            <th
                                                style="padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0;">
                                                Clock In</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($employees as $index => $employee)
                                            <tr style="background-color: {{ $index % 2 === 0 ? '#ffffff' : '#f8fafc' }};">
                                                <td
                                                    style="padding: 14px 16px; font-size: 14px; color: #1e293b; border-bottom: 1px solid #e2e8f0;">
                                                    <strong>{{ $employee['name'] }}</strong>
                                                </td>
                                                <td
                                                    style="padding: 14px 16px; font-size: 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">
                                                    {{ $employee['employee_id'] }}
                                                </td>
                                                <td
                                                    style="padding: 14px 16px; font-size: 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">
                                                    {{ $employee['department'] ?? 'N/A' }}
                                                </td>
                                                <td
                                                    style="padding: 14px 16px; font-size: 14px; color: #1e293b; border-bottom: 1px solid #e2e8f0;">
                                                    <span
                                                        style="background-color: #f1f5f9; color: #334155; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; white-space: nowrap;">
                                                        {{ $employee['clock_in'] }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                <p style="color: #64748b; margin: 24px 0 0 0; font-size: 13px; line-height: 1.6;">
                                    💡 <em>Please follow up with these employees to ensure attendance records are
                                        accurate.</em>
                                </p>
                            @endif
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 24px 40px; border-top: 1px solid #e2e8f0;">
                            <p style="color: #94a3b8; margin: 0; font-size: 12px; text-align: center;">
                                This is an automated message from <strong
                                    style="color: #64748b;">PeoplePulse</strong>.<br>
                                Generated on {{ now()->format('F j, Y \a\t g:i A') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>