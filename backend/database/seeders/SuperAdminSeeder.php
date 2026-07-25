<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['mobile' => '9999999999'],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@pga1.com',
                'password' => Hash::make('SuperAdmin@123'),
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );
    }
}
