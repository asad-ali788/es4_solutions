<?php

namespace Database\Seeders;

use App\Enum\Permissions\RoleEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

class DefaultUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!User::where('email', 'admin@itrend.com')->exists()) {
            User::factory()->create([
                'name'     => 'Administrator',
                'email'    => 'admin@itrend.com',
                'password' => Hash::make('Password@123'),
            ]);
        }

        if (!User::where('email', 'user@itrend.com')->exists()) {
            User::factory()->create([
                'name'     => 'User',
                'email'    => 'user@itrend.com',
                'password' => Hash::make('Password@123'),
            ]);
        }

        if (!User::where('email', 'supplier@itrend.com')->exists()) {
            User::factory()->create([
                'name'     => 'Supplier',
                'email'    => 'supplier@itrend.com',
                'password' => Hash::make('Password@123'),
            ]);
        }
        if (!User::where('email', 'developer@itrend.com')->exists()) {
            User::factory()->create([
                'name'     => 'Developer',
                'email'    => 'developer@itrend.com',
                'password' => Hash::make('Password@123'),
            ]);
        }
        
        // $role = RoleEnum::User->value;

        // User::factory()->count(50)->create()->each(function ($user) use ($role) {
        //     $user->assignRole($role);
        // });
    }
}
