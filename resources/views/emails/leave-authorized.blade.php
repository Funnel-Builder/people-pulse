<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Authorized</title>
</head>

<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f5f9; padding: 20px;">
    <div
        style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <p style="color: #334155; font-size: 16px;">
            Hi <strong>{{ $leave->user->name }}</strong>,
        </p>
        <p style="color: #64748b; font-size: 15px; line-height: 1.6;">
            Your leave application for
            <strong>
                @php
                    $dates = $leave->dates->pluck('date')->sort();
                    $startDate = $dates->first();
                    $endDate = $dates->last();
                @endphp
                @if ($startDate === $endDate)
                    {{ \Illuminate\Support\Carbon::parse($startDate)->format('M j, Y') }}
                @else
                    {{ \Illuminate\Support\Carbon::parse($startDate)->format('M j') }} -
                    {{ \Illuminate\Support\Carbon::parse($endDate)->format('M j, Y') }}
                @endif
            </strong>
            has been authorized by <strong>{{ $approver->name }}</strong>.
        </p>
        <p style="color: #94a3b8; font-size: 12px; margin-top: 20px;">
            This is an automated notification from PeoplePulse.
        </p>
    </div>
</body>

</html>