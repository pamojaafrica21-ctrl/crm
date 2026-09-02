<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OrganizationSeeder::class,
            PropertySeeder::class,
            SubscriptionPlanSeeder::class,
            RolePermissionSeeder::class,
            AdminSeeder::class,
            SuperAdminSeeder::class,
        ]);
    }
}
