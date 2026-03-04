<?php

namespace App\Enum\Permissions;

enum UserEnum: string
{
    case User                   = 'user';
    case UserCreate             = 'user.create';
    case UserUpdate             = 'user.update';
    case UserDelete             = 'user.delete';
    case UserDisable            = 'user.disable';

    case UserAssignAsin         = 'user.assign-asin';

    case UserPermissions        = 'user.permissions';
    case UserPermissionCreate   = 'user.permissions.create';
    case UserPermissionUpdate   = 'user.permissions.update';

    case UserRole               = 'user.roles';
    case UserRoleCreate         = 'user.roles.create';
    case UserRoleUpdate         = 'user.roles.update';
    case UserRoleDelete         = 'user.roles.delete';

    public function label(): string
    {
        return match ($this) {
            self::User                 => "Users Access",
            self::UserCreate           => "Create User",
            self::UserUpdate           => "Update User",
            self::UserDelete           => "Delete User",
            self::UserDisable          => "Disable / Block User",

            self::UserAssignAsin       => "Assign ASIN to User",

            self::UserPermissions      => "Manage User Permissions",
            self::UserPermissionCreate => "Add User Permission",
            self::UserPermissionUpdate => "Update User Permission",

            self::UserRole             => "Manage User Roles",
            self::UserRoleCreate       => "Create User Role",
            self::UserRoleUpdate       => "Update User Role",
            self::UserRoleDelete       => "Update Delete Role",
        };
    }

    public static function labels(): array
    {
        return [
            'label' => 'User Permissions',
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
