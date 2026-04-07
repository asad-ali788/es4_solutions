<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NightSupportCampaignBi extends Model
{
    use SoftDeletes;

    protected $table = 'night_support_campaign_bi';

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'report_date',
        'campaign',
        'state',
        'status',
        'type',
        'targeting',
        'impressions',
        'clicks',
        'ctr',
        'spend_usd',
        'cpc_usd',
        'orders',
        'sales_usd',
        'units_sold',
    ];

    /**
     * Type casting (important for BI accuracy)
     */
    protected function casts(): array
    {
        return [
            'report_date' => 'date',

            'impressions' => 'integer',
            'clicks' => 'integer',
            'orders' => 'integer',
            'units_sold' => 'integer',

            'ctr' => 'decimal:4',
            'spend_usd' => 'decimal:2',
            'cpc_usd' => 'decimal:4',
            'sales_usd' => 'decimal:2',

            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
