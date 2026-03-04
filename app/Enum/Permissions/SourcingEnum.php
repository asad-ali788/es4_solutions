<?php

namespace App\Enum\Permissions;

enum SourcingEnum: string
{
    case Sourcing         = 'sourcing';
    case SourcingExport   = 'sourcing.export';
    case SourcingCreate   = 'sourcing.create';
    case SourcingUpdate   = 'sourcing.update';
    case SourcingDelete   = 'sourcing.delete';
    case SourcingAddItems = 'sourcing.add-items';

    public function label(): string
    {
        return match ($this) {
            self::Sourcing         => "Sourcing Access",
            self::SourcingExport   => "Export Sourcing Data",
            self::SourcingCreate   => "Create Sourcing Entry",
            self::SourcingUpdate   => "Update Sourcing Entry",
            self::SourcingDelete   => "Delete Sourcing Entry",
            self::SourcingAddItems => "Add Items to Sourcing List",
        };
    }

    public static function labels(): array
    {
        return [
            'label' => 'Sourcing Permissions',
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
