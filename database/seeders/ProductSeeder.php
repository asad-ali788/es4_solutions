<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    
    public function run(): void
    {
        $faker = Faker::create();
        $countries = config('countries');

        foreach (range(1, 20) as $index) {
            // Insert into `products` table
            $productId = DB::table('products')->insertGetId([
                'uuid'         => Str::uuid(),
                'sku'          => strtoupper('SKU' . $faker->unique()->numberBetween(1000, 9999)),
                'short_title'  => $faker->sentence(3),
                'status'       => $faker->boolean,
                'created_at'   => now(),
            ]);

            // Insert into `product_listings` table for each country
            foreach ($countries as $country) {
                $productListingId = DB::table('product_listings')->insertGetId([
                    'products_id'       => $productId,
                    'uuid'              => Str::uuid(),
                    'translator'        => $faker->name,
                    'title_amazon'      => $faker->sentence(4),
                    'bullet_point_1'    => $faker->sentence(5),
                    'bullet_point_2'    => $faker->sentence(5),
                    'bullet_point_3'    => null,
                    'bullet_point_4'    => null,
                    'bullet_point_5'    => null,
                    'description'       => $faker->paragraph,
                    'search_terms'      => $faker->words(5, true),
                    'advertising_keywords' => $faker->words(5, true),
                    'instructions_file' => null,
                    'country'           => $country, // Use the current country from the loop
                    'product_category'  => $faker->word,
                    'progress_status'   => 1,
                    'created_at'        => now(),
                ]);

                // Insert into `product_additional_details` table
                DB::table('product_additional_details')->insert([
                    'product_listings_id' => $productListingId,
                    'fba_barcode_file'    => null,
                    'product_label_file'  => null,
                    'instructions_file_2' => null,
                    'listing_to_copy'     => $faker->paragraph,
                    'listing_research_file' => null,
                    'warnings'            => $faker->sentence,
                    'image1'              => null,
                    'image2'              => null,
                    'image3'              => null,
                    'image4'              => null,
                    'image5'              => null,
                    'image6'              => null,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);

                // Insert into `product_pricings` table
                DB::table('product_pricings')->insert([
                    'product_listings_id' => $productListingId,
                    'item_price'          => null,
                    'postage'             => null,
                    'base_price'          => null,
                    'fba_fee'             => null,
                    'duty'                => null,
                    'air_ship'            => null,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);

                DB::table('product_container_infos')->insert([
                    'product_listings_id' => $productListingId,
                    
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }
        }
    }
}
