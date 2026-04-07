<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CompetitorRank360Bi extends Model
{
    protected $table = 'competitor_rank_360_bi';

    protected $fillable = [
        'asin',
        'keyword',
        'rank_value',
        'report_date',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    /**
     * Scope by ASIN.
     */
    public function scopeForAsin(Builder $query, string $asin): Builder
    {
        return $query->where('asin', $asin);
    }

    /**
     * Scope by keyword.
     */
    public function scopeForKeyword(Builder $query, string $keyword): Builder
    {
        return $query->where('keyword', $keyword);
    }

    /**
     * Scope by date.
     */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('report_date', $date);
    }

    /**
     * Scope by date range.
     */
    public function scopeBetweenDates(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('report_date', [$from, $to]);
    }
}
