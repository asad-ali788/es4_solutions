<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpTargetSugByAdgroup extends Model
{
    //
    use SoftDeletes;

    protected $table = 'amz_target_sp_sug_by_adgroup';

    protected $fillable = [
        'campaign_id',
        'ad_group_id',
        'theme',
        'target_type',
        'match_type',
        'keyword_text',
        'bid_start',
        'bid_median',
        'bid_end',
        'country',
        'target_id',
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
