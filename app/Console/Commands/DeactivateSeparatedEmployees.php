<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class DeactivateSeparatedEmployees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'employees:deactivate-separated 
                            {--dry-run : Show what would be deactivated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate employees whose closing date has passed';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = Carbon::today()->format('Y-m-d');
        $isDryRun = $this->option('dry-run');

        $this->info("Checking for employees to deactivate (closing_date < {$today})...");

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Find all active users whose closing date has passed
        $employeesToDeactivate = User::where('is_active', true)
            ->whereNotNull('closing_date')
            ->where('closing_date', '<', $today)
            ->get();

        if ($employeesToDeactivate->isEmpty()) {
            $this->info('No employees to deactivate.');
            Log::info('[Employee Deactivation] No employees to deactivate today.');
            return self::SUCCESS;
        }

        $this->info("Found {$employeesToDeactivate->count()} employee(s) to deactivate.");

        $deactivatedList = [];

        foreach ($employeesToDeactivate as $employee) {
            $this->line("  - {$employee->name} ({$employee->employee_id}) - Closing date: {$employee->closing_date->format('Y-m-d')}");

            $deactivatedList[] = [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
                'email' => $employee->email,
                'closing_date' => $employee->closing_date->format('Y-m-d'),
            ];

            if (!$isDryRun) {
                $employee->update(['is_active' => false]);
            }
        }

        if ($isDryRun) {
            $this->warn("DRY RUN: Would have deactivated {$employeesToDeactivate->count()} employee(s).");
        } else {
            $this->info("Successfully deactivated {$employeesToDeactivate->count()} employee(s).");
        }

        // Log the deactivation for audit purposes
        Log::info('[Employee Deactivation] Completed', [
            'date' => $today,
            'dry_run' => $isDryRun,
            'count' => $employeesToDeactivate->count(),
            'employees' => $deactivatedList,
        ]);

        return self::SUCCESS;
    }
}
