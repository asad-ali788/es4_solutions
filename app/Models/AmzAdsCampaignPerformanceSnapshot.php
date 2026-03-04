<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzAdsCampaignPerformanceSnapshot extends Model
{
    use SoftDeletes;

    protected $table = 'amz_ads_campaign_performance_snapshots';

    protected $fillable = [
        'campaign_types',
        'total_spend',
        'total_sales',
        'total_units',
        'acos',
        'country',
        'snapshot_time',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'options' => 'array',
    ];
}
