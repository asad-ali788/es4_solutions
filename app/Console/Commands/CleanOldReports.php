<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanOldReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-old-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes report files older than 14 days';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $path = storage_path('app/public/api/reports');
        $files = \File::files($path);

        $deleted = 0;
        foreach ($files as $file) {
            // Delete files older than 14 days
            if ($file->getMTime() < now()->subDays(12)->getTimestamp()) {
                \File::delete($file->getRealPath());
                $deleted++;
            }
        }

        $this->info("✅ Deleted {$deleted} old report files.");
    }
}
