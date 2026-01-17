<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateAcademicSession extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-academic-session';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically update the academic session based on the current year.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentYear = now()->year;
        $nextYear = $currentYear + 1;
        $sessionName = "{$currentYear}-{$nextYear}";

        $this->info("Checking Academic Session: {$sessionName}");

        $startDate = "{$currentYear}-04-01";
        $endDate = "{$nextYear}-03-31";

        // Deactivate all other sessions
        \App\Models\AcademicSession::where('name', '!=', $sessionName)->update(['is_active' => false]);

        $session = \App\Models\AcademicSession::updateOrCreate(
            ['name' => $sessionName],
            [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => true,
            ]
        );

        $this->info("Academic Session '{$sessionName}' is now ACTIVE.");
    }
}
