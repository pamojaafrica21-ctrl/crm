<?php

namespace Database\Seeders;

use App\Domain\Organizations\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::whereIn('slug', ['demo-organisation', 'montana-resort'])->first();

        if ($org) {
            $org->update([
                'name' => 'Demo Organisation',
                'slug' => 'demo-organisation',
                'email' => $org->email ?: 'admin@crm.test',
                'status' => Organization::STATUS_ACTIVE,
                'settings' => [
                    'timezone' => 'Africa/Nairobi',
                    'currency' => 'USD',
                ],
            ]);
        } else {
            $org = Organization::create([
                'name' => 'Demo Organisation',
                'slug' => 'demo-organisation',
                'email' => 'admin@crm.test',
                'status' => Organization::STATUS_ACTIVE,
                'settings' => [
                    'timezone' => 'Africa/Nairobi',
                    'currency' => 'USD',
                ],
            ]);
        }

        \App\Domain\Properties\Models\Property::whereNull('organization_id')
            ->update(['organization_id' => $org->id]);
    }
}
