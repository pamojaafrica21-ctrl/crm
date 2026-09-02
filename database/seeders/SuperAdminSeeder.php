<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@crm.test'],
            [
                'name' => 'Platform Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'organization_id' => null,
                'is_super_admin' => true,
                'is_active' => true,
            ]
        );
    }
}
