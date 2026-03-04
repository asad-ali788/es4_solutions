<?php

namespace Database\Seeders;

use App\Enum\Permissions\AmzAdsEnum;
use App\Enum\Permissions\CurrencyEnum;
use App\Enum\Permissions\DashboardEnum;
use App\Enum\Permissions\DataEnum;
use App\Enum\Permissions\DeveloperEnum;
use App\Enum\Permissions\NotificationEnum;
use App\Enum\Permissions\ProductEnum;
use App\Enum\Permissions\UserEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Enum\Permissions\PurchaseOrderEnum;
use App\Enum\Permissions\SourcingEnum;
use App\Enum\Permissions\ShipmentEnum;
use App\Enum\Permissions\WarehouseEnum;
use App\Enum\Permissions\OrderForecastEnum;
use App\Enum\Permissions\SellingEnum;
use App\Enum\Permissions\StocksEnum;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        // Give all permission enum for insert to DB
        $permissions = array_merge(
            AmzAdsEnum::cases(),
            CurrencyEnum::cases(),
            DashboardEnum::cases(),
            DataEnum::cases(),
            DeveloperEnum::cases(),
            NotificationEnum::cases(),
            OrderForecastEnum::cases(),
            ProductEnum::cases(),
            PurchaseOrderEnum::cases(),
            SellingEnum::cases(),
            ShipmentEnum::cases(),
            SourcingEnum::cases(),
            StocksEnum::cases(),
            UserEnum::cases(),
            WarehouseEnum::cases(),
        );

        foreach ($permissions as $enum) {
            Permission::firstOrCreate(
                [
                    'name'       => $enum->value,
                    'guard_name' => 'web',
                ],
                [
                    'label'      => $enum->label(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
