<?php

namespace App\Domain\Organizations\Services;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\PermissionsTeamResolver;

class OrganizationTeamResolver implements PermissionsTeamResolver
{
    private int|string|null $teamId = null;

    public function getPermissionsTeamId(): int|string|null
    {
        if ($this->teamId !== null) {
            return $this->teamId;
        }

        $user = auth()->user();

        if ($user && $user->organization_id) {
            return $user->organization_id;
        }

        return null;
    }

    public function setPermissionsTeamId(Model|string|int|null $id): void
    {
        if ($id instanceof Model) {
            $this->teamId = $id->getKey();
        } else {
            $this->teamId = $id;
        }
    }
}
