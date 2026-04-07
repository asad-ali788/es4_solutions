<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignPerformanceLite extends Model
{
    use HasFactory;

    protected $table = 'campaign_performance_lite';
    
    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'report_date',
        'campaign_types',
        'country',
        'asin',
        'total_daily_budget',
        'total_spend',
        'total_sales',
        'purchases7d',
        'acos',
        'campaign_state',
        'sp_targeting_type',
    ];

    protected $casts = [
        'report_date' => 'date',
        'total_daily_budget' => 'decimal:2',
        'total_spend' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'acos' => 'decimal:2',
        'purchases7d' => 'integer',
    ];

    /**
     * Scope to filter by date range
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('report_date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by campaign type
     */
    public function scopeByCampaignType($query, $type)
    {
        return $query->where('campaign_types', $type);
    }

    /**
     * Scope to filter by country
     */
    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope to filter by ASIN
     */
    public function scopeByAsin($query, $asin)
    {
        return $query->where('asin', 'like', '%' . $asin . '%');
    }
}
