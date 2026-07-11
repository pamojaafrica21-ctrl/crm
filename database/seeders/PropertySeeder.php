<?php

namespace Database\Seeders;

use App\Domain\Properties\Models\Property;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        $properties = [
            ['name' => 'Grand Plaza Hotel', 'code' => 'GPH', 'timezone' => 'America/New_York', 'currency' => 'USD', 'address' => '123 Main Street, New York, NY'],
            ['name' => 'Seaside Resort & Spa', 'code' => 'SRS', 'timezone' => 'America/Los_Angeles', 'currency' => 'USD', 'address' => '456 Ocean Drive, Miami, FL'],
            ['name' => 'Mountain Lodge Retreat', 'code' => 'MLR', 'timezone' => 'America/Denver', 'currency' => 'USD', 'address' => '789 Alpine Way, Aspen, CO'],
        ];

        foreach ($properties as $property) {
            Property::firstOrCreate(['code' => $property['code']], $property);
        }
    }
}
