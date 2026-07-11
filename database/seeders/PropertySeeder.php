<?php

namespace Database\Seeders;

use App\Domain\Properties\Models\Property;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        $attributes = [
            'name' => 'Montana Resort',
            'timezone' => 'Africa/Nairobi',
            'currency' => 'USD',
            'address' => 'Montana Resort',
            'is_active' => true,
        ];

        // Prefer the existing Montana record; otherwise upgrade the oldest property
        // so demo data stays attached to the single company.
        $montana = Property::where('code', 'MR')->first()
            ?? Property::orderBy('id')->first();

        if ($montana) {
            $montana->update(array_merge($attributes, ['code' => 'MR']));
        } else {
            $montana = Property::create(array_merge($attributes, ['code' => 'MR']));
        }

        Property::query()
            ->where('id', '!=', $montana->id)
            ->update(['is_active' => false]);
    }
}
