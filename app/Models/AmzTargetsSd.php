<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzTargetsSd extends Model
{
    //
    use SoftDeletes;

    protected $table = 'amz_targets_sd';

    protected $fillable = [
        'target_id',
        'ad_group_id',
        'campaign_id',
        'state',
        'bid',
        'expression_type',
        'expression',
        'resolved_expression',
        'region',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'expression' => 'array',
        'resolved_expression' => 'array',
    ];
}
