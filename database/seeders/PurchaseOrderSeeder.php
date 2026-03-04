<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str; // For UUID generation
use Illuminate\Support\Carbon; // For date manipulation
use App\Models\PurchaseOrder; // Assuming your PurchaseOrder model
use App\Models\User; // To check if supplier_id 3 exists
use App\Models\Warehouse; // To get warehouse IDs 1-5
use Faker\Factory as Faker;

class PurchaseOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $purchaseOrders = [];

        // Fixed supplier_id as 3
        $fixedSupplierId = 3;

        // Fetch warehouse IDs from 1 to 5.
        // It's assumed that warehouses with these IDs exist.
        $availableWarehouseIds = Warehouse::whereBetween('id', [1, 5])->pluck('id')->toArray();

        // Ensure there are available warehouse IDs to prevent errors
        if (empty($availableWarehouseIds)) {
            echo "Warning: No warehouses found with IDs between 1 and 5. Please ensure these warehouses exist before running PurchaseOrderSeeder.\n";
            return; // Stop execution if no valid warehouses are found
        }

        $statuses = ['draft', 'confirmed', 'shipped', 'received', 'cancelled'];
        $paymentTerms = ['Net 30', 'Net 60', 'Due on Receipt', 'Prepaid'];
        $shippingMethods = ['Ground', 'Air', 'Sea', 'Express'];

        for ($i = 0; $i < 15; $i++) {
            $orderDate = $faker->dateTimeBetween('-3 months', 'now'); // Order placed within the last 3 months
            // Expected arrival 3 to 8 weeks after order date
            $expectedArrival = Carbon::parse($orderDate)->addWeeks($faker->numberBetween(3, 8));

            $totalCost = $faker->randomFloat(2, 100, 5000); // Random cost between 100 and 5000

            $purchaseOrders[] = [
                'uuid' => (string) Str::uuid(),
                'order_number' => 'PO-' . $faker->unique()->randomNumber(5), // Generate a unique PO number
                'supplier_id' => $fixedSupplierId, // Always use ID 3
                'warehouse_id' => $faker->randomElement($availableWarehouseIds), // Randomly pick from 1-5
                'order_date' => $orderDate,
                'expected_arrival' => $expectedArrival,
                'status' => $faker->randomElement($statuses),
                'payment_terms' => $faker->randomElement($paymentTerms),
                'shipping_method' => $faker->randomElement($shippingMethods),
                'total_cost' => $totalCost,
                'notes' => $faker->boolean(20) ? $faker->sentence : null, // 20% chance of notes
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert all collected purchase order data in one go
        PurchaseOrder::insert($purchaseOrders);
    }
}
