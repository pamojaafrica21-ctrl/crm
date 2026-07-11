<?php

namespace Database\Seeders;

use App\Domain\Appointments\Models\AppointmentType;
use App\Domain\Properties\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@crm.test'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'department' => 'Management',
                'is_active' => true,
            ]
        );

        $admin->assignRole('Administrator');
        $admin->properties()->sync(Property::pluck('id'));

        $manager = User::firstOrCreate(
            ['email' => 'manager@crm.test'],
            [
                'name' => 'Hotel Manager',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'department' => 'Management',
                'is_active' => true,
            ]
        );

        $manager->assignRole('Manager');
        $manager->properties()->sync(Property::pluck('id'));

        $sales = User::firstOrCreate(
            ['email' => 'sales@crm.test'],
            [
                'name' => 'Sales Representative',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'department' => 'Sales',
                'is_active' => true,
            ]
        );

        $sales->assignRole('Sales');
        $sales->properties()->sync([Property::first()->id]);

        Property::each(function (Property $property) {
            $types = [
                ['name' => 'Guest Consultation', 'color' => '#3b82f6', 'default_duration_minutes' => 30],
                ['name' => 'Sales Meeting', 'color' => '#10b981', 'default_duration_minutes' => 60],
                ['name' => 'Property Tour', 'color' => '#f59e0b', 'default_duration_minutes' => 45],
                ['name' => 'Event Consultation', 'color' => '#8b5cf6', 'default_duration_minutes' => 90],
            ];

            foreach ($types as $type) {
                AppointmentType::firstOrCreate(
                    ['property_id' => $property->id, 'name' => $type['name']],
                    array_merge($type, ['property_id' => $property->id])
                );
            }
        });
    }
}
