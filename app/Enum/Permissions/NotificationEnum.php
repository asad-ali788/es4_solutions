<?php

namespace App\Enum\Permissions;

enum NotificationEnum: string
{
    case Notification        = 'notification';
    case NotificationTrash   = 'notification.trash';
    case NotificationDelete  = 'notification.delete';

    public function label(): string
    {
        return match ($this) {
            self::Notification       => "Notification Access",
            self::NotificationTrash  => "View Notification Trash",
            self::NotificationDelete => "Delete Notifications",
        };
    }

    public static function labels(): array
    {
        return [
            'label' => 'Notification Permissions',
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
