<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrderForecastSnapshot;
use App\Models\Product;
use Illuminate\Support\Str;

class OrderForecastSnapshotSeeder extends Seeder
{
    public function run(): void
    {
        $forecastId = (int) file_get_contents(storage_path('app/forecast_id.txt'));

        // Find or create the product
        $product = Product::firstOrCreate(
            ['sku' => '01-J61E-EBJ7'],
            [
                'uuid' => Str::uuid(),
                'short_title' => '8 inch Tailor Scissors with Small Snippers',
            ]
        );

        $image1 = null;

        $product->load(['listings.additionalDetail']);

        foreach ($product->listings as $listing) {
            if ($listing->additionalDetail && $listing->additionalDetail->image1) {
                $image1 = $listing->additionalDetail->image1;
                break;
            }
        }

        OrderForecastSnapshot::create([
            'order_forecast_id' => $forecastId,
            'product_id' => $product->id,
            'product_sku' => $product->sku,
            'product_title' => $product->short_title,
            'product_img' => $image1,
            'product_price' => json_encode([
                'item_price' => '3.25',
                'postage' => '0.16',
                'fba_cost' => '2.20',
                'base_price' => '13.99',
            ]),
            'country' => 'UK',
            'amazon_stock' => rand(100, 1000),
            'warehouse_stock' => rand(200, 2000),
            'routes' => json_encode([
                'LL Route' => 0,
                'NX Route' => 0,
                'DY Route' => 0,
                'TL Route' => 0,
                'AGL Route UK' => 0,
            ]),
            'shipment_in_transit' => json_encode(generateRandomShipments()),
            'ytd_sales' => generateRandomYtdSales(),
            'sales_by_month_last_3_months' => json_encode(generateSalesLast3Months()),
            'sales_by_month_last_12_months' => json_encode(generateSalesNext12Months()),
            'input_data_by_month_12_months' => json_encode(generateRandomInputDataByMonth(12)),
        ]);
    }
}
