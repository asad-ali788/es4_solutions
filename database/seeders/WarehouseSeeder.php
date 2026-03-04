<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str; // Import Str for UUID generation
use App\Models\Warehouse; // Assuming your Warehouse model is in App\Models
use Faker\Factory as Faker; // Import Faker

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker      = Faker::create();
        $warehouses = [];
        $countries  = config('countries');

        for ($i = 0; $i < 15; $i++) {
            $warehouses[] = [
                'uuid'           => (string) Str::uuid(),               
                'warehouse_name' => $faker->company . ' Warehouse',
                'location'       => $faker->randomElement($countries),
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }

        // Use insert for bulk insertion
        Warehouse::insert($warehouses);
    }
}
