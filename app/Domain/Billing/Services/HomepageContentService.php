<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\PlatformSetting;
use Illuminate\Support\Facades\Storage;

class HomepageContentService
{
    public function trialDays(): int
    {
        return (int) PlatformSetting::getValue('default_trial_days', 14);
    }

    public function hero(): array
    {
        return [
            'eyebrow' => PlatformSetting::getValue('homepage_eyebrow', 'CRM for every industry') ?? 'CRM for every industry',
            'headline' => PlatformSetting::getValue('homepage_headline', 'Run your business from one CRM.') ?? 'Run your business from one CRM.',
            'subheadline' => PlatformSetting::getValue(
                'homepage_subheadline',
                'Manage customers, quotes, invoices, tasks, appointments, and detailed reports — built for teams in every industry, with billing that scales as your organisation grows.'
            ) ?? 'Manage customers, quotes, invoices, tasks, appointments, and detailed reports — built for teams in every industry, with billing that scales as your organisation grows.',
            'cta_label' => PlatformSetting::getValue('homepage_cta_label', 'Start free trial') ?? 'Start free trial',
        ];
    }

    public function features(): array
    {
        $stored = PlatformSetting::getValue('homepage_features');

        if (is_string($stored) && $stored !== '') {
            $decoded = json_decode($stored, true);
            if (is_array($decoded) && $decoded !== []) {
                return $decoded;
            }
        }

        return $this->defaultFeatures();
    }

    public function defaultFeatures(): array
    {
        return [
            [
                'title' => 'Performance dashboard',
                'description' => 'Track revenue, outstanding balances, pipeline activity, and team performance in one place.',
                'image' => '/images/marketing/crm-dashboard.png',
            ],
            [
                'title' => 'Customer CRM',
                'description' => 'Keep customer profiles, segments, notes, and communication history organised by location or team.',
                'image' => '/images/marketing/crm-customers.png',
            ],
            [
                'title' => 'Quotes & invoices',
                'description' => 'Convert quotes to invoices, record payments, and monitor sales pipeline status.',
                'image' => '/images/marketing/crm-sales.png',
            ],
            [
                'title' => 'Reports & insights',
                'description' => 'Open dedicated sales, finance, customer, operations, and performance reports — with charts, aging breakdowns, staff attribution, and CSV export.',
                'image' => '/images/marketing/crm-reports-detail.png',
            ],
        ];
    }

    public function saveHero(array $data): void
    {
        PlatformSetting::setValue('homepage_eyebrow', $data['eyebrow'] ?? '');
        PlatformSetting::setValue('homepage_headline', $data['headline'] ?? '');
        PlatformSetting::setValue('homepage_subheadline', $data['subheadline'] ?? '');
        PlatformSetting::setValue('homepage_cta_label', $data['cta_label'] ?? 'Start free trial');
    }

    public function saveFeatures(array $features): void
    {
        PlatformSetting::setValue('homepage_features', json_encode(array_values($features)));
    }

    public function storeFeatureImage($file, int $index): string
    {
        $path = $file->storeAs('homepage', 'feature-'.$index.'-'.time().'.'.$file->getClientOriginalExtension(), 'public');

        return Storage::disk('public')->url($path);
    }
}
