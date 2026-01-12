<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\UserLeaveBalance;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CalculateLeaveAccrual extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leave:calculate-accrual 
                            {--user= : Optional user ID to calculate for a specific user}
                            {--year= : Optional year to calculate for (defaults to current year)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate attendance-based leave accrual for all employees';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $year = $this->option('year') ?? now()->year;
        $userId = $this->option('user');

        $this->info("Calculating leave accrual for year {$year}...");

        // Get all attendance-based leave balances
        $query = UserLeaveBalance::where('accrual_type', 'attendance')
            ->whereNotNull('attendance_days_threshold')
            ->where('attendance_days_threshold', '>', 0);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $balances = $query->with(['user', 'leaveType'])->get();

        if ($balances->isEmpty()) {
            $this->warn('No attendance-based leave balances found.');
            return self::SUCCESS;
        }

        $processed = 0;
        $errors = 0;

        foreach ($balances as $balance) {
            try {
                $earnedLeave = $this->calculateEarnedLeave($balance, $year);
                
                // Update balance (earned leave minus any used leave)
                $previousBalance = $balance->balance;
                $balance->balance = $earnedLeave;
                $balance->last_accrual_date = now();
                $balance->save();

                $diff = $earnedLeave - $previousBalance;
                $userName = $balance->user->name ?? 'Unknown';
                $leaveTypeName = $balance->leaveType->name ?? 'Unknown';

                if ($diff != 0) {
                    $this->line("  {$userName}: {$leaveTypeName} = {$earnedLeave} days (changed by {$diff})");
                }

                $processed++;
            } catch (\Exception $e) {
                $this->error("Error processing balance ID {$balance->id}: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->info("Processed {$processed} leave balances.");
        
        if ($errors > 0) {
            $this->warn("{$errors} errors occurred during processing.");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Calculate earned leave days based on attendance.
     */
    protected function calculateEarnedLeave(UserLeaveBalance $balance, int $year): float
    {
        $user = $balance->user;
        
        // Determine start date: beginning of year or joining date (whichever is later)
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $joiningDate = $user->joining_date ? Carbon::parse($user->joining_date)->startOfDay() : null;
        
        $startDate = ($joiningDate && $joiningDate->gt($yearStart)) ? $joiningDate : $yearStart;
        
        // End date: today or end of year (whichever is earlier)
        $yearEnd = Carbon::create($year, 12, 31)->endOfDay();
        $today = now()->endOfDay();
        $endDate = $today->lt($yearEnd) ? $today : $yearEnd;

        // Count present days (excluding only 'absent' status)
        $presentDays = Attendance::where('user_id', $balance->user_id)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->where('status', '!=', 'absent')
            ->count();

        // Calculate earned leave: floor(present_days / threshold)
        $earnedLeave = floor($presentDays / $balance->attendance_days_threshold);

        $this->line("  User {$user->name}: {$presentDays} present days / {$balance->attendance_days_threshold} threshold = {$earnedLeave} earned");

        return (float) $earnedLeave;
    }
}
