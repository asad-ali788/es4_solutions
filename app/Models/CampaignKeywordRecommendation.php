<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignKeywordRecommendation extends Model
{
    use SoftDeletes;

    protected $table = 'campaign_keyword_recommendations';

    protected $fillable = [
        'campaign_id',
        'ad_group_id',
        'keyword',
        'match_type',
        'bid',
        'bid_start',
        'bid_suggestion',
        'bid_end',
        'country',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        // Add your date columns here
    ];

    protected $casts = [
        'options' => 'array',
    ];
}
