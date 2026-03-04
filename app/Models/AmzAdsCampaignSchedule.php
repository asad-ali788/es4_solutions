<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzAdsCampaignSchedule extends Model
{
    use SoftDeletes;

    protected $table = 'amz_ads_campaign_schedules';

    protected $fillable = [
        'day_of_week',
        'country',
        'start_time',
        'end_time',
        'hours_on',
        'hours_off',
        'added',
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
