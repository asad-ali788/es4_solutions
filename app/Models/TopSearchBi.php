<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TopSearchBi extends Model
{
    //
    use SoftDeletes;

    protected $table = 'top_search_bis';

    protected $fillable = [
        'search_frequency_rank',
        'search_term',
        'top_clicked_brand_1',
        'top_clicked_brand_2',
        'top_clicked_brand_3',
        'top_clicked_category_1',
        'top_clicked_category_2',
        'top_clicked_category_3',
        'top_clicked_product_1_asin',
        'top_clicked_product_2_asin',
        'top_clicked_product_3_asin',
        'week',
        'reporting_date',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'reporting_date',
    ];

    protected $casts = [
        'reporting_date' => 'datetime',
    ];
}
