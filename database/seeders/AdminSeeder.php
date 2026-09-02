<?php

namespace Database\Seeders;

use App\Domain\Appointments\Models\AppointmentType;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Properties\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::whereIn('slug', ['demo-organisation', 'montana-resort'])->first();
        $property = Property::whereIn('code', ['MAIN', 'MR'])->first() ?? Property::where('is_active', true)->first();

        $admin = User::firstOrCreate(
            ['email' => 'admin@crm.test'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'department' => 'Management',
                'is_active' => true,
                'organization_id' => $org?->id,
            ]
        );

        if ($org) {
            setPermissionsTeamId($org->id);
        }
        $admin->assignRole('Administrator');
        if ($property) {
            $admin->properties()->sync([$property->id]);
        }

        if ($org && ! $org->owner_id) {
            $org->update(['owner_id' => $admin->id]);
        }

        $manager = User::updateOrCreate(
            ['email' => 'manager@crm.test'],
            [
                'name' => 'Resort Manager',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'department' => 'Management',
                'is_active' => true,
                'organization_id' => $org?->id,
            ]
        );

        if ($org) {
            setPermissionsTeamId($org->id);
        }
        $manager->assignRole('Manager');
        $manager->syncPermissions(
            Role::findByName('Manager')->permissions->pluck('name')
        );
        if ($property) {
            $manager->properties()->sync([$property->id]);
        }

        $sales = User::firstOrCreate(
            ['email' => 'sales@crm.test'],
            [
                'name' => 'Sales Representative',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'department' => 'Sales',
                'is_active' => true,
                'organization_id' => $org?->id,
            ]
        );

        if ($org) {
            setPermissionsTeamId($org->id);
        }
        $sales->assignRole('Sales');
        $sales->syncPermissions(
            Role::findByName('Sales')->permissions->pluck('name')
        );
        if ($property) {
            $sales->properties()->sync([$property->id]);
        }

        if ($property) {
            $types = [
                ['name' => 'Guest Consultation', 'color' => '#3b82f6', 'default_duration_minutes' => 30],
                ['name' => 'Sales Meeting', 'color' => '#10b981', 'default_duration_minutes' => 60],
                ['name' => 'Resort Tour', 'color' => '#f59e0b', 'default_duration_minutes' => 45],
                ['name' => 'Event Consultation', 'color' => '#8b5cf6', 'default_duration_minutes' => 90],
            ];

            foreach ($types as $type) {
                AppointmentType::firstOrCreate(
                    ['property_id' => $property->id, 'name' => $type['name']],
                    array_merge($type, ['property_id' => $property->id])
                );
            }
        }
    }
}
