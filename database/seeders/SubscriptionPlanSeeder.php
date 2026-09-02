<?php

namespace Database\Seeders;

use App\Domain\Billing\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        SubscriptionPlan::updateOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'description' => 'Essential CRM features for small teams.',
                'price' => 49.00,
                'currency' => 'USD',
                'interval' => 'monthly',
                'trial_days' => 14,
                'features' => [
                    'max_properties' => 1,
                    'max_users' => 5,
                    'modules' => ['customers', 'quotes', 'invoices', 'tasks', 'appointments'],
                    'highlights' => [
                        '1 property workspace',
                        'Up to 5 staff users',
                        'Customers, quotes & invoices',
                        'Tasks & appointments',
                    ],
                    'is_featured' => false,
                    'badge' => null,
                    'cta_label' => 'Start free trial',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        SubscriptionPlan::updateOrCreate(
            ['slug' => 'professional'],
            [
                'name' => 'Professional',
                'description' => 'Full CRM suite for growing organisations.',
                'price' => 99.00,
                'currency' => 'USD',
                'interval' => 'monthly',
                'trial_days' => 14,
                'features' => [
                    'max_properties' => 5,
                    'max_users' => 25,
                    'modules' => ['customers', 'quotes', 'invoices', 'tasks', 'appointments', 'reports', 'targets', 'sync'],
                    'highlights' => [
                        'Up to 5 properties',
                        'Up to 25 staff users',
                        'Reports & sales targets',
                        'HMS sync',
                    ],
                    'is_featured' => true,
                    'badge' => 'Most popular',
                    'cta_label' => 'Start free trial',
                ],
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        SubscriptionPlan::updateOrCreate(
            ['slug' => 'enterprise'],
            [
                'name' => 'Enterprise',
                'description' => 'Unlimited access with priority support.',
                'price' => 199.00,
                'currency' => 'USD',
                'interval' => 'monthly',
                'trial_days' => 30,
                'features' => [
                    'max_properties' => null,
                    'max_users' => null,
                    'modules' => ['all'],
                    'highlights' => [
                        'Unlimited properties & users',
                        'All CRM modules',
                        'Priority onboarding support',
                        'Custom trial length available',
                    ],
                    'is_featured' => false,
                    'badge' => null,
                    'cta_label' => 'Start free trial',
                ],
                'is_active' => true,
                'sort_order' => 3,
            ]
        );
    }
}
