<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SourcingContainerItem extends Model
{
    use SoftDeletes;

    protected $table = 'sourcing_container_items';

    protected $fillable = [
        'uuid',
        'sourcing_container_id',
        'supplier_id',
        'sku',
        'ean',
        'short_title',
        'amazon_url',
        'image',
        'description',
        'buyer_questions',
        'supplier_answers',
        'base_price_us',
        'base_price_uk',
        'base_price_eu',
        'qty_to_order',
        'notes',
        'add_to_pl',
        'asin_no',
        'amz_price',
        'suplier_price',

        'archived',
        'archived_note',
        'archiver_user_id',
        'archived_date',

        'fba_cost',
        'carton_length',
        'carton_width',
        'carton_height',
        'carton_qty',

        'item_length',
        'item_widht',
        'item_height',

        'pro_variations',
        'pro_weight',

        'unit_price',
        'shipping_cost',
        'landed_costs_us',
        'landed_costs_eu',
        'landed_costs_uk',
        'moq',
        'total_order_value',

    ];

    public function sourcingContainer()
    {
        return $this->belongsTo(SourcingContainer::class, 'sourcing_container_id');
    }

    public function sourcingBuyerQuestionChats()
    {
        return $this->hasMany(SourcingBuyerQuestionChat::class, 'sourcing_container_items_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(SourcingBuyerQuestionChat::class, 'sourcing_container_items_id')
            ->latestOfMany();
    }
}
