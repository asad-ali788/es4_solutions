<?php

namespace App\Enum\Permissions;

enum CurrencyEnum: string
{
    case Currency            = 'currency';
    case CurrencyViewAny     = 'currency.view-any';
    case CurrencyView        = 'currency.view';
    case CurrencyCreate      = 'currency.create';
    case CurrencyUpdate      = 'currency.update';

    public function label(): string
    {
        return match ($this) {
            self::Currency            => "Currency Access",
            self::CurrencyViewAny     => "Currency List Access",
            self::CurrencyView        => "Currency View Access",
            self::CurrencyCreate      => "Currency Create Access",
            self::CurrencyUpdate      => "Currency Update Access",
            self::CurrencyDelete      => "Currency Delete Access",
            self::CurrencyRestore     => "Currency Restore Access",
            self::CurrencyForceDelete => "Currency Force Delete Access",
        };
    }

    public static function labels(): array
    {
        return [
            'label' => 'Currency Permissions',
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
