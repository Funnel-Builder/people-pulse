<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\LeaveDate;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class MarkAbsentEmployees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:mark-absent 
                            {--date= : Optional date to check (defaults to today)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark employees as absent if they have no attendance record for the day';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->option('date') 
            ? Carbon::parse($this->option('date'))->format('Y-m-d')
            : now()->format('Y-m-d');

        $dayName = Carbon::parse($date)->format('l'); // e.g., "Friday", "Saturday"

        $this->info("Checking attendance for {$date} ({$dayName})...");

        // Get all active employees (exclude admins if needed, adjust as necessary)
        $employees = User::where('role', '!=', 'admin')
            ->whereNotNull('joining_date')
            ->where(function ($query) use ($date) {
                // Only employees who joined before or on this date
                $query->where('joining_date', '<=', $date);
            })
            ->get();

        if ($employees->isEmpty()) {
            $this->warn('No employees found.');
            return self::SUCCESS;
        }

        $marked = 0;
        $skippedExisting = 0;
        $skippedWeekend = 0;
        $skippedLeave = 0;

        foreach ($employees as $employee) {
            // 1. Check if it's a weekend for this employee
            if ($employee->isWeekend($dayName)) {
                $skippedWeekend++;
                continue;
            }

            // 2. Check if employee has approved leave for this date
            if ($this->hasApprovedLeave($employee->id, $date)) {
                $this->line("  Skipped (on leave): {$employee->name}");
                $skippedLeave++;
                continue;
            }

            // 3. Check if attendance record already exists
            $existingRecord = Attendance::where('user_id', $employee->id)
                ->whereDate('date', $date)
                ->exists();

            if ($existingRecord) {
                $skippedExisting++;
                continue;
            }

            // Create absence record
            Attendance::create([
                'user_id' => $employee->id,
                'date' => $date,
                'status' => 'absent',
                'clock_in' => null,
                'clock_out' => null,
                'gross_minutes' => 0,
                'break_minutes' => 0,
                'net_minutes' => 0,
                'is_late' => false,
                'late_minutes' => 0,
                'early_exit_minutes' => 0,
            ]);

            $this->line("  Marked absent: {$employee->name} ({$employee->employee_id})");
            $marked++;
        }

        $this->info("Completed:");
        $this->info("  - Marked absent: {$marked}");
        $this->info("  - Skipped (already recorded): {$skippedExisting}");
        $this->info("  - Skipped (weekend): {$skippedWeekend}");
        $this->info("  - Skipped (on leave): {$skippedLeave}");

        return self::SUCCESS;
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
