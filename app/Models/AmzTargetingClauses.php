<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzTargetingClauses extends Model
{
    //
    use SoftDeletes;

    protected $table = 'amz_targeting_clauses';

    protected $fillable = [
        'country',
        'target_id',
        'ad_group_id',
        'campaign_id',
        'expression',
        'expression_val',
        'state',
        'bid',
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
}
