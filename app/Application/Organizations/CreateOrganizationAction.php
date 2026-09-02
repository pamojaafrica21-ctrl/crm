<?php

namespace App\Application\Organizations;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Properties\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateOrganizationAction
{
    public function execute(array $data): Organization
    {
        return DB::transaction(function () use ($data) {
            $plan = isset($data['subscription_plan_id'])
                ? SubscriptionPlan::find($data['subscription_plan_id'])
                : SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->first();

            $slug = Str::slug($data['name']);
            $baseSlug = $slug;
            $counter = 1;
            while (Organization::where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$counter;
                $counter++;
            }

            $trialDays = $data['trial_days'] ?? $plan?->trial_days ?? 14;

            $org = Organization::create([
                'name' => $data['name'],
                'slug' => $slug,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => $data['status'] ?? Organization::STATUS_TRIAL,
                'trial_ends_at' => now()->addDays((int) $trialDays),
                'subscription_plan_id' => $plan?->id,
                'settings' => [
                    'timezone' => $data['timezone'] ?? 'Africa/Nairobi',
                    'currency' => $plan?->currency ?? 'USD',
                ],
            ]);

            if (! empty($data['owner_name']) && ! empty($data['owner_email'])) {
                $user = User::create([
                    'name' => $data['owner_name'],
                    'email' => $data['owner_email'],
                    'password' => Hash::make($data['owner_password'] ?? 'password'),
                    'phone' => $data['phone'] ?? null,
                    'organization_id' => $org->id,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]);

                setPermissionsTeamId($org->id);
                $user->assignRole('Administrator');

                $org->update(['owner_id' => $user->id]);

                Property::create([
                    'organization_id' => $org->id,
                    'name' => $org->name,
                    'code' => strtoupper(substr($slug, 0, 6)),
                    'timezone' => $data['timezone'] ?? 'Africa/Nairobi',
                    'currency' => $plan?->currency ?? 'USD',
                    'address' => $data['address'] ?? '',
                    'is_active' => true,
                ])->users()->sync([$user->id]);
            }

            return $org;
        });
    }
}
