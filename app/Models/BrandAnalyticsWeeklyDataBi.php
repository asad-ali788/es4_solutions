<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrandAnalyticsWeeklyDataBi extends Model
{

    protected $table = 'brand_analytics_weekly_data_bi';

    protected $fillable = [
        'asin',
        'week_number',
        'week_date',
        'week_year',
        'impressions',
        'clicks',
        'orders',
    ];

    /**
     * Type casting (important for BI accuracy)
     */
    protected function casts(): array
    {
        return [
            'impressions' => 'integer',
            'clicks' => 'integer',
            'orders' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
