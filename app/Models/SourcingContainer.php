<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SourcingContainer extends Model
{
    use SoftDeletes;

    protected $table = 'sourcing_container';

    protected $fillable = [
        'container_id',
        'uuid',
        'desciptions',
        'due_date',
    ];

    const PRICE_CALCULATE_CARTON_RATE = '200';

    public function sourcingContainerItem()
    {
        return $this->hasOne(SourcingContainerItem::class, 'sourcing_container_id');
    }

    public function sourcingContainerItems()
    {
        return $this->hasMany(SourcingContainerItem::class, 'sourcing_container_id');
    }

    public function chats()
    {
        return $this->hasManyThrough(
            SourcingBuyerQuestionChat::class,       // Final model
            SourcingContainerItem::class,           // Intermediate model
            'sourcing_container_id',                 // Foreign key on intermediate model (container item)
            'sourcing_container_item_id',            // Foreign key on final model (chat)
            'id',                                   // Local key on SourcingContainer
            'id'                                    // Local key on SourcingContainerItem
        );
    }
}
