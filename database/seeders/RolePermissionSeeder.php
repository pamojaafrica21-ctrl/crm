<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'customers.view', 'customers.create', 'customers.update', 'customers.delete',
            'quotes.view', 'quotes.create', 'quotes.update', 'quotes.delete', 'quotes.convert',
            'invoices.view', 'invoices.create', 'invoices.update', 'invoices.delete',
            'payments.view', 'payments.create',
            'tasks.view', 'tasks.create', 'tasks.update', 'tasks.delete',
            'appointments.view', 'appointments.create', 'appointments.update', 'appointments.delete',
            'targets.view', 'targets.create', 'targets.update', 'targets.delete',
            'staff.view', 'staff.create', 'staff.update', 'staff.delete',
            'reports.view', 'reports.export',
            'sync.view', 'sync.run',
            'announcements.view', 'announcements.create',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $rolePermissions = [
            'Administrator' => $permissions,
            'Manager' => array_diff($permissions, ['staff.delete', 'sync.run']),
            'Reception' => ['dashboard.view', 'customers.view', 'customers.create', 'customers.update', 'appointments.view', 'appointments.create', 'appointments.update', 'tasks.view', 'tasks.create', 'tasks.update', 'announcements.view'],
            'Reservations' => ['dashboard.view', 'customers.view', 'customers.create', 'customers.update', 'appointments.view', 'appointments.create', 'appointments.update', 'tasks.view', 'tasks.create', 'announcements.view'],
            'Sales' => ['dashboard.view', 'customers.view', 'customers.create', 'customers.update', 'quotes.view', 'quotes.create', 'quotes.update', 'quotes.convert', 'invoices.view', 'invoices.create', 'invoices.update', 'payments.view', 'payments.create', 'appointments.view', 'appointments.create', 'appointments.update', 'targets.view', 'tasks.view', 'tasks.create', 'tasks.update', 'reports.view', 'reports.export', 'announcements.view'],
            'Restaurant' => ['dashboard.view', 'customers.view', 'tasks.view', 'tasks.create', 'tasks.update', 'announcements.view'],
            'Events' => ['dashboard.view', 'customers.view', 'customers.create', 'customers.update', 'appointments.view', 'appointments.create', 'appointments.update', 'tasks.view', 'tasks.create', 'announcements.view'],
            'Finance' => ['dashboard.view', 'customers.view', 'invoices.view', 'invoices.create', 'invoices.update', 'payments.view', 'payments.create', 'quotes.view', 'reports.view', 'reports.export', 'targets.view', 'announcements.view'],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($perms);
        }
    }
}
