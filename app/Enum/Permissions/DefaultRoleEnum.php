<?php

namespace App\Enum\Permissions;


enum DefaultRoleEnum: string
{

    case Administrator = 'administrator';
    case Guest         = 'guest';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => "Administrator", // Has full access of the application
            self::Guest         => "Guest",         // Has no access of the application (Default role for new user)
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
}
