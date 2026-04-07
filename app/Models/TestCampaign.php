<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestCampaign extends Model
{
    protected $table = 'test_campaigns';
    protected $fillable = [
        'campaign_id',
        'ad_group_id',
        'cost',
        'sales1d',
        'sales7d',
        'purchases1d',
        'purchases7d',
        'clicks',
        'costPerClick',
        'c_budget',
        'budget_gap',
        'c_currency',
        'c_status',
        'c_date',
        'country',
        'added',
        'campaign_type',
        'report_type',
        'report_status',
    ];
    protected $dates = [
        'c_date',
        'added',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    protected $casts = [
        'cost'           => 'float',
        'sales1d'        => 'float',
        'sales7d'        => 'float',
        'costPerClick'   => 'float',
        'c_budget'       => 'float',
        'purchases1d'    => 'integer',
        'purchases7d'    => 'integer',
        'clicks'         => 'integer',
        'budget_gap'     => 'boolean',
    ];
}
