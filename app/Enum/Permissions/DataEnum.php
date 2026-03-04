<?php

namespace App\Enum\Permissions;

enum DataEnum: string
{
    case Data                               = 'data';
    case DataDownloadUSMaster               = 'data.download.us-master';
    case DataDownloadForecast               = 'data.download.forecast';
    case DataDownloadAdsCampaignPerformance = 'data.download.ads-campaign-performance';
    case DataDownloadPerformanceReport      = 'data.download.performance-report';
    case DataDownloadWarehouseReport        = 'data.download.warehouse-report';
    case DataExchangeRate                   = 'data.exchange-rate';
    case DataExchangeRateUpdate             = 'data.exchange-rate-update';

    public function label(): string
    {
        return match ($this) {
            self::Data                               => "Data Access",
            self::DataDownloadUSMaster               => "Download US Master Data",
            self::DataDownloadForecast               => "Download Forecast Data",
            self::DataDownloadAdsCampaignPerformance => "Download Ads Campaign Performance Data",
            self::DataDownloadPerformanceReport      => "Download Performance Report",
            self::DataDownloadWarehouseReport        => "Download Warehouse Report",
            self::DataExchangeRate                   => "Exchange Rate Data",
            self::DataExchangeRateUpdate             => "Update Exchange Rate Data",
        };
    }

    public static function labels(): array
    {
        return [
            'label' => 'Data Permissions',
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
