<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzAdsGroupsSd extends Model
{
    //
    use SoftDeletes;

    protected $table = 'amz_ads_groups_sd';

    protected $fillable = [
        'country',
        'ad_group_id',
        'campaign_id',
        'name',
        'default_bid',
        'bid_optimization',
        'state',
        'tactic',
        'creative_type',
        'added',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'added',
        // Add your date columns here
    ];

    protected $casts = [
        'options' => 'array',
    ];
}
