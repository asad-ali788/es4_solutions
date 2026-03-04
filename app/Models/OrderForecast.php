<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderForecast extends Model
{
    //
    use SoftDeletes;

    protected $table = 'order_forecasts';

    protected $fillable = [
        'order_name',
        'status',
        'order_date',
        'notes',
        'status_flag'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        // 'order_date',
        // Add your date columns here
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function snapshots()
    {
        return $this->hasMany(OrderForecastSnapshot::class);
    }

    public function snapshotAsins()
    {
        return $this->hasMany(OrderForecastSnapshotAsins::class, 'order_forecast_id');
    }


    /**
     * Hard delete including related snapshots.
     */
    public function forceDeleteWithSnapshots()
    {
        foreach ($this->snapshots as $snapshot) {
            $snapshot->forceDelete();
        }

        foreach ($this->snapshotAsins as $snapshotAsin) {
            $snapshotAsin->forceDelete();
        }


        return $this->forceDelete();
    }
}
