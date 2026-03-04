<?php

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * KeywordCampaignPerformanceLite - Unified keyword + campaign performance cache
 * 
 * This model caches keyword and campaign performance data in a single optimized
 * SQLite table for ultra-fast querying without JOINs.
 * 
 * Features:
 * - Keywords as primary identifier (grouped by campaign)
 * - Single day (1d) metrics only
 * - Campaign names instead of IDs
 * - Aggregated metrics by keyword + campaign combo
 * - Budget information included
 * - Automatic ROAS and conversion rate calculations
 */
class KeywordCampaignPerformanceLite extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'ai_sqlite';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'keyword_campaign_performance_lites';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'keyword_text',
        'campaign_name',
        'campaign_id',
        'asin',
        'country',
        'campaign_type',
        'campaign_state',
        'report_date',
        'daily_budget',
        'estimated_monthly_budget',
        'total_spend',
        'total_sales',
        'acos',
        'purchases',
        'clicks',
        'impressions',
        'cpc',
        'ctr',
        'roas',
        'conversion_rate',
        'keyword_bid',
        'keyword_state',
        'keyword_cpc',
        'keyword_match_type',
        'product_price',
        'product_rating',
        'product_review_count',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_spend' => 'float',
        'total_sales' => 'float',
        'acos' => 'float',
        'roas' => 'float',
        'cpc' => 'float',
        'ctr' => 'float',
        'conversion_rate' => 'float',
        'daily_budget' => 'float',
        'estimated_monthly_budget' => 'float',
        'keyword_bid' => 'float',
        'keyword_cpc' => 'float',
        'product_price' => 'float',
        'purchases' => 'integer',
        'clicks' => 'integer',
        'impressions' => 'integer',
        'product_rating' => 'integer',
        'product_review_count' => 'integer',
        'report_date' => 'date',
        'notes' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ========================================
    // Query Scopes
    // ========================================

    /**
     * Filter by keyword text (partial match)
     */
    public function scopeSearchKeyword($query, string $keyword)
    {
        return $query->where('keyword_text', 'like', "%{$keyword}%");
    }

    /**
     * Filter by exact keyword
     */
    public function scopeByKeyword($query, string $keyword)
    {
        return $query->where('keyword_text', $keyword);
    }

    /**
     * Filter by campaign name (partial match)
     */
    public function scopeSearchCampaign($query, string $campaign)
    {
        return $query->where('campaign_name', 'like', "%{$campaign}%");
    }

    /**
     * Filter by exact campaign name
     */
    public function scopeByCampaign($query, string $campaign)
    {
        return $query->where('campaign_name', $campaign);
    }

    /**
     * Filter by campaign ID
     */
    public function scopeByCampaignId($query, string $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Filter by ASIN
     */
    public function scopeByAsin($query, string $asin)
    {
        return $query->where('asin', $asin);
    }

    /**
     * Filter by country
     */
    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', strtoupper($country));
    }

    /**
     * Filter by campaign type (SP, SB, SD)
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('campaign_type', strtoupper($type));
    }

    /**
     * Filter by campaign state
     */
    public function scopeByState($query, string $state)
    {
        return $query->where('campaign_state', strtoupper($state));
    }

    /**
     * Filter by keyword state
     */
    public function scopeByKeywordState($query, string $state)
    {
        return $query->where('keyword_state', strtoupper($state));
    }

    /**
     * Filter by date
     */
    public function scopeForDate($query, string $date)
    {
        return $query->where('report_date', $date);
    }

    /**
     * Filter by date range
     */
    public function scopeForDateRange($query, string $fromDate, string $toDate)
    {
        return $query->whereBetween('report_date', [$fromDate, $toDate]);
    }

    /**
     * Only enabled campaigns and keywords
     */
    public function scopeEnabled($query)
    {
        return $query->where('campaign_state', 'ENABLED')
                     ->where('keyword_state', 'ENABLED');
    }

    /**
     * Filter by ACOS threshold
     */
    public function scopeHighAcos($query, float $threshold)
    {
        return $query->where('acos', '>=', $threshold)->where('acos', '>', 0);
    }

    /**
     * Filter by ROAS threshold
     */
    public function scopeGoodRoas($query, float $threshold)
    {
        return $query->where('roas', '>=', $threshold);
    }

    /**
     * Filter by sales threshold
     */
    public function scopeMinSales($query, float $amount)
    {
        return $query->where('total_sales', '>=', $amount);
    }

    /**
     * Filter by spend threshold
     */
    public function scopeMinSpend($query, float $amount)
    {
        return $query->where('total_spend', '>=', $amount);
    }

    /**
     * Sort by sales (descending)
     */
    public function scopeTopBySales($query)
    {
        return $query->orderBy('total_sales', 'desc');
    }

    /**
     * Sort by spend (descending)
     */
    public function scopeTopBySpend($query)
    {
        return $query->orderBy('total_spend', 'desc');
    }

    /**
     * Sort by ACOS (descending)
     */
    public function scopeHighestAcos($query)
    {
        return $query->orderBy('acos', 'desc');
    }

    /**
     * Sort by ROAS (descending)
     */
    public function scopeHighestRoas($query)
    {
        return $query->orderBy('roas', 'desc');
    }

    /**
     * Sort by clicks (descending)
     */
    public function scopeMostClicks($query)
    {
        return $query->orderBy('clicks', 'desc');
    }

    // ========================================
    // Model Events / Hooks
    // ========================================

    /**
     * Boot the model - handle automatic calculations
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->calculateMetrics();
        });

        static::updating(function ($model) {
            $model->calculateMetrics();
        });
    }

    /**
     * Auto-calculate ROAS and conversion rate
     */
    public function calculateMetrics(): void
    {
        // Calculate ROAS from ACOS: ROAS = 100 / ACOS (when ACOS > 0)
        if ($this->acos > 0) {
            $this->roas = round(100 / $this->acos, 4);
        } else {
            $this->roas = null;
        }

        // Calculate conversion rate: purchases / clicks (when clicks > 0)
        if ($this->clicks > 0) {
            $this->conversion_rate = round(($this->purchases / $this->clicks) * 100, 4);
        } else {
            $this->conversion_rate = 0;
        }

        // Estimate monthly budget based on daily
        if ($this->daily_budget > 0) {
            $this->estimated_monthly_budget = round($this->daily_budget * 30, 2);
        } else {
            $this->estimated_monthly_budget = 0;
        }
    }

    // ========================================
    // Accessors
    // ========================================

    /**
     * Get efficiency status (good/moderate/poor ACOS)
     */
    public function getEfficiencyAttribute(): string
    {
        if ($this->acos <= 0) return 'no-data';
        if ($this->acos <= 20) return 'excellent';
        if ($this->acos <= 40) return 'good';
        if ($this->acos <= 80) return 'moderate';
        return 'poor';
    }

    /**
     * Get profitability status based on ROAS
     */
    public function getProfitabilityAttribute(): string
    {
        if ($this->roas === null) return 'unknown';
        if ($this->roas >= 5) return 'highly-profitable';
        if ($this->roas >= 3) return 'profitable';
        if ($this->roas >= 1) return 'breakeven';
        return 'unprofitable';
    }
}
