<?php

namespace App\Enum\Permissions;

enum RoleEnum: string
{
    case Supplier  = 'supplier';
    case User      = 'user';
    case Developer = 'developer';
    case Manager   = 'manager';
    case TeamLead  = 'teamlead';
    case MD        = 'md';

    public function label(): string
    {
        return match ($this) {
            self::Supplier  => "Supplier",
            self::User      => "User",
            self::Developer => "Developer",
            self::Manager   => "Manager",
            self::TeamLead  => "TeamLead",
            self::MD        => "MD",
        };
    }

    public static function labels(): array
    {
        return [
            'label'       => 'Application Default Roles',
            'roles'       => array_reduce(self::cases(), function ($carry, $enum) {
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
