<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzAdsReportLog extends Model
{
    use SoftDeletes;

    protected $table = 'amz_ads_report_log';

    protected $fillable = [
        'country',
        'report_id',
        'report_type',
        'report_status',
        'report_date',
        'r_iteration',
        'added',
    ];

    protected $dates = [
        'report_date',
        'added',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
