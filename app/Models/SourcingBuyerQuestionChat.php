<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SourcingBuyerQuestionChat extends Model
{
    use SoftDeletes;

    protected $table = 'sourcing_buyer_QuestionChat';

    protected $fillable = [
        'sourcing_container_items_id',
        'q_a',
        'attachment',
        'sender_id',
        'receiver_id',
        'record_type',
        'read_status',
        'created_date',
    ];

    public function sourcingContainerItems()
    {
        return $this->hasMany(SourcingContainerItem::class, 'sourcing_container_items_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
