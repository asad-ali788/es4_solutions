<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductListing extends Model
{
    use SoftDeletes;

    protected $table = 'product_listings';

    protected $fillable = [
        'uuid',
        'products_id',
        'translator',
        'title_amazon',
        'bullet_point_1',
        'bullet_point_2',
        'bullet_point_3',
        'bullet_point_4',
        'bullet_point_5',
        'description',
        'search_terms',
        'advertising_keywords',
        'instructions_file',
        'country',
        'product_category',
        'progress_status',
        'disc_status',
        'sync_status',
        'title_change_status',
        'bullets_change_status',
        'description_change_status',
    ];

    public const SYNC_CLEAN  = 'clean';
    public const SYNC_DIRTY  = 'dirty';
    public const SYNC_SYNCED = 'synced';
    public const SYNC_FAILED = 'failed';

    public function product()
    {
        return $this->belongsTo(Product::class, 'products_id');
    }

    public function additionalDetail()
    {
        return $this->hasOne(ProductAdditionalDetail::class, 'product_listings_id');
    }

    public function pricing()
    {
        return $this->hasOne(ProductPricing::class, 'product_listings_id');
    }

    public function containerInfo()
    {
        return $this->hasOne(ProductContainerInfo::class, 'product_listings_id');
    }

    public function listingLog()
    {
        return $this->hasMany(ProductListingLog::class, 'product_id', 'products_id');
    }

    public function productNotes()
    {
        return $this->hasMany(ProductNote::class, 'product_id', 'products_id');
    }

    public function discontinueInfo()
    {
        return $this->hasOne(ProductDiscontinue::class, 'products_id', 'products_id');
    }

    public function productRanking()
    {
        return $this->hasOne(ProductRanking::class, 'product_id', 'products_id');
    }
}
