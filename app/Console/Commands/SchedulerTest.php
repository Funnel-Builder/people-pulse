<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SchedulerTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduler:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'A test command for the scheduler';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Scheduler is working!');
        $this->info('Scheduler test command executed successfully!');
    }
}
