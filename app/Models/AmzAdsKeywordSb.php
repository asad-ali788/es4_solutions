<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzAdsKeywordSb extends Model
{
    use SoftDeletes;

    protected $table = 'amz_ads_keyword_sb';

    protected $fillable = [
        'country',
        'keyword_id',
        'campaign_id',
        'ad_group_id',
        'keyword_text',
        'match_type',
        'state',
        'bid',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'added',
        'updated',
        // Add your date columns here
    ];

    protected $casts = [
        'options' => 'array',
    ];
}
