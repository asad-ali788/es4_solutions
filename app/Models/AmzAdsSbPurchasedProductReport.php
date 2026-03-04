<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmzAdsSbPurchasedProductReport extends Model
{
    protected $table = 'amz_ads_sb_purchased_product_reports';

    protected $fillable = [
        'campaign_id',
        'campaign_name',
        'ad_group_id',
        'ad_group_name',
        'asin',
        'product_name',
        'product_cat',
        'orders14d',
        'sales14d',
        'units_sold14d',
        'ntb_orders14d',
        'ntb_orders_pct14d',
        'ntb_purchases14d',
        'ntb_purchases_pct14d',
        'ntb_sales14d',
        'ntb_sales_pct14d',
        'ntb_units14d',
        'ntb_units_pct14d',
        'c_date',
        'country',
        'added'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'options' => 'array',
    ];
}
