<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzTargetingClausesSb extends Model
{
    //
    use SoftDeletes;

    protected $table = 'amz_targeting_clauses_sb';

    protected $fillable = [
        'target_id',
        'campaign_id',
        'ad_group_id',
        'country',
        'bid',
        'expressions',
        'resolved_expressions',
        'state',
        'added',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'added'
        // Add your date columns here
    ];

     protected $casts = [
        'expressions' => 'array',
        'resolved_expressions' => 'array',
    ];
}
