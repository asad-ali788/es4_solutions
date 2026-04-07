<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModifiedCategoryRankHistoryBi extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'modified_category_rank_history_bis';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'date',
        'asin',
        'category_type',
        'category_name',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date',
    ];
}
