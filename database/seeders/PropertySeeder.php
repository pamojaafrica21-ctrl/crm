<?php

namespace Database\Seeders;

use App\Domain\Organizations\Models\Organization;
use App\Domain\Properties\Models\Property;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::whereIn('slug', ['demo-organisation', 'montana-resort'])->first();

        $attributes = [
            'organization_id' => $org?->id,
            'name' => 'Main Property',
            'timezone' => 'Africa/Nairobi',
            'currency' => 'USD',
            'address' => '',
            'is_active' => true,
        ];

        // Prefer the existing main property record; otherwise upgrade the oldest property.
        $property = Property::whereIn('code', ['MAIN', 'MR'])->first()
            ?? Property::orderBy('id')->first();

        if ($property) {
            $property->update(array_merge($attributes, ['code' => 'MAIN']));
        } else {
            $property = Property::create(array_merge($attributes, ['code' => 'MAIN']));
        }

        Property::query()
            ->where('id', '!=', $property->id)
            ->update(['is_active' => false]);
    }
}
