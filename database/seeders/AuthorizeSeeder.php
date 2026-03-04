<?php

namespace Database\Seeders;

use App\Enum\Permissions\AmzAdsEnum;
use App\Enum\Permissions\CurrencyEnum;
use App\Enum\Permissions\DashboardEnum;
use App\Enum\Permissions\DataEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Enum\Permissions\DefaultRoleEnum;
use App\Enum\Permissions\DeveloperEnum;
use App\Enum\Permissions\NotificationEnum;
use App\Enum\Permissions\ProductEnum;
use App\Enum\Permissions\PurchaseOrderEnum;
use App\Enum\Permissions\RoleEnum;
use App\Enum\Permissions\ShipmentEnum;
use App\Enum\Permissions\SourcingEnum;
use App\Enum\Permissions\UserEnum;
use App\Enum\Permissions\WarehouseEnum;
use App\Enum\Permissions\OrderForecastEnum;
use App\Enum\Permissions\SellingEnum;
use App\Enum\Permissions\StocksEnum;;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

class AuthorizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /**========================================================================
         *                           ROLE HAS PERMISSION
         *========================================================================**/

        $role = Role::defaultRoleEnv()->where('name', DefaultRoleEnum::Administrator->value)->first(); //use the scopeDefaultRoleEnv

        $role->syncPermissions(Permission::fetchAllStaticPermissionKeysWithoutGroup());

        // User role permissions
        Role::where('name', RoleEnum::User->value)->first()
            ->givePermissionTo(array_merge(
                AmzAdsEnum::all(),
                ProductEnum::all(),
                DashboardEnum::all(),
                SellingEnum::all(),
                PurchaseOrderEnum::all(),
                SourcingEnum::all(),
                ShipmentEnum::all(),
                WarehouseEnum::all(),
                OrderForecastEnum::all(),
                StocksEnum::all(),
                NotificationEnum::all(),
            ));

        // Supplier role permissions
        Role::where('name', RoleEnum::Supplier->value)->first()
            ->givePermissionTo(array_merge(
                AmzAdsEnum::all(),
                ProductEnum::all(),
                DashboardEnum::all(),
                PurchaseOrderEnum::all(),
                SourcingEnum::all(),
                ShipmentEnum::all(),
                WarehouseEnum::all(),
                CurrencyEnum::all(),
                OrderForecastEnum::all(),
                SellingEnum::all(),
                NotificationEnum::all(),
                StocksEnum::all(),
            ));

        // Developer role permissions
        Role::where('name', RoleEnum::Developer->value)->first()
            ->givePermissionTo(array_merge(
                AmzAdsEnum::all(),
                UserEnum::all(),
                DashboardEnum::all(),
                ProductEnum::all(),
                DeveloperEnum::all(),
                DataEnum::all(),
                PurchaseOrderEnum::all(),
                SourcingEnum::all(),
                ShipmentEnum::all(),
                WarehouseEnum::all(),
                CurrencyEnum::all(),
                OrderForecastEnum::all(),
                SellingEnum::all(),
                NotificationEnum::all(),
                StocksEnum::all(),
            ));

        $sharedPermissions = array_merge(
            AmzAdsEnum::all(),
            UserEnum::all(),
            DashboardEnum::all(),
            ProductEnum::all(),
            DataEnum::all(),
            PurchaseOrderEnum::all(),
            SourcingEnum::all(),
            ShipmentEnum::all(),
            WarehouseEnum::all(),
            CurrencyEnum::all(),
            OrderForecastEnum::all(),
            SellingEnum::all(),
            NotificationEnum::all(),
            StocksEnum::all(),
        );

        foreach ([RoleEnum::Manager, RoleEnum::TeamLead, RoleEnum::MD] as $roleEnum) {
            Role::where('name', $roleEnum->value)->first()
                ->givePermissionTo($sharedPermissions);
        }


        /**========================================================================
         *                           USER HAS ROLE
         *========================================================================**/

        $administrator = User::where('email', 'admin@itrend.com')->first();
        $user          = User::where('email', 'user@itrend.com')->first();
        $developer     = User::where('email', 'developer@itrend.com')->first();
        $supplier      = User::where('email', 'supplier@itrend.com')->first();

        $administrator->syncRoles([DefaultRoleEnum::Administrator->value]);
        $user->syncRoles([RoleEnum::User->value]);
        $supplier->syncRoles([RoleEnum::Supplier->value]);
        $developer->syncRoles([RoleEnum::Developer->value]);

        /**========================================================================
         *                           USER HAS PERMISSION
         *========================================================================**/
    }
}
