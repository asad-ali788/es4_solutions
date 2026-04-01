<?php

namespace Database\Seeders;

use App\Services\Demo\DashboardDemoDataGenerator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DashboardDemoSeeder extends Seeder
{
    public function run(): void
    {
        $marketTz = config('timezone.market', 'America/Los_Angeles');
        $endDate = Carbon::now($marketTz)->startOfDay();
        $startDate = $endDate->copy()->subDays(34);

        app(DashboardDemoDataGenerator::class)->generate($startDate, $endDate, true);
    }
}
