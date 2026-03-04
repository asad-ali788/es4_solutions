<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzAdsCampaignScheduleLogs extends Model
{
    use SoftDeletes;

    protected $table = 'amz_ads_campaign_schedule_logs';

    protected $fillable = [
        'campaign_id',
        'payload_request',
        'country',
        'action',
        'executed_at',
        'status',
        'api_response',
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
