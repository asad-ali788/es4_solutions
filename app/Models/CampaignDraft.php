<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignDraft extends Model
{
    use SoftDeletes;

    protected $table = 'campaign_drafts';

    protected $fillable = [
        'user_id',
        'asin',
        'sku',
        'country',
        'campaign_type',
        'targeting_type',
        'campaigns',
        'status',
        'error',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'sku'       => 'array',
        'campaigns' => 'array',
    ];
}
