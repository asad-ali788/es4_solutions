<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzAdsProducts extends Model
{
    use SoftDeletes;

    protected $table = 'amz_ads_products';

    protected $fillable = [
        'country',
        'ad_id',
        'ad_group_id',
        'campaign_id',
        'asin',
        'sku',
        'state',
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

    public function campaigns()
    {
        return $this->hasMany(AmzCampaigns::class, 'campaign_id', 'campaign_id');
    }
}
