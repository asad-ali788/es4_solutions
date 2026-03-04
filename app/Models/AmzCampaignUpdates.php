<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzCampaignUpdates extends Model
{
    use SoftDeletes;

    protected $table = 'amz_campaign_updates';

    protected $fillable = [
        'campaign_id',
        'campaign_type',
        'old_budget',
        'new_budget',
        'old_status',
        'new_status',
        'iteration',
        'status',
        'country',
        'api_response ',
        'updated_by',
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

    public function user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
