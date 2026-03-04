<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpKeywordSugByAdgroup extends Model
{
    use SoftDeletes;

    protected $table = 'amz_keyword_sp_sug_by_adgroup';

    protected $fillable = [
        'ad_group_id',
        'campaign_id',
        'keyword_id',
        'keyword_text',
        'bid_start',
        'bid_suggestion',
        'bid_end',
        'match_type',
        'country',
        'added',
        'is_processed',
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
