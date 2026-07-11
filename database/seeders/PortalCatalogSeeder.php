<?php

namespace Database\Seeders;

use App\Domain\Content\Models\Faq;
use App\Domain\Content\Models\Promotion;
use App\Domain\Properties\Models\Property;
use App\Domain\Restaurant\Models\MenuCategory;
use App\Domain\Restaurant\Models\MenuItem;
use App\Domain\Restaurant\Models\RestaurantTable;
use App\Domain\Rooms\Models\Amenity;
use App\Domain\Rooms\Models\ExtraService;
use App\Domain\Rooms\Models\Room;
use App\Domain\Rooms\Models\RoomType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PortalCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $property = Property::query()->orderBy('id')->first();

        if (! $property) {
            return;
        }

        $amenities = [
            ['name' => 'Wi-Fi', 'icon' => 'wifi'],
            ['name' => 'Air conditioning', 'icon' => 'ac'],
            ['name' => 'Mini bar', 'icon' => 'bar'],
            ['name' => 'Ocean view', 'icon' => 'view'],
            ['name' => 'Bathtub', 'icon' => 'bath'],
            ['name' => 'Workspace', 'icon' => 'desk'],
        ];

        $amenityIds = [];
        foreach ($amenities as $amenity) {
            $model = Amenity::withoutGlobalScopes()->firstOrCreate(
                ['property_id' => $property->id, 'name' => $amenity['name']],
                ['icon' => $amenity['icon']]
            );
            $amenityIds[] = $model->id;
        }

        $roomTypes = [
            [
                'name' => 'Deluxe King',
                'description' => 'A calm king room with soft light, layered textiles, and a generous workspace.',
                'base_price' => 220,
                'capacity_adults' => 2,
                'capacity_children' => 1,
                'bed_type' => 'King',
                'size' => '32 m²',
                'is_featured' => true,
                'rooms' => 5,
            ],
            [
                'name' => 'Twin Garden',
                'description' => 'Twin beds facing garden greenery — ideal for friends or family traveling together.',
                'base_price' => 190,
                'capacity_adults' => 2,
                'capacity_children' => 2,
                'bed_type' => 'Twin',
                'size' => '30 m²',
                'is_featured' => true,
                'rooms' => 4,
            ],
            [
                'name' => 'Executive Suite',
                'description' => 'A suite with separate lounge, soaking tub, and elevated amenities for longer stays.',
                'base_price' => 380,
                'capacity_adults' => 3,
                'capacity_children' => 2,
                'bed_type' => 'King',
                'size' => '55 m²',
                'is_featured' => true,
                'rooms' => 3,
            ],
        ];

        foreach ($roomTypes as $index => $data) {
            $type = RoomType::withoutGlobalScopes()->firstOrCreate(
                ['property_id' => $property->id, 'slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'base_price' => $data['base_price'],
                    'capacity_adults' => $data['capacity_adults'],
                    'capacity_children' => $data['capacity_children'],
                    'bed_type' => $data['bed_type'],
                    'size' => $data['size'],
                    'is_featured' => $data['is_featured'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );

            $type->amenities()->syncWithoutDetaching($amenityIds);

            $prefix = strtoupper(Str::substr(Str::slug($type->name), 0, 3));
            for ($i = 1; $i <= $data['rooms']; $i++) {
                Room::withoutGlobalScopes()->firstOrCreate(
                    [
                        'property_id' => $property->id,
                        'room_type_id' => $type->id,
                        'room_number' => $prefix.'-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                    ],
                    [
                        'floor' => (string) (floor(($i - 1) / 5) + 2),
                        'is_active' => true,
                    ]
                );
            }
        }

        $extras = [
            ['name' => 'Airport transfer', 'price' => 45, 'pricing_type' => 'per_unit', 'description' => 'One-way private transfer'],
            ['name' => 'Breakfast for two', 'price' => 35, 'pricing_type' => 'per_night', 'description' => 'Daily breakfast buffet'],
            ['name' => 'Late checkout', 'price' => 50, 'pricing_type' => 'per_stay', 'description' => 'Checkout until 4pm'],
            ['name' => 'Champagne welcome', 'price' => 65, 'pricing_type' => 'per_stay', 'description' => 'Bottle on arrival'],
        ];

        foreach ($extras as $extra) {
            ExtraService::withoutGlobalScopes()->firstOrCreate(
                ['property_id' => $property->id, 'slug' => Str::slug($extra['name'])],
                [
                    'name' => $extra['name'],
                    'description' => $extra['description'],
                    'price' => $extra['price'],
                    'pricing_type' => $extra['pricing_type'],
                    'is_active' => true,
                ]
            );
        }

        $menu = [
            'Starters' => [
                ['name' => 'Heirloom tomato salad', 'price' => 14, 'description' => 'Basil oil, burrata, sea salt'],
                ['name' => 'Seared scallops', 'price' => 22, 'description' => 'Citrus beurre blanc'],
            ],
            'Mains' => [
                ['name' => 'Grilled catch of the day', 'price' => 34, 'description' => 'Seasonal vegetables, lemon'],
                ['name' => 'Herb roasted chicken', 'price' => 28, 'description' => 'Pan jus, soft herbs'],
                ['name' => 'Wild mushroom risotto', 'price' => 26, 'description' => 'Parmesan, truffle oil'],
            ],
            'Desserts' => [
                ['name' => 'Dark chocolate tart', 'price' => 12, 'description' => 'Sea salt caramel'],
                ['name' => 'Citrus panna cotta', 'price' => 11, 'description' => 'Seasonal fruit'],
            ],
        ];

        $sort = 1;
        foreach ($menu as $categoryName => $items) {
            $category = MenuCategory::withoutGlobalScopes()->firstOrCreate(
                ['property_id' => $property->id, 'slug' => Str::slug($categoryName)],
                [
                    'name' => $categoryName,
                    'sort_order' => $sort++,
                    'is_active' => true,
                ]
            );

            foreach ($items as $itemSort => $item) {
                MenuItem::withoutGlobalScopes()->firstOrCreate(
                    ['property_id' => $property->id, 'slug' => Str::slug($item['name'])],
                    [
                        'menu_category_id' => $category->id,
                        'name' => $item['name'],
                        'description' => $item['description'],
                        'price' => $item['price'],
                        'is_available' => true,
                        'sort_order' => $itemSort + 1,
                    ]
                );
            }
        }

        foreach (['T1' => 2, 'T2' => 2, 'T4' => 4, 'T6' => 6, 'T8' => 8] as $name => $capacity) {
            RestaurantTable::withoutGlobalScopes()->firstOrCreate(
                ['property_id' => $property->id, 'name' => $name],
                ['capacity' => $capacity, 'is_active' => true]
            );
        }

        $faqs = [
            ['question' => 'What are the check-in and check-out times?', 'answer' => 'Check-in is from 3:00 PM and check-out is by 11:00 AM. Early check-in and late checkout may be available on request.', 'category' => 'Stay'],
            ['question' => 'Is breakfast included?', 'answer' => 'Breakfast can be added as an extra during booking, or ordered from the restaurant during your stay.', 'category' => 'Dining'],
            ['question' => 'Do you offer parking?', 'answer' => 'On-site parking is available for registered guests. Please mention your vehicle when you arrive.', 'category' => 'Stay'],
            ['question' => 'Can I cancel my booking online?', 'answer' => 'Yes. Sign in to My Stay, open your booking, and cancel if it is still pending or confirmed.', 'category' => 'Bookings'],
        ];

        foreach ($faqs as $index => $faq) {
            Faq::withoutGlobalScopes()->firstOrCreate(
                ['property_id' => $property->id, 'question' => $faq['question']],
                [
                    'answer' => $faq['answer'],
                    'category' => $faq['category'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }

        $promotions = [
            [
                'title' => 'Weekday calm',
                'description' => 'Enjoy quieter midweek rates and complimentary late checkout on stays of two nights or more.',
                'discount_percent' => 15,
            ],
            [
                'title' => 'Dining credit',
                'description' => 'Book a suite and receive a dining credit to use at the restaurant during your stay.',
                'discount_percent' => 10,
            ],
        ];

        foreach ($promotions as $promo) {
            Promotion::withoutGlobalScopes()->firstOrCreate(
                ['property_id' => $property->id, 'slug' => Str::slug($promo['title'])],
                [
                    'title' => $promo['title'],
                    'description' => $promo['description'],
                    'discount_percent' => $promo['discount_percent'],
                    'starts_at' => now()->subDay(),
                    'ends_at' => now()->addMonths(3),
                    'is_active' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1200&q=80',
                ]
            );
        }
    }
}
