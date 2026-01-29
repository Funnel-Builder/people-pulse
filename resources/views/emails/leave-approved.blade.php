<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Approved</title>
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
                            style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 32px 40px; text-align: center;">
                            <img src="{{ asset('logos/logo-icon-peoplepulse-dark-mode.png') }}" alt="PeoplePulse"
                                style="height: 40px; margin-bottom: 16px;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 600;">Leave Approved ✓
                            </h1>
                            <p style="color: rgba(255, 255, 255, 0.85); margin: 8px 0 0 0; font-size: 14px;">
                                Your leave request has been approved
                            </p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 32px 40px;">
                            <p style="color: #334155; margin: 0 0 24px 0; font-size: 16px; line-height: 1.6;">
                                Hi <strong>{{ $leave->user->name }}</strong>,
                            </p>

                            <p style="color: #64748b; margin: 0 0 24px 0; font-size: 15px; line-height: 1.6;">
                                Great news! Your <strong>{{ $leave->leaveType->name }}</strong> leave request has been
                                approved.
                            </p>

                            <!-- Leave Details Card -->
                            <div
                                style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
                                <h3 style="color: #166534; margin: 0 0 16px 0; font-size: 14px; font-weight: 600;">
                                    LEAVE DETAILS</h3>
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td style="color: #64748b; font-size: 13px; padding: 4px 0;">Type:</td>
                                        <td style="color: #1e293b; font-size: 13px; padding: 4px 0; text-align: right;">
                                            <strong>{{ $leave->leaveType->name }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748b; font-size: 13px; padding: 4px 0;">Duration:</td>
                                        <td style="color: #1e293b; font-size: 13px; padding: 4px 0; text-align: right;">
                                            <strong>{{ $leave->dates->count() }}
                                                {{ $leave->dates->count() === 1 ? 'day' : 'days' }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748b; font-size: 13px; padding: 4px 0;">Date(s):</td>
                                        <td style="color: #1e293b; font-size: 13px; padding: 4px 0; text-align: right;">
                                            @php
                                                $dates = $leave->dates->pluck('date')->sort();
                                                $startDate = $dates->first();
                                                $endDate = $dates->last();
                                            @endphp
                                            @if ($startDate === $endDate)
                                                <strong>{{ \Illuminate\Support\Carbon::parse($startDate)->format('M j, Y') }}</strong>
                                            @else
                                                <strong>{{ \Illuminate\Support\Carbon::parse($startDate)->format('M j') }}
                                                    -
                                                    {{ \Illuminate\Support\Carbon::parse($endDate)->format('M j, Y') }}</strong>
                                            @endif
                                        </td>
                                    </tr>
                                    @if ($leave->reason)
                                        <tr>
                                            <td style="color: #64748b; font-size: 13px; padding: 4px 0;">Reason:</td>
                                            <td
                                                style="color: #1e293b; font-size: 13px; padding: 4px 0; text-align: right;">
                                                {{ $leave->reason }}
                                            </td>
                                        </tr>
                                    @endif
                                </table>
                            </div>

                            <!-- Leave Balance Summary -->
                            @if ($leaveBalances->isNotEmpty())
                                <div
                                    style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
                                    <h3
                                        style="color: #475569; margin: 0 0 16px 0; font-size: 14px; font-weight: 600;">
                                        YOUR LEAVE BALANCE</h3>
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                        @foreach ($leaveBalances as $balance)
                                            <tr>
                                                <td
                                                    style="color: #64748b; font-size: 13px; padding: 6px 0; border-bottom: 1px solid #e2e8f0;">
                                                    {{ $balance->leaveType->name ?? 'Leave' }}
                                                </td>
                                                <td
                                                    style="color: #1e293b; font-size: 13px; padding: 6px 0; text-align: right; border-bottom: 1px solid #e2e8f0;">
                                                    <strong
                                                        style="color: {{ $balance->available > 0 ? '#059669' : '#dc2626' }};">
                                                        {{ number_format($balance->available, 1) }}
                                                    </strong>
                                                    <span style="color: #94a3b8;">/ {{ number_format($balance->balance, 1) }} days</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            @endif

                            <!-- Dashboard Button -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <a href="{{ url('/leave') }}"
                                            style="display: inline-block; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 15px; font-weight: 600;">
                                            View Leave History
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
                                This is an automated notification from <strong
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
