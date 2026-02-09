<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $employee['name'] }} - Lifetime Report</title>
    <style>
        @page {
            margin: 0.5cm;
            size: A4;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1f2937;
            font-size: 10px;
            line-height: 1.3;
            background-color: #f9fafb;
        }

        .w-full {
            width: 100%;
        }

        .no-break {
            page-break-inside: avoid;
        }

        /* Card Styles */
        .card {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 15px;
            /* Spacing between stacked sections */
        }

        .card-header {
            padding: 8px 12px;
            border-bottom: 1px solid #e5e7eb;
            background-color: #f9fafb;
        }

        .card-content {
            padding: 10px;
        }

        .card-title {
            font-size: 11px;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
        }

        /* 1. Profile Section (Full Width) */
        .profile-table {
            width: 100%;
            border-collapse: collapse;
        }

        .profile-td-avatar {
            width: 80px;
            text-align: center;
            vertical-align: top;
            padding-right: 15px;
        }

        .profile-td-info {
            vertical-align: top;
        }

        .avatar-img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #e5e7eb;
        }

        .avatar-ph {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background-color: #eff6ff;
            color: #2563eb;
            font-size: 24px;
            font-weight: bold;
            line-height: 70px;
            text-align: center;
            display: inline-block;
            border: 1px solid #e5e7eb;
        }

        .profile-name {
            font-size: 18px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 2px;
        }

        .profile-role {
            font-size: 11px;
            color: #6b7280;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .grid-info {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .grid-info td {
            vertical-align: top;
            width: 33%;
        }

        .info-label {
            font-size: 9px;
            color: #6b7280;
            display: block;
        }

        .info-val {
            font-size: 11px;
            font-weight: bold;
            color: #111827;
        }

        /* 2. Stats Grid (4 in a row) */
        .stats-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5px 0;
            /* Gutter */
            margin: 0 -5px 15px -5px;
            table-layout: fixed;
        }

        .stat-box {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
            position: relative;
            height: 50px;
        }

        .stat-val {
            font-size: 16px;
            font-weight: bold;
            display: block;
            margin-bottom: 2px;
        }

        .stat-lbl {
            font-size: 9px;
            text-transform: uppercase;
            color: #6b7280;
        }

        .stat-bar {
            height: 3px;
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
        }

        .bg-p {
            background-color: #10b981;
        }

        /* Present */
        .bg-l {
            background-color: #f59e0b;
        }

        /* Late */
        .bg-a {
            background-color: #ef4444;
        }

        /* Absent */
        .bg-g {
            background-color: #6b7280;
        }

        /* Gray */
        .bg-b {
            background-color: #3b82f6;
        }

        /* Blue */

        /* 3. Charts (Full Width) */
        .chart-container {
            border: 1px solid #e5e7eb;
            background-color: #fff;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        .chart-title {
            font-size: 11px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 5px;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 5px;
        }

        .bar-table {
            width: 100%;
            height: 120px;
            /* Increased height for labels below */
            border-collapse: collapse;
            table-layout: fixed;
        }

        .bar-td {
            vertical-align: bottom;
            text-align: center;
            padding: 0 4px;
            height: 120px;
        }

        .bar-wrap {
            height: 80px;
            width: 100%;
            position: relative;
            display: inline-block;
        }

        .bar-div {
            width: 60%;
            margin: 0 auto;
            border-radius: 3px 3px 0 0;
            position: absolute;
            bottom: 0;
            left: 20%;
        }

        .bar-txt {
            font-size: 8px;
            color: #6b7280;
            margin-top: 4px;
            text-align: center;
        }

        .bar-val-bottom {
            font-size: 8px;
            color: #111827;
            margin-top: 2px;
            text-align: center;
            height: 12px;
        }

        /* 4. Lists (Split or Stacked, sticking to split for efficiency on page) */
        .list-t {
            width: 100%;
            border-collapse: collapse;
        }

        .list-r {
            border-bottom: 1px solid #f3f4f6;
        }

        .list-d {
            padding: 6px 0;
            vertical-align: top;
            font-size: 9px;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <div style="margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;">
        <table style="width: 100%;">
            <tr>
                <td>
                    <h1 style="font-size: 20px; font-weight: bold; margin: 0; color: #111827;">Lifetime Report</h1>
                    <p style="margin: 0; color: #6b7280; font-size: 11px;">{{ $employee['name'] }}'s performance
                        history.</p>
                </td>
                <td style="text-align: right; vertical-align: bottom;">
                    <p style="margin: 0; color: #9ca3af; font-size: 10px;">Generated: {{ date('M d, Y') }}</p>
                </td>
            </tr>
        </table>
    </div>

    <!-- 1. Profile Section (Full Width) -->
    <div class="card no-break">
        <div class="card-content">
            <table class="profile-table">
                <tr>
                    <td class="profile-td-avatar">
                        @if($employee['profile_picture'])
                            <img src="{{ public_path('storage/' . $employee['profile_picture']) }}" class="avatar-img">
                        @else
                            <div class="avatar-ph">{{ substr($employee['name'], 0, 1) }}</div>
                        @endif
                    </td>
                    <td class="profile-td-info">
                        <div style="float: right;">
                            <span
                                style="background: {{ $employee['status'] === 'Active' ? '#ecfdf5' : '#f3f4f6' }}; color: {{ $employee['status'] === 'Active' ? '#047857' : '#374151' }}; padding: 3px 8px; border-radius: 99px; font-size: 10px; font-weight: bold; border: 1px solid #e5e7eb;">
                                {{ $employee['status'] === 'Active' ? 'Current' : $employee['status'] }}
                            </span>
                        </div>
                        <div class="profile-name">{{ $employee['name'] }}</div>
                        <div class="profile-role">{{ $employee['designation'] }}</div>

                        <div style="border-top: 1px solid #e5e7eb; margin: 10px 0;"></div>

                        <table class="grid-info">
                            <tr>
                                <td>
                                    <span class="info-label">ID</span>
                                    <span class="info-val">{{ $employee['employee_id'] }}</span>
                                </td>
                                <td>
                                    <span class="info-label">Joined</span>
                                    <span class="info-val">{{ $employee['joining_date'] }}</span>
                                </td>
                                <td>
                                    <span class="info-label">Department</span>
                                    <span class="info-val">{{ $employee['department'] }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" style="height: 8px;"></td>
                            </tr>
                            <tr>
                                <td>
                                    <span class="info-label">Phone</span>
                                    <span class="info-val">{{ $employee['phone'] }}</span>
                                </td>
                                <td colspan="2">
                                    <span class="info-label">Email</span>
                                    <span class="info-val">{{ $employee['email'] }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- 2. Statistics (4 in a row) -->
    <table class="stats-table no-break">
        <tr>
            <td>
                <div class="stat-box">
                    <span class="stat-val">{{ $attendanceStats['total_present'] }}</span>
                    <span class="stat-lbl">Present</span>
                    <div class="stat-bar bg-p"></div>
                </div>
            </td>
            <td>
                <div class="stat-box">
                    <span class="stat-val">{{ $attendanceStats['total_late'] }}</span>
                    <span class="stat-lbl">Late</span>
                    <div class="stat-bar bg-l"></div>
                </div>
            </td>
            <td>
                <div class="stat-box">
                    <span class="stat-val">{{ $attendanceStats['total_absent'] }}</span>
                    <span class="stat-lbl">Absent</span>
                    <div class="stat-bar bg-a"></div>
                </div>
            </td>
            <td>
                <div class="stat-box">
                    <span class="stat-val">{{ $attendanceStats['avg_working_hours'] }}h</span>
                    <span class="stat-lbl">Avg Hours</span>
                    <div class="stat-bar bg-g"></div>
                </div>
            </td>
        </tr>
    </table>

    <!-- 3. Work Hours Chart (Full Width) -->
    <div class="chart-container no-break">
        <div class="chart-title">
            Monthly Work Hours
            <span style="float: right; color: #111827;">
                @php
                    // Logic: Exclude current month (last item) and months with 0 hours
                    // monthlyTrends is an associative array keyed by 'YYYY-MM'. 
                    // array_slice to get all but last? Or just pop?
                    $trendsCopy = $monthlyTrends;
                    array_pop($trendsCopy); // Remove current month

                    $activeMonths = array_filter($trendsCopy, function ($m) {
                        return $m['hours'] > 0;
                    });
                    $count = count($activeMonths);
                    $totalHours = array_sum(array_column($activeMonths, 'hours'));
                    $avgMonthly = $count > 0 ? round($totalHours / $count) : 0;
                    echo $avgMonthly . 'h /mo';
                @endphp
            </span>
        </div>
        <table class="bar-table">
            <tr>
                @foreach($monthlyTrends as $month)
                    <td class="bar-td">
                        <div class="bar-wrap">
                            @php
                                $barColor = $month['hours'] > 0 ? 'bg-b' : 'bg-g';
                                $height = max($month['workHeight'], 2); 
                            @endphp
                            <div class="bar-div {{ $barColor }}" style="height: {{ $height }}%;"></div>
                        </div>
                        <div class="bar-txt">{{ $month['monthLabel'] }}</div>
                        <div class="bar-val-bottom">
                            @if($month['hours'] > 0)
                                {{ $month['hours'] }}h
                            @else
                                <span style="color: #e5e7eb;">-</span>
                            @endif
                        </div>
                    </td>
                @endforeach
            </tr>
        </table>
    </div>

    <!-- 4. Performance Chart (Full Width) -->
    <div class="chart-container no-break">
        <div class="chart-title">
            Performance Score
            <span style="float: right; color: #111827;">
                @php
                    $lastMonth = end($monthlyTrends);
                    echo ($lastMonth ? $lastMonth['score'] : 100) . '%';
                @endphp
            </span>
        </div>
        <table class="bar-table">
            <tr>
                @foreach($monthlyTrends as $month)
                    <td class="bar-td">
                        <div class="bar-wrap">
                            @php
                                $barColor = $month['score'] > 0 ? 'bg-l' : 'bg-g';
                                $height = max($month['punctualityHeight'], 2);
                            @endphp
                            <div class="bar-div {{ $barColor }}" style="height: {{ $height }}%;"></div>
                        </div>
                        <div class="bar-txt">{{ $month['monthLabel'] }}</div>
                        <div class="bar-val-bottom">
                            @if($month['score'] > 0)
                                {{ $month['score'] }}%
                            @else
                                <span style="color: #e5e7eb;">-</span>
                            @endif
                        </div>
                    </td>
                @endforeach
            </tr>
        </table>
    </div>

    <!-- 5. History Lists (Stacked Full Width) -->
    <div class="card no-break">
        <div class="card-header">
            <span class="card-title">Leave Overview</span>
        </div>
        <div class="card-content">
            <table class="list-t">
                <tr class="list-r">
                    <td class="list-d" style="font-weight: bold; width: 70%;">Total Leaves</td>
                    <td class="list-d" style="text-align: right; font-weight: bold;">
                        {{ $leaveStats['total_leaves_taken'] }}
                    </td>
                </tr>
                @forelse($leaveBreakdown as $leave)
                    <tr class="list-r">
                        <td class="list-d">
                            <span
                                style="display:inline-block; width:6px; height:6px; background-color:#a855f7; border-radius:50%; margin-right:4px;"></span>
                            {{ $leave['type'] }}
                        </td>
                        <td class="list-d" style="text-align: right;">{{ $leave['count'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2"
                            style="text-align: center; color: #9ca3af; padding: 10px; font-style: italic; font-size: 9px;">
                            No leaves recorded.</td>
                    </tr>
                @endforelse
            </table>

            <div
                style="margin-top: 10px; font-size: 9px; color: #6b7280; background: #f9fafb; padding: 5px; border-radius: 4px;">
                Pending Requests: <strong>{{ $leaveStats['pending_requests'] }}</strong>
            </div>
        </div>
    </div>

    <div class="card no-break">
        <div class="card-header">
            <span class="card-title">Cover History</span>
        </div>
        <div class="card-content">
            <table class="list-t">
                @forelse($coverHistory['recent'] as $cover)
                    <tr class="list-r">
                        <td class="list-d">
                            <div style="font-weight: 500; color: #111827;">{{ $cover['covered_for'] }}</div>
                            <div style="color: #6b7280; font-size: 8px;">{{ $cover['type'] }}</div>
                        </td>
                        <td class="list-d" style="text-align: right; color: #6b7280;">{{ $cover['date'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2"
                            style="text-align: center; color: #9ca3af; padding: 10px; font-style: italic; font-size: 9px;">
                            No cover history found.</td>
                    </tr>
                @endforelse
            </table>
        </div>
    </div>

</body>

</html>