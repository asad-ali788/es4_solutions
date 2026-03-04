<?php

namespace Database\Seeders;

use App\Models\ProductListing;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            DefaultUserSeeder::class,
            // ProductSeeder::class,
            // SourcingDatabaseSeeder::class,
            // ProductListingSeeder::class,
            // WarehouseSeeder::class,
            // ShipmentSeeder::class,
            // PurchaseOrderSeeder::class,
            AuthorizeSeeder::class,
            // NotificationSeeder::class,
            // PriceUpdateReasonSeeder::class,
            // CurrencySeeder::class,
            // OrderForecastSeeder::class,
            // OrderForecastSnapshotSeeder::class,
        ]);
    }
}
