<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('currencies')->upsert([
            [
                'country_code' => 'US',
                'currency_code' => 'USD',
                'currency_name' => 'US Dollar',
                'currency_symbol' => '$',
                'conversion_rate_to_usd' => 1.00,
            ],
            [
                'country_code' => 'UK',
                'currency_code' => 'GBP',
                'currency_name' => 'British Pound',
                'currency_symbol' => '£',
                'conversion_rate_to_usd' => 1.28,
            ],
            [
                'country_code' => 'CA',
                'currency_code' => 'CAD',
                'currency_name' => 'Canadian Dollar',
                'currency_symbol' => 'C$',
                'conversion_rate_to_usd' => 0.74,
            ],
            [
                'country_code' => 'FR',
                'currency_code' => 'EUR',
                'currency_name' => 'Euro',
                'currency_symbol' => '€',
                'conversion_rate_to_usd' => 1.08,
            ],
            [
                'country_code' => 'DE',
                'currency_code' => 'EUR',
                'currency_name' => 'Euro',
                'currency_symbol' => '€',
                'conversion_rate_to_usd' => 1.08,
            ],
            [
                'country_code' => 'ES',
                'currency_code' => 'EUR',
                'currency_name' => 'Euro',
                'currency_symbol' => '€',
                'conversion_rate_to_usd' => 1.08,
            ],
            [
                'country_code' => 'MX',
                'currency_code' => 'MXN',
                'currency_name' => 'Mexican Peso',
                'currency_symbol' => '$',
                'conversion_rate_to_usd' => 0.059,
            ],
        ], ['country_code'], [
            'currency_code',
            'currency_name',
            'currency_symbol',
        ]);
    }
}
