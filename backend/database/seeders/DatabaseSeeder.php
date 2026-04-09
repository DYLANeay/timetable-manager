<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'manager@station.com'],
            ['name' => 'Manager', 'role' => Role::Manager, 'password' => bcrypt('password')],
        );

        User::firstOrCreate(
            ['email' => 'employee@station.com'],
            ['name' => 'Employee', 'role' => Role::Employee, 'password' => bcrypt('password')],
        );

        $this->call(ShiftTemplateSeeder::class);
    }
}
