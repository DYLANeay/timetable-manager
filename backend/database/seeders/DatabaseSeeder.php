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
        User::factory()->create([
            'name' => 'Manager',
            'email' => 'manager@station.com',
            'role' => Role::Manager,
        ]);

        User::factory()->create([
            'name' => 'Employee',
            'email' => 'employee@station.com',
            'role' => Role::Employee,
        ]);
    }
}
