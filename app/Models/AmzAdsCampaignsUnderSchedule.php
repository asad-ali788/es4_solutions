<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzAdsCampaignsUnderSchedule extends Model
{
    use SoftDeletes;

    protected $table = 'amz_ads_campaigns_under_schedules';

    protected $fillable = [
        'campaign_id',
        'country',
        'campaign_type',
        'campaign_status',
        'run_status',
        'user_id',
        'added',
        'last_updated',
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
