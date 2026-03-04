<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzAdsGroups extends Model
{
    //
    use SoftDeletes;

    protected $table = 'amz_ads_groups';

    protected $fillable = [
        'country',
        'campaign_id',
        'ad_group_id',
        'gr_name',
        'gr_state',
        'default_bid',
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
