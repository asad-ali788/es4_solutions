<?php

namespace App\Enum\Permissions;

enum PurchaseOrderEnum: string
{
    case PurchaseOrder       = 'purchase-order';
    case PurchaseOrderView   = 'purchase-order.view';
    case PurchaseOrderAdd    = 'purchase-order.add';
    case AllPurchaseOrderList = 'purchase-order.all-list';

    public function label(): string
    {
        return match ($this) {
            self::PurchaseOrder        => "Purchase Order Access",
            self::PurchaseOrderView    => "View Purchase Orders",
            self::PurchaseOrderAdd     => "Add New Purchase Order",
            self::AllPurchaseOrderList => "View All Purchase Orders",
        };
    }

    public static function labels(): array
    {
        return [
            'label' => 'Purchase Order Permissions',
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
