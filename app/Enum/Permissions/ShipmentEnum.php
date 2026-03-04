<?php

namespace App\Enum\Permissions;

enum ShipmentEnum: string
{
    case Shipment          = 'shipment';
    case ShipmentCreate    = 'shipment.create';
    case ShipmentUpdate    = 'shipment.update';
    case ShipmentDelete    = 'shipment.delete';
    case AllShipmentList   = 'shipment.all-list';
    case AllShipmentUpdate = 'shipment.all-update';

    public function label(): string
    {
        return match ($this) {
            self::Shipment          => "Shipment Access",
            self::ShipmentCreate    => "Create Shipment",
            self::ShipmentUpdate    => "Update Shipment",
            self::ShipmentDelete    => "Delete Shipment",
            self::AllShipmentList   => "View All Shipments",
            self::AllShipmentUpdate => "Update All Shipments",
        };
    }

    public static function labels(): array
    {
        return [
            'label' => 'Shipment Permissions',
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
