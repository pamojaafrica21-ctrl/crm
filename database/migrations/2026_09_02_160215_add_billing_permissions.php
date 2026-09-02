<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['billing.view', 'billing.manage'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $admin = Role::findByName('Administrator');
        $admin->givePermissionTo(['billing.view', 'billing.manage']);

        $finance = Role::findByName('Finance');
        $finance->givePermissionTo(['billing.view', 'billing.manage']);

        // Refresh direct permission copies for staff who mirror their role.
        $manager = Role::findByName('Manager');
        $finance = Role::findByName('Finance');

        foreach (\App\Models\User::role('Manager')->get() as $user) {
            setPermissionsTeamId($user->organization_id);
            if ($user->getDirectPermissions()->isNotEmpty()) {
                $user->givePermissionTo('billing.view');
            }
        }

        foreach (\App\Models\User::role('Finance')->get() as $user) {
            setPermissionsTeamId($user->organization_id);
            if ($user->getDirectPermissions()->isNotEmpty()) {
                $user->givePermissionTo(['billing.view', 'billing.manage']);
            }
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['billing.view', 'billing.manage'] as $name) {
            Permission::where('name', $name)->delete();
        }
    }
};
