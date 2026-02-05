<?php

namespace App\Console\Commands;

use App\Mail\MissedClockOutReport;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyMissedClockOut extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:notify-missed-clockout
                            {--date= : Optional date to check (defaults to today)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email notification to admins about employees who missed clock-out';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->format('Y-m-d')
            : now()->format('Y-m-d');

        $formattedDate = Carbon::parse($date)->format('F j, Y');

        $this->info("Checking for missed clock-outs on {$formattedDate}...");
        Log::info("[Missed Clock-Out] Starting check for {$date}");

        // Check if today is a holiday - skip entire process
        if ($this->isHoliday($date)) {
            $this->info("Skipping: {$date} is a holiday.");
            Log::info("[Missed Clock-Out] Skipping {$date} - holiday");
            return self::SUCCESS;
        }

        // Find employees who clocked in but didn't clock out
        $missedClockOuts = Attendance::with(['user', 'user.department'])
            ->whereDate('date', $date)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->whereHas('user', function ($query) {
                $query->where('role', '!=', 'admin');
            })
            ->get();

        if ($missedClockOuts->isEmpty()) {
            $this->info('No missed clock-outs found. No email will be sent.');
            Log::info("[Missed Clock-Out] No missed clock-outs for {$date}");
            return self::SUCCESS;
        }

        // Prepare employee data for the email
        $employees = $missedClockOuts->map(function ($attendance) {
            return [
                'name' => $attendance->user->name,
                'employee_id' => $attendance->user->employee_id ?? 'N/A',
                'department' => $attendance->user->department?->name ?? 'N/A',
                'clock_in' => Carbon::parse($attendance->clock_in)->format('h:i A'),
            ];
        });

        $this->info("Found {$employees->count()} employee(s) who missed clock-out:");
        foreach ($employees as $emp) {
            $this->line("  - {$emp['name']} ({$emp['employee_id']}) - Clocked in at {$emp['clock_in']}");
        }

        // Get all admin users
        $admins = User::where('role', 'admin')->get();

        if ($admins->isEmpty()) {
            $this->warn('No admin users found to send the email to.');
            Log::warning("[Missed Clock-Out] No admin users found");
            return self::SUCCESS;
        }

        // Send email to all admins
        $adminEmails = $admins->pluck('email')->toArray();

        try {
            Mail::to($adminEmails)->send(new MissedClockOutReport($employees, $formattedDate));

            $this->info("Email sent successfully to " . implode(', ', $adminEmails));
            Log::info("[Missed Clock-Out] Email sent to admins", [
                'date' => $date,
                'missed_count' => $employees->count(),
                'admins' => $adminEmails,
                'employees' => $employees->pluck('name')->toArray(),
            ]);
        } catch (\Exception $e) {
            $this->error("Failed to send email: " . $e->getMessage());
            Log::error("[Missed Clock-Out] Failed to send email", [
                'error' => $e->getMessage(),
                'date' => $date,
            ]);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Check if the given date is a holiday.
     */
    protected function isHoliday(string $date): bool
    {
        return Holiday::whereDate('date', $date)->exists();
    }
}
