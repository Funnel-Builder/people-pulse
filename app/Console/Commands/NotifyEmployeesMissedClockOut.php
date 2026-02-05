<?php

namespace App\Console\Commands;

use App\Mail\ClockOutReminder;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyEmployeesMissedClockOut extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:remind-clockout
                            {--date= : Optional date to check (defaults to today)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email reminder to employees who clocked in but have not clocked out yet';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->format('Y-m-d')
            : now()->format('Y-m-d');

        $this->info("Checking for missed clock-outs on {$date}...");
        Log::info("[Clock-Out Reminder] Starting check for {$date}");

        // Check if today is a holiday - skip entire process
        if ($this->isHoliday($date)) {
            $this->info("Skipping: {$date} is a holiday.");
            Log::info("[Clock-Out Reminder] Skipping {$date} - holiday");
            return self::SUCCESS;
        }

        // Find employees who clocked in but didn't clock out
        $missedClockOuts = Attendance::with(['user', 'user.department'])
            ->whereDate('date', $date)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->whereHas('user', function ($query) {
                $query->where('role', '!=', 'admin')
                    // Exclude separated employees
                    ->where(function ($q) {
                        $q->where('is_active', true)
                            ->orWhereNull('is_active');
                    });
            })
            ->get();

        if ($missedClockOuts->isEmpty()) {
            $this->info('No employees with missed clock-out found.');
            Log::info("[Clock-Out Reminder] No missed clock-outs for {$date}");
            return self::SUCCESS;
        }

        $notifiedCount = 0;
        $notifiedList = [];

        foreach ($missedClockOuts as $attendance) {
            $employee = $attendance->user;
            $clockInTime = Carbon::parse($attendance->clock_in)->format('h:i A');

            try {
                Mail::to($employee->email)->send(new ClockOutReminder($employee, $date, $clockInTime));

                $this->line("  Reminder sent: {$employee->name} ({$employee->email}) - Clocked in at {$clockInTime}");
                $notifiedList[] = "{$employee->name} ({$employee->employee_id})";
                $notifiedCount++;
            } catch (\Exception $e) {
                $this->error("  Failed to send to {$employee->name}: " . $e->getMessage());
                Log::error("[Clock-Out Reminder] Failed to send email", [
                    'employee' => $employee->name,
                    'email' => $employee->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Completed:");
        $this->info("  - Reminders sent: {$notifiedCount}");

        // Log comprehensive summary
        Log::info("[Clock-Out Reminder] Completed for {$date}", [
            'date' => $date,
            'summary' => [
                'total_missed' => $missedClockOuts->count(),
                'reminders_sent' => $notifiedCount,
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
}
