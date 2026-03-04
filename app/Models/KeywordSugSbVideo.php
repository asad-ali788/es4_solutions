<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KeywordSugSbVideo extends Model
{
    //
    use SoftDeletes;

    protected $table = 'keyword_sug_sb_video';

    protected $fillable = [
        'ad_group_id',
        'campaign_id',
        'keyword_id',
        'keyword_text',
        'key_bid_start',
        'key_bid_end',
        'key_bid_suggestion',
        'target_bid_start',
        'target_bid_end',
        'target_bid_suggestion',
        'match_type',
        'country',
        'added'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'added',
    ];

    protected $casts = [
        'options' => 'array',
    ];
}
