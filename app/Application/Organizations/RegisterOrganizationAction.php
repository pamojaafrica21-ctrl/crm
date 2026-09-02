<?php

namespace App\Application\Organizations;

use App\Domain\Billing\Models\PlatformSetting;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Properties\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterOrganizationAction
{
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $plan = SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->first();
            $trialDays = (int) (PlatformSetting::getValue('default_trial_days') ?? $plan?->trial_days ?? 14);

            $slug = Str::slug($data['organization_name']);
            $baseSlug = $slug;
            $counter = 1;
            while (Organization::where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$counter;
                $counter++;
            }

            $org = Organization::create([
                'name' => $data['organization_name'],
                'slug' => $slug,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'status' => Organization::STATUS_TRIAL,
                'trial_ends_at' => now()->addDays($trialDays),
                'subscription_plan_id' => $plan?->id,
                'settings' => [
                    'timezone' => 'Africa/Nairobi',
                    'currency' => $plan?->currency ?? 'USD',
                ],
            ]);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'organization_id' => $org->id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            $org->update(['owner_id' => $user->id]);

            setPermissionsTeamId($org->id);
            $user->assignRole('Administrator');

            $propertyCode = strtoupper(substr($slug, 0, 6));
            $property = Property::create([
                'organization_id' => $org->id,
                'name' => $org->name,
                'code' => $propertyCode,
                'timezone' => 'Africa/Nairobi',
                'currency' => $plan?->currency ?? 'USD',
                'address' => '',
                'is_active' => true,
            ]);

            $user->properties()->sync([$property->id]);

            return $user;
        });
    }
}
