<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        if (User::count() === 0) {
            User::factory()->count(5)->create();
        }

        $users = User::all();
        $titles = ['Low Amazon Stock', 'Stock Checked In Amazon'];

        foreach ($users as $user) {
            // Fake details as JSON
            $details = [
                ['sku' => 'SKU-' . rand(1000, 9999), 'quantity_available' => rand(1, 20)],
                ['sku' => 'SKU-' . rand(1000, 9999), 'quantity_available' => rand(1, 20)],
            ];

            Notification::create([
                'notification_id'  => rand(1000, 9999),
                'assigned_user_id' => rand(2, 5),
                'title'            => $titles[array_rand($titles)],
                'details'          => json_encode($details),                                     // stored as JSON string
                'level'            => rand(1, 5),
                'read_status'      => rand(0, 1),
                'created_date'     => now()->startOfMonth()->addDays(rand(0, now()->day - 1)),
                'read_date'        => null,
                'handler'          => null,
            ]);
        }
    }
}
