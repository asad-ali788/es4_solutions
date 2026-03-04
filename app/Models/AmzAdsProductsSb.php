<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzAdsProductsSb extends Model
{
    use SoftDeletes;

    protected $table = 'amz_ads_products_sb';

    protected $fillable = [
        'country',
        'ad_id',
        'ad_group_id',
        'campaign_id',
        'asin',
        'related_asins',
        'sku',
        'state',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        // Add your date columns here
    ];

    protected $casts = [
        'related_asins' => 'array',
    ];
}
