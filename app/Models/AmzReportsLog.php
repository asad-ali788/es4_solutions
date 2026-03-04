<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmzReportsLog extends Model
{
    //
    use SoftDeletes;

    protected $table = 'amz_reports_log';

    protected $fillable = [
        'report_type',
        'report_frequency',
        'report_id',
        'report_name',
        'report_status',
        'start_date',
        'end_date',
        'marketplace_ids',
        'report_document_id'
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'marketplace_ids' => 'array',
    ];
}
