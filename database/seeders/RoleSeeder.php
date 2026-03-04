<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Enum\Permissions\RoleEnum;
use App\Enum\Permissions\DefaultRoleEnum;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = array_merge(DefaultRoleEnum::cases(), RoleEnum::cases());

        foreach ($roles as $enum) {
            Role::firstOrCreate(
                [
                    'name'       => $enum->value,
                    'guard_name' => 'web',
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
