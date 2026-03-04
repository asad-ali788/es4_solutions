<?php

namespace App\Enum\Permissions;

enum AmzAdsEnum: string
{
    case AmazonAds                            = 'amazon-ads';
    case AmazonAdsData                        = 'amazon-ads.data';
    case AmazonAdsDataCampaigns               = 'amazon-ads.data.campaigns';
    case AmazonAdsDataCampaignCreate          = 'amazon-ads.data.campaign-create';
    case AmazonAdsDataCampaignUpdate          = 'amazon-ads.data.campaign-update';
    case AmazonAdsDataCampaignSchedule        = 'amazon-ads.data.campaign-schedule';
    case AmazonAdsDataCampaignRelatedKeywords = 'amazon-ads.data.campaign-related-keywords';
    case AmazonAdsKeywords                    = 'amazon-ads.keywords';
    case AmazonAdsKeywordUpdate               = 'amazon-ads.keyword-update';
    case AmazonAdsTargets                     = 'amazon-ads.targets';
    case AmazonAdsBudgetsUsage                = 'amazon-ads.budgets-usage';
    case AmazonAdsBudgetRecommendation        = 'amazon-ads.budget-recommendation';

    case AmazonAdsPerformance                     = 'amazon-ads.performance';
    case AmazonAdsAsinPerformance                 = 'amazon-ads.asin-performance';
    case AmazonAdsCampaignPerformance             = 'amazon-ads.campaign-performance';
    case AmazonAdsCampaignPerformanceManualBudget = 'amazon-ads.campaign-performance.manual-budget';
    case AmazonAdsCampaignPerformanceRunUpdate    = 'amazon-ads.campaign-performance.run-update';
    case AmazonAdsCampaignPerformanceMakeLive     = 'amazon-ads.campaign-performance.make-live';
    case AmazonAdsCampaignPerformanceExcelExport  = 'amazon-ads.campaign-performance.excel-export';

    case AmazonAdsKeywordPerformance              = 'amazon-ads.keyword-performance';
    case AmazonAdsKeywordPerformanceManualBudget  = 'amazon-ads.keyword-performance.manual-budget';
    case AmazonAdsKeywordPerformanceRunUpdate     = 'amazon-ads.keyword-performance.run-update';
    case AmazonAdsKeywordPerformanceMakeLive      = 'amazon-ads.keyword-performance.make-live';
    case AmazonAdsKeywordPerformanceExcelExport   = 'amazon-ads.keyword-performance.excel-export';

    case AmazonAdsTargetPerformance               = 'amazon-ads.target-performance';
    case AmazonAdsTargetPerformanceRunUpdate      = 'amazon-ads.target-performance.run-update';
    case AmazonAdsTargetPerformanceMakeLive       = 'amazon-ads.target-performance.make-live';
    case AmazonAdsTargetPerformanceExport         = 'amazon-ads.target-performance.export';
    case AmazonAdsViewLivePerformance             = 'amazon-ads.view-live-performance';
    case AmazonAdsCampaignSchedule                = 'amazon-ads.campaign-schedule';
    case AmazonAdsCampaignAddSchedule             = 'amazon-ads.campaign-schedule.add';
    case AmazonAdsCampaignUpdateSchedule          = 'amazon-ads.campaign-schedule.update';

    case AmazonAdsCampaignRuleUpdate              = 'amazon-ads.campaign-cron-rules.update';
    case AmazonAdsKeywordRuleUpdate               = 'amazon-ads.keyword-cron-rules.update';

    case AmazonAdsLogs                            = 'amazon-ads.update-logs';

    case AmazonAdsCampaignOverviewDashboard             = 'amazon-ads.campaign-overview-dashboard';
    case AmazonAdsCampaignOverview                      = 'amazon-ads.campaign-overview';
    case AmazonAdsCampaignOverviewKeywords              = 'amazon-ads.campaign-overview-keyword';
    case AmazonAdsCampaignOverviewKeywordRecommendation = 'amazon-ads.campaign-overview-keyword-recommendation';

    case AmazonAdsKeywordOverviewDashboard              = 'amazon-ads.keyword-overview-dashboard';
    case AmazonAdsKeywordOverview                       = 'amazon-ads.keyword-overview';

    case AmazonAdsSearchTerms               = 'amazon-ads.search-terms';
    case AmazonAdsSearchTermsExport         = 'amazon-ads.search-terms.export';

    public function label(): string
    {
        return match ($this) {
            self::AmazonAds                                => "1-Amazon Ads Access",
            self::AmazonAdsData                            => "1-Ads Data Access (Default)",
            self::AmazonAdsDataCampaigns                   => "1-Campaign  (SP, SB, SD) (Default)",
            self::AmazonAdsDataCampaignCreate              => "1-Campaign Create (SP)",
            self::AmazonAdsDataCampaignUpdate              => "1-Update Campaigns Budget",
            self::AmazonAdsDataCampaignSchedule            => "1-Manage Campaign Schedules",
            self::AmazonAdsDataCampaignRelatedKeywords     => "1-Campaign Related Keywords",
            self::AmazonAdsKeywords                        => "1-Keywords (SP, SB)",
            self::AmazonAdsKeywordUpdate                   => "1-Update Keywords Bid",
            self::AmazonAdsTargets                         => "1-Ad Targets (SD)",
            // 
            self::AmazonAdsPerformance                     => "2-Ads Performance Access",
            self::AmazonAdsAsinPerformance                 => "2-ASIN Performance (Default)",
            self::AmazonAdsCampaignPerformance             => "2-Campaign Performance",
            self::AmazonAdsCampaignPerformanceManualBudget => "2-Update Campaign Manual Budget",
            self::AmazonAdsCampaignPerformanceRunUpdate    => "2-Run Campaign Performance Update",
            self::AmazonAdsCampaignPerformanceMakeLive     => "2-Make Campaigns Live",
            self::AmazonAdsCampaignPerformanceExcelExport  => "2-Export Campaign Performance to Excel",

            self::AmazonAdsKeywordPerformance              => "3-View Keyword Performance",
            self::AmazonAdsKeywordPerformanceManualBudget  => "3-Update Keyword Manual Budget",
            self::AmazonAdsKeywordPerformanceRunUpdate     => "3-Run Keyword Performance Update",
            self::AmazonAdsKeywordPerformanceMakeLive      => "3-Make Keywords Live",
            self::AmazonAdsKeywordPerformanceExcelExport   => "3-Export Keyword Performance to Excel",

            self::AmazonAdsTargetPerformance               => "4-View Target Performance",
            self::AmazonAdsTargetPerformanceRunUpdate      => "4-Run Target Performance Update",
            self::AmazonAdsTargetPerformanceMakeLive       => "4-Make Targets Live",
            self::AmazonAdsTargetPerformanceExport         => "4-Export Target Performance Data",
            self::AmazonAdsViewLivePerformance             => "4-View Live Performance",
            // 
            self::AmazonAdsCampaignSchedule                => "5-View Campaign Schedules",
            self::AmazonAdsCampaignAddSchedule             => "5-Add Campaign Schedule",
            self::AmazonAdsCampaignUpdateSchedule          => "5-Update Campaign Schedule",

            self::AmazonAdsCampaignRuleUpdate               => "6-Update Campaign Cron Rules",
            self::AmazonAdsKeywordRuleUpdate                => "6-Update Keyword Cron Rules",

            self::AmazonAdsLogs                             => "7-View Budget / Bid Update Logs",

            self::AmazonAdsCampaignOverviewDashboard             => "8-Campaign Overview Dashboard",
            self::AmazonAdsCampaignOverview                      => "8-Campaign Overview Campaigns",
            self::AmazonAdsCampaignOverviewKeywords              => "8-Campaign Overview Keywords",
            self::AmazonAdsCampaignOverviewKeywordRecommendation => "8-Campaign Overview Keyword Recommendations",

            self::AmazonAdsKeywordOverviewDashboard              => "8-Keywords Overview Dashboard",
            self::AmazonAdsKeywordOverview                       => "8-Keywords Overview Keywords",
            
            self::AmazonAdsSearchTerms               => "9-View Search Terms",
            self::AmazonAdsSearchTermsExport         => "9-Export Search Terms",

            self::AmazonAdsBudgetsUsage         => "10-Campaign Budget Usage",
            self::AmazonAdsBudgetRecommendation => "10-Campaign Budget Recommendation",
        };
    }

    public static function labels(): array
    {
        return [
            'label' => 'Amazon Ads Permissions',
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
