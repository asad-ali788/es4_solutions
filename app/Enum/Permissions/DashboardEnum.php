<?php

namespace App\Enum\Permissions;

enum DashboardEnum: string
{
    case SalesSummery               = 'dashboard.sales-summery';
    case TopSalesAndCampaignSummary = 'dashboard.top-sales-and-campaigns';
    case PerformanceGraphs          = 'dashboard.performance-graphs';
    case TopCampaigns               = 'dashboard.top-campaigns';

    public function label(): string
    {
        return match ($this) {
            self::SalesSummery               => "View Sales Summery Today and Yesterday",
            self::TopSalesAndCampaignSummary => "View Top Sellers & Campaign",
            self::PerformanceGraphs          => "View Performance Graphs",
            self::TopCampaigns               => "View Top Campaigns SP and SB",
        };
    }

    public static function labels(): array
    {
        return [
            'label' => 'Dashboard Permissions',
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
