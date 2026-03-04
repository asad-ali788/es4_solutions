<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzCampaignsSd extends Model
{
    //
    use SoftDeletes;

    protected $table = 'amz_campaigns_sd';

    protected $fillable = [
        'country',
        'campaign_id',
        'portfolio_id',
        'campaign_name',
        'campaign_type',
        'targeting_type',
        'daily_budget',
        'start_date',
        'campaign_state',
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
    // For Sponsored Display
    public function targetSb()
    {
        return $this->hasMany(AmzTargetsSd::class, 'campaign_id', 'campaign_id');
    }
}
