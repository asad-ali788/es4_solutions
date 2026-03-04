<?php

namespace App\Models;

use App\Enum\Permissions\CurrencyEnum;
use App\Enum\Permissions\DeveloperEnum;
use App\Enum\Permissions\ProductEnum;
use App\Enum\Permissions\PurchaseOrderEnum;
use App\Enum\Permissions\ShipmentEnum;
use App\Enum\Permissions\SourcingEnum;
use App\Enum\Permissions\UserEnum;
use App\Enum\Permissions\WarehouseEnum;
use App\Enum\Permissions\OrderForecastEnum;
use App\Enum\Permissions\SellingEnum;

use Carbon\Carbon;
use Spatie\Permission\Models\Permission as SpatiePermission;
use App\Enum\Permissions\AmzAdsEnum;
use App\Enum\Permissions\DashboardEnum;
use App\Enum\Permissions\DataEnum;
use App\Enum\Permissions\NotificationEnum;
use App\Enum\Permissions\StocksEnum;

class Permission extends SpatiePermission
{
    //
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'permissions';

    protected $fillable = [
        'name',
        'guard_name',
    ];

    public static function fetchAllStaticPermissions(): array
    {
        return [
            AmzAdsEnum::labels(),
            UserEnum::labels(),
            DashboardEnum::labels(),
            ProductEnum::labels(),
            DeveloperEnum::labels(),
            DataEnum::labels(),
            PurchaseOrderEnum::labels(),
            SourcingEnum::labels(),
            ShipmentEnum::labels(),
            WarehouseEnum::labels(),
            CurrencyEnum::labels(),
            OrderForecastEnum::labels(),
            SellingEnum::labels(),
            NotificationEnum::labels(),
            StocksEnum::labels(),
        ];
    }

    public static function fetchAllStaticPermissionKeys(): array
    {
        return [
            AmzAdsEnum::cases(),
            DashboardEnum::cases(),
            UserEnum::cases(),
            ProductEnum::cases(),
            DeveloperEnum::cases(),
            DataEnum::cases(),
            PurchaseOrderEnum::cases(),
            SourcingEnum::cases(),
            ShipmentEnum::cases(),
            WarehouseEnum::cases(),
            CurrencyEnum::cases(),
            OrderForecastEnum::cases(),
            SellingEnum::cases(),
            NotificationEnum::cases(),
            StocksEnum::cases(),
        ];
    }

    public static function fetchAllStaticPermissionKeysWithoutGroup(): array
    {
        $data = self::fetchAllStaticPermissionKeys(); // returns a nested array of Enum cases

        return array_merge(...$data); // flattens it to a single array of Enum cases
    }

    public function getCreatedAtAttribute($value): string
    {
        return Carbon::parse($value)->format('d-M-Y');
    }

    public function getUpdatedAtAttribute($value): string
    {
        return Carbon::parse($value)->format('d-M-Y');
    }
}
