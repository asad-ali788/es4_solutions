<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductAdditionalDetail extends Model
{
    use SoftDeletes;

    protected $table = 'product_additional_details';

    protected $fillable = [
        'fba_barcode_file',
        'product_label_file',
        'instructions_file_2',
        'listing_to_copy',
        'listing_research_file',
        'warnings',
        'image1',
        'image2',
        'image3',
        'image4',
        'image5',
        'image6',
        'product_listings_id',
    ];

    public function listing()
    {
        return $this->belongsTo(ProductListing::class, 'product_listings_id');
    }
}
