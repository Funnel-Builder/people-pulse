<?php

namespace App\Console\Commands;

use App\Mail\ClockInReminder;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveDate;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyMissedClockIn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:notify-missed-clockin
                            {--date= : Optional date to check (defaults to today)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email reminder to employees who have not clocked in yet';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->format('Y-m-d')
            : now()->format('Y-m-d');

        $dayName = Carbon::parse($date)->format('l'); // e.g., "Friday", "Saturday"

        $this->info("Checking for missed clock-ins on {$date} ({$dayName})...");
        Log::info("[Clock-In Reminder] Starting check for {$date} ({$dayName})");

        // Get all active employees (exclude admins and separated employees)
        $employees = User::where('role', '!=', 'admin')
            ->whereNotNull('joining_date')
            ->where(function ($query) use ($date) {
                // Only employees who joined before or on this date
                $query->where('joining_date', '<=', $date);
            })
            // Exclude separated employees (is_active = false)
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhereNull('is_active'); // Treat null as active for backward compatibility
            })
            ->get();

        if ($employees->isEmpty()) {
            $this->warn('No employees found.');
            Log::warning("[Clock-In Reminder] No employees found for {$date}");
            return self::SUCCESS;
        }

        // Check if today is a holiday - skip entire process like weekend
        if ($this->isHoliday($date)) {
            $this->info("Skipping: {$date} is a holiday.");
            Log::info("[Clock-In Reminder] Skipping {$date} - holiday");
            return self::SUCCESS;
        }

        $notifiedCount = 0;
        $skippedWeekend = 0;
        $skippedLeave = 0;
        $alreadyClockedIn = 0;

        $notifiedList = [];

        foreach ($employees as $employee) {
            // 1. Check if it's a weekend for this employee
            if ($employee->isWeekend($dayName)) {
                $skippedWeekend++;
                continue;
            }

            // 2. Check if employee has approved leave for this date
            if ($this->hasApprovedLeave($employee->id, $date)) {
                $skippedLeave++;
                continue;
            }

            // 3. Check if employee already clocked in
            $existingRecord = Attendance::where('user_id', $employee->id)
                ->whereDate('date', $date)
                ->whereNotNull('clock_in')
                ->first();

            if ($existingRecord) {
                $alreadyClockedIn++;
                continue;
            }

            // 4. Send reminder email
            try {
                Mail::to($employee->email)->send(new ClockInReminder($employee, $date));

                $this->line("  Reminder sent: {$employee->name} ({$employee->email})");
                $notifiedList[] = "{$employee->name} ({$employee->employee_id})";
                $notifiedCount++;
            } catch (\Exception $e) {
                $this->error("  Failed to send to {$employee->name}: " . $e->getMessage());
                Log::error("[Clock-In Reminder] Failed to send email", [
                    'employee' => $employee->name,
                    'email' => $employee->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Completed:");
        $this->info("  - Reminders sent: {$notifiedCount}");
        $this->info("  - Already clocked in: {$alreadyClockedIn}");
        $this->info("  - Skipped (weekend): {$skippedWeekend}");
        $this->info("  - Skipped (on leave): {$skippedLeave}");

        // Log comprehensive summary
        Log::info("[Clock-In Reminder] Completed for {$date}", [
            'date' => $date,
            'summary' => [
                'total_employees' => $employees->count(),
                'reminders_sent' => $notifiedCount,
                'already_clocked_in' => $alreadyClockedIn,
                'on_leave' => $skippedLeave,
                'weekend' => $skippedWeekend,
            ],
            'notified_employees' => $notifiedList,
        ]);

        return self::SUCCESS;
    }

    /**
     * Check if the given date is a holiday.
     */
    protected function isHoliday(string $date): bool
    {
        return Holiday::whereDate('date', $date)->exists();
    }

    /**
     * Check if employee has an approved leave for the given date.
     */
    protected function hasApprovedLeave(int $userId, string $date): bool
    {
        return LeaveDate::where('date', $date)
            ->whereHas('leave', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where('status', Leave::STATUS_APPROVED);
            })
            ->exists();
    }
}
