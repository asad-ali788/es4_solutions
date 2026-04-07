<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TargetingReportBi extends Model
{
    //
    use SoftDeletes;

    protected $table = 'targeting_report_bi';

    protected $fillable = [
        'report_date',
        'campaign_name',
        'portfolio_name',
        'country',
        'targeting',
        'match_type',
        'impressions',
        'clicks',
        'spend',
        'sales',
        'units',
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
