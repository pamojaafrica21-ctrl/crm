<?php

use App\Domain\Billing\Models\PlatformSetting;
use App\Domain\Billing\Services\HomepageContentService;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    use WithFileUploads;

    public string $eyebrow = '';
    public string $headline = '';
    public string $subheadline = '';
    public string $cta_label = 'Start free trial';
    public int $default_trial_days = 14;

    /** @var array<int, array{title:string,description:string,image:string}> */
    public array $features = [];

    /** @var array<int, mixed> */
    public array $featureUploads = [];

    public function mount(HomepageContentService $homepage): void
    {
        $hero = $homepage->hero();
        $this->eyebrow = $hero['eyebrow'];
        $this->headline = $hero['headline'];
        $this->subheadline = $hero['subheadline'];
        $this->cta_label = $hero['cta_label'];
        $this->default_trial_days = $homepage->trialDays();
        $this->features = $homepage->features();
        $this->featureUploads = array_fill(0, count($this->features), null);
    }

    public function addFeature(): void
    {
        $this->features[] = [
            'title' => '',
            'description' => '',
            'image' => '/images/marketing/crm-dashboard.png',
        ];
        $this->featureUploads[] = null;
    }

    public function removeFeature(int $index): void
    {
        unset($this->features[$index], $this->featureUploads[$index]);
        $this->features = array_values($this->features);
        $this->featureUploads = array_values($this->featureUploads);
    }

    public function save(HomepageContentService $homepage): void
    {
        $this->validate([
            'eyebrow' => ['required', 'string', 'max:120'],
            'headline' => ['required', 'string', 'max:255'],
            'subheadline' => ['required', 'string', 'max:500'],
            'cta_label' => ['required', 'string', 'max:80'],
            'default_trial_days' => ['required', 'integer', 'min:1', 'max:365'],
            'features' => ['array', 'min:1'],
            'features.*.title' => ['required', 'string', 'max:120'],
            'features.*.description' => ['required', 'string', 'max:500'],
            'features.*.image' => ['required', 'string', 'max:500'],
            'featureUploads.*' => ['nullable', 'image', 'max:4096'],
        ]);

        foreach ($this->featureUploads as $index => $upload) {
            if ($upload) {
                $this->features[$index]['image'] = $homepage->storeFeatureImage($upload, $index);
            }
        }

        $homepage->saveHero([
            'eyebrow' => $this->eyebrow,
            'headline' => $this->headline,
            'subheadline' => $this->subheadline,
            'cta_label' => $this->cta_label,
        ]);

        PlatformSetting::setValue('default_trial_days', (string) $this->default_trial_days);
        $homepage->saveFeatures($this->features);

        $this->featureUploads = array_fill(0, count($this->features), null);

        session()->flash('status', 'Homepage content saved. Visit the public homepage to preview.');
    }
}; ?>

<div class="space-y-6 max-w-3xl">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Homepage</h1>
        <p class="mt-1 text-sm text-slate-500">
            Edit the public landing page. Trial days and plans also sync from
            <a href="{{ route('admin.plans.index') }}" wire:navigate class="text-violet-600 hover:underline">Plans</a>
            and the free-trial setting below.
        </p>
    </div>

    @if (session('status'))
        <div class="bg-green-50 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('status') }}</div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
            <h2 class="font-semibold text-slate-900">Hero & free trial CTA</h2>
            <div>
                <label class="block text-sm font-medium text-slate-700">Eyebrow</label>
                <input type="text" wire:model="eyebrow" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Headline</label>
                <input type="text" wire:model="headline" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Supporting text</label>
                <textarea wire:model="subheadline" rows="3" class="mt-1 block w-full rounded-lg border-slate-200 text-sm"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">CTA label</label>
                    <input type="text" wire:model="cta_label" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Default free trial days</label>
                    <input type="number" wire:model="default_trial_days" min="1" max="365" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                    <p class="mt-1 text-xs text-slate-500">Shown on homepage CTAs and used for new organisation signups.</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-900">Feature sections & screenshots</h2>
                <button type="button" wire:click="addFeature" class="text-sm text-violet-600 hover:text-violet-700">Add feature</button>
            </div>

            @foreach ($features as $index => $feature)
                <div class="border border-slate-100 rounded-lg p-4 space-y-3" wire:key="feature-{{ $index }}">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-slate-700">Feature {{ $index + 1 }}</span>
                        @if (count($features) > 1)
                            <button type="button" wire:click="removeFeature({{ $index }})" class="text-sm text-red-600">Remove</button>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Title</label>
                        <input type="text" wire:model="features.{{ $index }}.title" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Description</label>
                        <textarea wire:model="features.{{ $index }}.description" rows="2" class="mt-1 block w-full rounded-lg border-slate-200 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Screenshot / image</label>
                        @if (! empty($feature['image']))
                            <img src="{{ $feature['image'] }}" alt="" class="mt-2 mb-2 h-28 rounded-lg border border-slate-100 object-cover">
                        @endif
                        <input type="file" wire:model="featureUploads.{{ $index }}" accept="image/*" class="mt-1 block w-full text-sm">
                        <input type="hidden" wire:model="features.{{ $index }}.image">
                        <div wire:loading wire:target="featureUploads.{{ $index }}" class="text-xs text-slate-500 mt-1">Uploading…</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex gap-3 items-center">
            <button type="submit" class="px-4 py-2 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700">
                Save Homepage
            </button>
            <a href="{{ route('home') }}" target="_blank" class="text-sm text-slate-600 hover:text-slate-900">Open public homepage ↗</a>
        </div>
    </form>
</div>
