<?php

namespace App\Enum\Permissions;

enum OrderForecastEnum: string
{
    case OrderForecast                   = 'order_forecast';
    case OrderForecastCreate             = 'order_forecast.create';
    case OrderForecastUpdate             = 'order_forecast.update';
    case OrderForecastDelete             = 'order_forecast.delete';
    case OrderForecastDownloadSnapShorts = 'order_forecast.download-snapshots';
    case OrderForecastSku                = 'order_forecast.sku';
    case OrderForecastSkuExport          = 'order_forecast.sku-export';
    case OrderForecastAsin               = 'order_forecast.asin';
    case OrderForecastAsinExport         = 'order_forecast.asin-export';
    case OrderForecastAsinMonthly        = 'order_forecast.asin-monthly';
    case OrderForecastAsinMonthlyExport  = 'order_forecast.asin-monthly-export';

    public function label(): string
    {
        return match ($this) {
            self::OrderForecast                   => "Order Forecast Access",
            self::OrderForecastCreate             => "Create Order Forecast",
            self::OrderForecastUpdate             => "Update Order Forecast",
            self::OrderForecastDelete             => "Delete Order Forecast",
            self::OrderForecastDownloadSnapShorts => "Download Forecast Snapshots",
            self::OrderForecastSku                => "View Forecast by SKU",
            self::OrderForecastSkuExport          => "Export Forecast by SKU",
            self::OrderForecastAsin               => "View Forecast by ASIN",
            self::OrderForecastAsinExport         => "Export Forecast by ASIN",
            self::OrderForecastAsinMonthly        => 'View Monthly Forecast by ASIN',
            self::OrderForecastAsinMonthlyExport  => 'Export Monthly Forecast by ASIN',
        };
    }

    public static function labels(): array
    {
        return [
            'label' => 'Order Forecast Permissions',
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
