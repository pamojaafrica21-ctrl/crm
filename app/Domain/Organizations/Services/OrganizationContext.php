<?php

namespace App\Domain\Organizations\Services;

use App\Domain\Organizations\Models\Organization;
use App\Models\User;

class OrganizationContext
{
    private ?int $organizationId = null;

    public function set(int $organizationId): void
    {
        $this->organizationId = $organizationId;
        session(['active_organization_id' => $organizationId]);
    }

    public function id(): ?int
    {
        if ($this->organizationId) {
            return $this->organizationId;
        }

        $sessionId = session('active_organization_id');

        return $sessionId ? (int) $sessionId : null;
    }

    public function organization(): ?Organization
    {
        $id = $this->id();

        return $id ? Organization::find($id) : null;
    }

    public function resolveForUser(?User $user): ?Organization
    {
        if (! $user) {
            return null;
        }

        if ($user->is_super_admin) {
            return $this->organization();
        }

        if ($user->organization_id) {
            if ($this->id() !== $user->organization_id) {
                $this->set($user->organization_id);
            }

            return Organization::find($user->organization_id);
        }

        return null;
    }
}
