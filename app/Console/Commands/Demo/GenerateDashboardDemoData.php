<?php

namespace App\Console\Commands\Demo;

use App\Services\Demo\DashboardDemoDataGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateDashboardDemoData extends Command
{
    protected $signature = 'demo:generate-dashboard-data
                            {--days=35 : Number of days to generate ending at --date}
                            {--date= : End date in Y-m-d format (default: today in market timezone)}
                            {--cleanup : Delete older demo rows in selected range before inserting}';

    protected $description = 'Generate consistent demo data for dashboard, selling, and ads overview tables.';

    public function handle(DashboardDemoDataGenerator $generator): int
    {
        $marketTz = config('timezone.market', 'America/Los_Angeles');

        $endDate = $this->option('date')
            ? Carbon::parse($this->option('date'), $marketTz)->startOfDay()
            : Carbon::now($marketTz)->startOfDay();

        $days = max(1, (int) $this->option('days'));
        $startDate = $endDate->copy()->subDays($days - 1);
        $cleanup = (bool) $this->option('cleanup');

        $this->info(sprintf(
            'Generating demo data from %s to %s (days=%d, cleanup=%s)',
            $startDate->toDateString(),
            $endDate->toDateString(),
            $days,
            $cleanup ? 'yes' : 'no'
        ));

        $result = $generator->generate($startDate, $endDate, $cleanup);

        $this->newLine();
        $this->info('Demo data generation completed.');
        $this->line('Products:  ' . $result['products']);
        $this->line('Campaigns: ' . $result['campaigns']);
        $this->line('Keywords:  ' . $result['keywords']);
        $this->line('Range:     ' . $result['from'] . ' -> ' . $result['to']);

        return self::SUCCESS;
    }
}
