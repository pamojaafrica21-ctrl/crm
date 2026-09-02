<?php

namespace App\Application\Organizations;

use App\Domain\Organizations\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteOrganizationAction
{
    public function execute(Organization $organization): void
    {
        DB::transaction(function () use ($organization) {
            setPermissionsTeamId($organization->id);

            $organization->users()->each(function (User $user) {
                $user->roles()->detach();
                $user->permissions()->detach();
                $user->properties()->detach();
                $user->delete();
            });

            $organization->properties()->update([
                'organization_id' => null,
                'is_active' => false,
            ]);

            $organization->delete();
        });
    }
}
