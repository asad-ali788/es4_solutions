<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzAdsProductsSd extends Model
{
    //
    use SoftDeletes;

    protected $table = 'amz_ads_products_sd';

    protected $fillable = [
        'country',
        'ad_id',
        'state',
        'ad_group_id',
        'campaign_id',
        'ad_name',
        'asin',
        'sku',
        'landing_page_url',
        'landing_page_type',
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
