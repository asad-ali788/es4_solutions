<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SourcingDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed sourcing_container
        for ($i = 1; $i <= 4; $i++) { // Create 4 containers
            $containerId = 'container_' . $i;
            DB::table('sourcing_container')->insert([
                'uuid'         => (string) Str::uuid(),
                'container_id' => $containerId,
                'descriptions' =>  fake()->paragraph,
                'due_date'     => now()->addDays(rand(1, 30)),
                'created_at'   => now(),
            ]);

            // Seed sourcing_container_items for each container
            for ($j = 1; $j <= 50; $j++) { // Create 50 items per container
                $itemId = DB::table('sourcing_container_items')->insertGetId([
                    'uuid'                  => (string) Str::uuid(),
                    'sourcing_container_id' => $i,                                                 // Link to the current container
                    'supplier_id'           => 3,                                                  // Assuming there are at least 10 suppliers
                    'sku'                   => 'SKU' . rand(1000, 9999),
                    'ean'                   => 'EAN_' . fake()->unique()->numberBetween(1, 999),
                    'short_title'           => fake()->words(3, true),
                    'amazon_url'            => 'https://www.amazon.in/Amazon/dp/' . 'B083F2X9Y' . $j.'/',
                    'image'                 => 'sourcing/y11cseuN6Kk5zhv5iJTLV6pN3MtmdPGKzqdfHoNP.jpg',
                    'description'           => fake()->paragraph,
                    // 'price'                 => rand(100, 1000) / 10,
                    'qty_to_order'      => rand(1, 100),
                    'qty_to_order_uk'   => rand(1, 100),
                    'notes'             => fake()->paragraph,
                    'add_to_pl'         => rand(0, 1),
                    'amz_price'         => rand(100, 1000) / 10,
                    'suplier_price'     => rand(100, 1000) / 10,
                    'archived'          => rand(0, 1),
                    'archived_note'     => fake()->paragraph,
                    'archiver_user_id'  => rand(1, 10),
                    'archived_date'     => now()->subDays(rand(1, 30)),
                    'fba_cost'          => json_encode(['cost' => rand(10, 100)]),
                    'carton_length'     => rand(10, 50),
                    'carton_width'      => rand(10, 50),
                    'carton_height'     => rand(10, 50),
                    'item_length'       => rand(10, 50),
                    'item_widht'        => rand(10, 50),
                    'item_height'       => rand(10, 50),
                    'carton_qty'        => rand(1, 50),
                    'pro_weight'        => rand(1, 100),
                    'shipping_usd'      => rand(10, 100),
                    'unit_price'        => rand(100, 1000),
                    'shipping_cost'     => rand(10, 100),
                    'landed_costs_eu'   => rand(100, 1000),
                    'landed_costs_us'   => rand(100, 1000),
                    'landed_costs_uk'   => rand(100, 1000),
                    'moq'               => rand(1, 100),
                    'total_order_value' => rand(1000, 10000),
                    'pro_variations'    => 'Variation_' . $i . '_' . $j . ',Variation_' . $i . '_' . ($j + 1),
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                // Seed sourcing_buyer_QuestionChat for each item
                for ($k = 1; $k <= 3; $k++) { // Create 3 question chat rows per item
                    DB::table('sourcing_buyer_QuestionChat')->insert([
                        'sourcing_container_items_id' => $itemId,
                        'q_a' => 'Question and Answer ' . $i . '_' . $j . '_' . $k,
                        'attachment' => 'http://example.com/attachment/' . $i . '_' . $j . '_' . $k . '.pdf',
                        'sender_id' => rand(1, 10), // Assuming there are at least 10 users
                        'receiver_id' => rand(1, 10),
                        'record_type' => rand(0, 1),
                        'read_status' => rand(0, 1),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}