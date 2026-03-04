<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzKeywordUpdate extends Model
{
    use SoftDeletes;

    protected $table = 'amz_keyword_updates';

    protected $fillable = [
        'keyword_id',
        'ad_group_id',
        'campaign_id',
        'keyword_type',
        'old_bid',
        'new_bid',
        'old_state',
        'new_state',
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
}
