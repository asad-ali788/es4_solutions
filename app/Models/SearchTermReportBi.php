<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchTermReportBi extends Model
{
    protected $table = 'search_term_report_bi';

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'report_date',
        'campaign_name',
        'portfolio_name',
        'targeting',
        'match_type',
        'customer_search_term',
        'impressions',
        'clicks',
        'spend',
        'sales',
        'units',
    ];

    /**
     * Type casting
     */
    protected function casts(): array
    {
        return [
            'report_date' => 'date',

            'impressions' => 'integer',
            'clicks' => 'integer',
            'units' => 'integer',

            'spend' => 'decimal:2',
            'sales' => 'decimal:2',

            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
