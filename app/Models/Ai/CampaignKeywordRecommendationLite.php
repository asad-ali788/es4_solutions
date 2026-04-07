<?php

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignKeywordRecommendationLite extends Model
{
    use HasFactory;

    protected $table = 'campaign_keyword_recommendations_lite';
    
    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'campaign_type',
        'keyword',
        'match_type',
        'asin',
        'current_bid',
        'bid_suggestion_start',
        'bid_suggestion_mid',
        'bid_suggestion_end',
        'country',
        'ad_group_id',
        'report_date',
        'synced_at',
    ];

    protected $casts = [
        'report_date' => 'date',
        'synced_at' => 'datetime',
        'current_bid' => 'decimal:2',
        'bid_suggestion_start' => 'decimal:2',
        'bid_suggestion_mid' => 'decimal:2',
        'bid_suggestion_end' => 'decimal:2',
    ];

    /**
     * Scope to filter by campaign ID
     */
    public function scopeByCampaignId($query, $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Scope to filter by keyword
     */
    public function scopeByKeyword($query, $keyword)
    {
        return $query->where('keyword', 'like', '%' . $keyword . '%');
    }

    /**
     * Scope to filter by campaign type
     */
    public function scopeByCampaignType($query, $type)
    {
        return $query->where('campaign_type', $type);
    }

    /**
     * Scope to filter by match type
     */
    public function scopeByMatchType($query, $matchType)
    {
        return $query->where('match_type', $matchType);
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
