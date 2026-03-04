<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class ProductListingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $products = [];
        $countries = config('countries');

        foreach (range(1, 20) as $index) {
            $products[] = [
                'uuid'         => Str::uuid(),
                'sku'          => strtoupper('SKU' . $faker->unique()->numberBetween(1000, 9999)),
                'short_title'  => $faker->sentence(3),
                'translator'   => $faker->name,
                'title_amazon' => $faker->sentence(4),
                'country'      => $faker->randomElement($countries),
            ];
        }

        DB::table('product_listings')->insert($products);
    }
}
