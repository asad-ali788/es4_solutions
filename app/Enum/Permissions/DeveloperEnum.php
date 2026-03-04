<?php

namespace App\Enum\Permissions;

enum DeveloperEnum: string
{
    case Developer      = 'developer';
    case DevTools       = 'developer.dev-tools';
    case LogViewer      = 'viewLogViewer';
    case Pulse          = 'viewPulse';
    case Supervisor     = 'developer.supervisor';
    case Jobs           = 'developer.jobs';
    case DatabaseBackup = 'developer.database-backup';
    case SchedulesList  = 'developer.schedule-list';

    public function label(): string
    {
        return match ($this) {
            self::Developer      => "Developer Only Access",
            self::DevTools       => "Access Developer Tools",
            self::LogViewer      => "View Application Logs",
            self::Pulse          => "Access Pulse Monitoring",
            self::Supervisor     => "Access Supervisor (Cron Management)",
            self::Jobs           => "Access Job Queue and Failed Jobs",
            self::DatabaseBackup => "Access Database Backup Download page",
            self::SchedulesList  => "Access Schedule List page",
        };
    }

    public static function labels(): array
    {
        return [
            'label' => 'Developer Permissions',
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
