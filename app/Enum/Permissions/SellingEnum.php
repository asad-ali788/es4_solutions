<?php

namespace App\Enum\Permissions;

enum SellingEnum: string
{
    case Selling                   = 'selling';
    case SellingSku                = 'selling.sku';
    case SellingAsin               = 'selling.asin';
    case SellingDashboard          = 'selling.dashboard';
    case SellingSetPrice           = 'selling.set-price';
    case SellingDiscontinue        = 'selling.discontinue';
    case SellingStockInfo          = 'selling.stock-info';
    case SellingForecastByMonth    = 'selling.forecast-by-month';
    case SellingDailySales         = 'selling.daily-sales';
    case SellingWeeklySales        = 'selling.weekly-sales';
    case SellingProductRanking     = 'selling.product-ranking';
    case SellingProductPriceRange  = 'selling.product-price-range';
    case SellingProductLog         = 'selling.product-log';
    case SellingNotes              = 'selling.notes';
    case SellingAdvertisingCost    = 'selling.advertising-cost';
    case SellingCampaigns          = 'selling.campaign-info';

    case SellingAdsItem                       = 'selling.ads-item';
    case SellingAdsItemDashboard              = 'selling.ads-item.dashboard';
    case SellingAdsItemDashboardSalesData     = 'selling.ads-item.dashboard.salesdata';
    case SellingAdsItemDashboardTotalCampaign = 'selling.ads-item.dashboard.totalcampaign';



    public function label(): string
    {
        return match ($this) {
            self::Selling                => "Selling Access",
            self::SellingSku             => "View SKU Information",
            self::SellingAsin            => "View ASIN Information",
            self::SellingDashboard       => "Access Selling Dashboard",
            self::SellingSetPrice        => "Set Product Price",
            self::SellingDiscontinue     => "Discontinue Products",
            self::SellingStockInfo       => "View Stock Information",
            self::SellingForecastByMonth => "Forecast Sales by Month",
            self::SellingDailySales      => "View Daily Sales",
            self::SellingWeeklySales     => "View Weekly Sales",
            self::SellingProductRanking  => "View Product Ranking",
            self::SellingProductLog      => "View Product Logs",
            self::SellingNotes           => "Manage Selling Notes",
            self::SellingAdvertisingCost => "View Advertising Costs",
            self::SellingProductPriceRange => "View Product Price Ranges",
            self::SellingCampaigns       => "View Product Campaigns",

            self::SellingAdsItem                       => "Ads Item - Asin",
            self::SellingAdsItemDashboard              => "Ads Item Dashboard",
            self::SellingAdsItemDashboardSalesData     => "Ads Item Dashboard Sales Data",
            self::SellingAdsItemDashboardTotalCampaign => "Ads Item Dashboard Total Campaign",
        };
    }

    public static function labels(): array
    {
        return [
            'label' => 'Selling Permissions',
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
