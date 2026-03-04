<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrderForecast;

class OrderForecastSeeder extends Seeder
{
    public function run(): void
    {
        $forecast = OrderForecast::create([
            'order_name' => 'Spring 2025 Forecast test',
            'order_date' => now()->toDateString(),
            'status' => 'finalized',
            'notes' => 'Imported from planning tool',
        ]);

        // store ID for use in snapshot seeder
        file_put_contents(
            storage_path('app/forecast_id.txt'),
            $forecast->id
        );
    }
}
