<?php

namespace Database\Seeders;

use App\Domain\Properties\Models\Property;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        $properties = [
            [
                'name' => 'Grand Plaza Hotel',
                'code' => 'GPH',
                'timezone' => 'America/New_York',
                'currency' => 'USD',
                'address' => '123 Main Street, New York, NY',
                'tagline' => 'City calm, elevated.',
                'about' => 'Grand Plaza Hotel pairs refined guest rooms with attentive service in the heart of the city. Wake to skyline light, dine on seasonal plates, and unwind in spaces designed for unhurried stays.',
                'phone' => '+1 (212) 555-0148',
                'email' => 'stay@grandplaza.example',
                'check_in_time' => '15:00:00',
                'check_out_time' => '11:00:00',
                'latitude' => 40.7580000,
                'longitude' => -73.9855000,
            ],
            [
                'name' => 'Seaside Resort & Spa',
                'code' => 'SRS',
                'timezone' => 'America/Los_Angeles',
                'currency' => 'USD',
                'address' => '456 Ocean Drive, Miami, FL',
                'tagline' => 'Ocean air, slow mornings.',
                'about' => 'Seaside Resort & Spa is a coastal escape with spa rituals, open-air dining, and rooms that open toward the water.',
                'phone' => '+1 (305) 555-0192',
                'email' => 'hello@seasideresort.example',
                'check_in_time' => '16:00:00',
                'check_out_time' => '11:00:00',
            ],
            [
                'name' => 'Mountain Lodge Retreat',
                'code' => 'MLR',
                'timezone' => 'America/Denver',
                'currency' => 'USD',
                'address' => '789 Alpine Way, Aspen, CO',
                'tagline' => 'Alpine quiet, warm hearths.',
                'about' => 'Mountain Lodge Retreat offers timbered suites, fireplace lounges, and trail access for guests seeking altitude and ease.',
                'phone' => '+1 (970) 555-0166',
                'email' => 'stay@mountainlodge.example',
                'check_in_time' => '15:00:00',
                'check_out_time' => '10:00:00',
            ],
        ];

        foreach ($properties as $property) {
            Property::updateOrCreate(['code' => $property['code']], $property);
        }
    }
}
