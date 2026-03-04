<?php

namespace App\Enum\Permissions;

enum StocksEnum: string
{
    case Stocks           = 'stocks';
    case StocksSku        = 'stocks.sku';
    case StocksAsin       = 'stocks.asin';
    case StocksAsinExport = 'stocks.asin-export';
    case StocksSkuExport  = 'stocks.sku-export';

    public function label(): string
    {
        return match ($this) {
            self::Stocks           => "Stocks Access",
            self::StocksSku        => "View Stocks by SKU",
            self::StocksAsin       => "View Stocks by ASIN",
            self::StocksAsinExport => "Export ASIN Stock Data",
            self::StocksSkuExport  => "Export SKU Stock Data",
        };
    }

    public static function labels(): array
    {
        return [
            'label' => 'Stock Permissions',
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
