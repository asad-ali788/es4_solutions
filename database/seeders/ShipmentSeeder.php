<?php

namespace Database\Seeders;

use App\Models\InboundShipment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Shipment;
use Faker\Factory as Faker;
use Illuminate\Support\Carbon;

class ShipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker                 = Faker::create();
        $shipments             = [];
        $fixedSupplierId       = 3;
        $availableWarehouseIds = Warehouse::whereBetween('id', [1, 5])->pluck('id')->toArray();
        $statuses              = ['planned', 'shipped', 'in_transit', 'received', 'cancelled'];
        $carriers              = ['FedEx', 'UPS', 'DHL', 'USPS', 'Local Courier'];

        for ($i = 0; $i < 15; $i++) {
            $dispatchDate    = $faker->dateTimeBetween('-2 months', '+1 month');
            $expectedArrival = Carbon::parse($dispatchDate)->addWeeks(5);
            $status          = $faker->randomElement($statuses);
            $actualArrival   = null;

            if (in_array($status, ['received'])) {
                $actualArrival = Carbon::parse($expectedArrival)->addDays($faker->numberBetween(-7, 7));
            } elseif (in_array($status, ['in_transit', 'shipped']) && $dispatchDate < now()) {
                $dispatchDate = $faker->dateTimeBetween('-2 months', '-1 day');
            }

            $shipments[] = [
                'shipment_name'    => $faker->words(3, true) . ' Shipment',
                'supplier_id'      => $fixedSupplierId,
                'warehouse_id'     => $faker->randomElement($availableWarehouseIds),
                'status'           => $status,
                'tracking_number'  => $faker->unique()->regexify('[A-Z]{2}[0-9]{9}[A-Z]{2}'),
                'carrier_name'     => $faker->randomElement($carriers),
                'dispatch_date'    => $dispatchDate,
                'expected_arrival' => $expectedArrival,
                'actual_arrival'   => $actualArrival,
                'shipping_notes'   => $faker->boolean(30) ? $faker->sentence : null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }

        InboundShipment::insert($shipments);
    }
}
