<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PriceUpdateReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            [
                'reason_code'    => 1,
                'reason_detail'  => 'OverPerforming',
                'description'    => 'Product is performing better than expected',
            ],
            [
                'reason_code'    => 2,
                'reason_detail'  => 'UnderPerforming',
                'description'    => 'Product is performing below expectations',
            ],
            [
                'reason_code'    => 3,
                'reason_detail'  => 'Low Stock',
                'description'    => 'Product inventory is running low',
            ],
            [
                'reason_code'    => 4,
                'reason_detail'  => 'Overstock',
                'description'    => 'Product has excess inventory',
            ],
            [
                'reason_code'    => 5,
                'reason_detail'  => 'New Item',
                'description'    => 'Newly added product',
            ],
            [
                'reason_code'    => 6,
                'reason_detail'  => 'Other',
                'description'    => 'Other reasons for price update',
            ],
        ];

        foreach ($reasons as $reason) {
            DB::table('price_update_reasons')->updateOrInsert(
                ['reason_code' => $reason['reason_code']], // Unique identifier
                $reason // Data to update/insert
            );
        }
    }
}

