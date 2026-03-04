<?php

namespace App\Enum\Permissions;

enum WarehouseEnum: string
{
    case Warehouse                = 'warehouse';
    case WarehouseUpdate          = 'warehouse.update';
    case WarehouseList            = 'warehouse.list';
    case WarehouseImportInventory = 'warehouse.inventory.import';
    case WarehouseInventoryCreate = 'warehouse.inventory.create';
    case WarehouseInventoryUpdate = 'warehouse.inventory.update';
    case WarehouseInventoryDelete = 'warehouse.inventory.delete';
    case AllWarehouseStock        = 'warehouse.all-stock';
    case AllWarehouseStockExport  = 'warehouse.all-stock-export';

    public function label(): string
    {
        return match ($this) {
            self::Warehouse                => "Warehouse Access",
            self::WarehouseUpdate          => "Update Warehouse",
            self::WarehouseList            => "View Warehouse List",
            self::WarehouseImportInventory => "Import Warehouse Inventory",
            self::WarehouseInventoryCreate => "Warehouse Create",
            self::WarehouseInventoryUpdate => "Warehouse Update",
            self::WarehouseInventoryDelete => "Warehouse Delete",
            self::AllWarehouseStock        => "View All Warehouse Stock",
            self::AllWarehouseStockExport  => "Export All Warehouse Stock Data",
        };
    }

    public static function labels(): array
    {
        return [
            'label' => 'Warehouse Permissions',
            'permissions' => array_reduce(self::cases(), function ($carry, $enum) {
                $carry[$enum->value] = $enum->label();
                return $carry;
            }, []),
        ];
    }

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }
}
