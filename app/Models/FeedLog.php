<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeedLog extends Model
{
    use SoftDeletes;

    protected $table = 'feed_logs';

    protected $fillable = [
        'feedDocID',
        'country',
        'feed_submit',
        'feed_type',
        'feed_id',
        'feed_result_ID',
        'status',
        'feed_summary',
        'date',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        // Add your date columns here
    ];

    protected $casts = [
        'feed_type'      => 'array',
        'feed_result_ID' => 'array',

    ];
}
