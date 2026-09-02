<?php

use App\Application\Billing\StripePlanSyncService;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Organizations\Models\Organization;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    public bool $showForm = false;
    public ?int $editingId = null;
    public ?int $viewingId = null;

    public string $name = '';
    public string $description = '';
    public string $price = '0';
    public string $currency = 'USD';
    public string $interval = 'monthly';
    public int $trial_days = 14;
    public bool $is_active = true;
    public int $sort_order = 0;
    public string $max_properties = '';
    public string $max_users = '';
    public string $highlights_text = '';
    public string $modules_text = '';
    public bool $is_featured = false;
    public string $badge = '';
    public string $cta_label = 'Start free trial';

    public function with(): array
    {
        return [
            'plans' => SubscriptionPlan::orderBy('sort_order')->get(),
            'viewingPlan' => $this->viewingId
                ? SubscriptionPlan::withCount('organizations')->find($this->viewingId)
                : null,
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->viewingId = null;
        $this->showForm = true;
    }

    public function view(int $id): void
    {
        $this->viewingId = $id;
        $this->showForm = false;
    }

    public function edit(int $id): void
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $this->editingId = $plan->id;
        $this->viewingId = null;
        $this->name = $plan->name;
        $this->description = $plan->description ?? '';
        $this->price = (string) $plan->price;
        $this->currency = $plan->currency;
        $this->interval = $plan->interval;
        $this->trial_days = $plan->trial_days;
        $this->is_active = $plan->is_active;
        $this->sort_order = $plan->sort_order;
        $this->max_properties = (string) ($plan->featureLimit('max_properties') ?? '');
        $this->max_users = (string) ($plan->featureLimit('max_users') ?? '');
        $this->highlights_text = implode("\n", $plan->highlightItems());
        $this->modules_text = implode(', ', $plan->moduleList());
        $this->is_featured = $plan->isFeatured();
        $this->badge = $plan->badge() ?? '';
        $this->cta_label = $plan->ctaLabel();
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'interval' => ['required', 'in:monthly,yearly'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'highlights_text' => ['nullable', 'string'],
            'modules_text' => ['nullable', 'string'],
            'is_featured' => ['boolean'],
            'badge' => ['nullable', 'string', 'max:50'],
            'cta_label' => ['required', 'string', 'max:80'],
        ]);

        $highlights = collect(preg_split('/\r\n|\r|\n/', $this->highlights_text) ?: [])
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $modules = collect(explode(',', $this->modules_text))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();

        $features = [
            'max_properties' => $this->max_properties !== '' ? (int) $this->max_properties : null,
            'max_users' => $this->max_users !== '' ? (int) $this->max_users : null,
            'highlights' => $highlights,
            'modules' => $modules,
            'is_featured' => $this->is_featured,
            'badge' => $this->badge ?: null,
            'cta_label' => $this->cta_label,
        ];

        unset($validated['highlights_text'], $validated['modules_text'], $validated['is_featured'], $validated['badge'], $validated['cta_label']);

        if ($this->editingId) {
            $plan = SubscriptionPlan::findOrFail($this->editingId);
            // Reset Stripe price when amount/currency/interval change so sync can recreate.
            if (
                (string) $plan->price !== (string) $validated['price']
                || strtoupper($plan->currency) !== strtoupper($validated['currency'])
                || $plan->interval !== $validated['interval']
            ) {
                $plan->stripe_price_id = null;
            }
            $plan->update(array_merge($validated, ['features' => $features]));
        } else {
            $plan = SubscriptionPlan::create(array_merge($validated, ['features' => $features]));
        }

        try {
            app(StripePlanSyncService::class)->syncPlan($plan->fresh());
        } catch (\Throwable) {
            // Stripe sync is optional until keys are configured.
        }

        $this->showForm = false;
        $this->viewingId = $plan->id;
        $this->resetForm();
        session()->flash('status', 'Plan saved. Homepage and billing will show these updates.');
    }

    public function deletePlan(int $id): void
    {
        $plan = SubscriptionPlan::findOrFail($id);

        Organization::where('subscription_plan_id', $plan->id)->update([
            'subscription_plan_id' => null,
        ]);

        $plan->delete();

        if ($this->viewingId === $id) {
            $this->viewingId = null;
        }

        if ($this->editingId === $id) {
            $this->showForm = false;
            $this->resetForm();
        }

        session()->flash('status', 'Plan deleted successfully.');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->price = '0';
        $this->currency = 'USD';
        $this->interval = 'monthly';
        $this->trial_days = 14;
        $this->is_active = true;
        $this->sort_order = 0;
        $this->max_properties = '';
        $this->max_users = '';
        $this->highlights_text = '';
        $this->modules_text = '';
        $this->is_featured = false;
        $this->badge = '';
        $this->cta_label = 'Start free trial';
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Subscription Plans</h1>
            <p class="mt-1 text-sm text-slate-500">Add, edit, or delete plans. Changes appear on the homepage and billing page immediately.</p>
        </div>
        <button wire:click="openCreate" class="px-4 py-2 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700">
            Add Plan
        </button>
    </div>

    @if (session('status'))
        <div class="bg-green-50 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('status') }}</div>
    @endif

    @if ($showForm)
        <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-5">
            <h2 class="font-semibold text-slate-900">{{ $editingId ? 'Edit Plan in Detail' : 'New Plan' }}</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Name</label>
                    <input type="text" wire:model="name" class="mt-1 block w-full rounded-lg border-slate-200 text-sm" required>
                    @error('name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Price</label>
                    <input type="number" step="0.01" wire:model="price" class="mt-1 block w-full rounded-lg border-slate-200 text-sm" required>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Description</label>
                <textarea wire:model="description" rows="2" class="mt-1 block w-full rounded-lg border-slate-200 text-sm" placeholder="Shown on homepage and billing"></textarea>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Currency</label>
                    <input type="text" wire:model="currency" maxlength="3" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Interval</label>
                    <select wire:model="interval" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Trial Days</label>
                    <input type="number" wire:model="trial_days" min="0" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Sort Order</label>
                    <input type="number" wire:model="sort_order" min="0" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Max Properties</label>
                    <input type="number" wire:model="max_properties" placeholder="Unlimited" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Max Users</label>
                    <input type="number" wire:model="max_users" placeholder="Unlimited" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Homepage feature bullets</label>
                <textarea wire:model="highlights_text" rows="5" class="mt-1 block w-full rounded-lg border-slate-200 text-sm" placeholder="One benefit per line&#10;Customer & guest CRM&#10;Quotes & invoices&#10;Team tasks"></textarea>
                <p class="mt-1 text-xs text-slate-500">These appear on the public homepage pricing cards.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Included modules</label>
                <input type="text" wire:model="modules_text" class="mt-1 block w-full rounded-lg border-slate-200 text-sm" placeholder="customers, quotes, invoices, tasks">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Badge (optional)</label>
                    <input type="text" wire:model="badge" class="mt-1 block w-full rounded-lg border-slate-200 text-sm" placeholder="Most popular">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">CTA button label</label>
                    <input type="text" wire:model="cta_label" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-6">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" wire:model="is_active" class="rounded border-slate-300"> Active (visible on homepage & billing)
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" wire:model="is_featured" class="rounded border-slate-300"> Featured on homepage
                </label>
            </div>

            <div class="flex gap-3 pt-1">
                <button type="submit" class="px-4 py-2 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700">Save Plan</button>
                <button type="button" wire:click="$set('showForm', false)" class="px-4 py-2 text-sm text-slate-600">Cancel</button>
            </div>
        </form>
    @endif

    @if ($viewingPlan && ! $showForm)
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">{{ $viewingPlan->name }}</h2>
                    <p class="text-sm text-slate-500 mt-1">{{ $viewingPlan->description }}</p>
                </div>
                <button wire:click="$set('viewingId', null)" class="text-sm text-slate-500 hover:text-slate-700">Close</button>
            </div>
            <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 text-sm">
                <div><dt class="text-slate-500">Price</dt><dd class="font-medium">{{ $viewingPlan->formattedPrice() }}/{{ $viewingPlan->interval }}</dd></div>
                <div><dt class="text-slate-500">Trial</dt><dd class="font-medium">{{ $viewingPlan->trial_days }} days</dd></div>
                <div><dt class="text-slate-500">Status</dt><dd class="font-medium">{{ $viewingPlan->is_active ? 'Active' : 'Inactive' }}</dd></div>
                <div><dt class="text-slate-500">Organisations</dt><dd class="font-medium">{{ $viewingPlan->organizations_count }}</dd></div>
                <div><dt class="text-slate-500">Featured</dt><dd class="font-medium">{{ $viewingPlan->isFeatured() ? 'Yes' : 'No' }}</dd></div>
                <div><dt class="text-slate-500">Badge</dt><dd class="font-medium">{{ $viewingPlan->badge() ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Max Properties</dt><dd class="font-medium">{{ $viewingPlan->featureLimit('max_properties') ?? 'Unlimited' }}</dd></div>
                <div><dt class="text-slate-500">Max Users</dt><dd class="font-medium">{{ $viewingPlan->featureLimit('max_users') ?? 'Unlimited' }}</dd></div>
            </dl>
            @if ($viewingPlan->highlightItems())
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-slate-700 mb-2">Homepage bullets</h3>
                    <ul class="text-sm text-slate-600 space-y-1 list-disc list-inside">
                        @foreach ($viewingPlan->highlightItems() as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="flex gap-3 mt-6">
                <button wire:click="edit({{ $viewingPlan->id }})" class="px-4 py-2 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700">Edit</button>
                <button
                    wire:click="deletePlan({{ $viewingPlan->id }})"
                    wire:confirm="Delete {{ $viewingPlan->name }}? Organisations on this plan will be unassigned."
                    class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">
                    Delete
                </button>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach ($plans as $plan)
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <div class="flex justify-between items-start gap-2">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $plan->name }}</h3>
                        @if ($plan->badge())
                            <span class="inline-block mt-1 text-xs px-2 py-0.5 bg-violet-100 text-violet-700 rounded-full">{{ $plan->badge() }}</span>
                        @endif
                        <p class="text-2xl font-bold text-violet-600 mt-2">{{ $plan->formattedPrice() }}<span class="text-sm text-slate-500 font-normal">/{{ $plan->interval }}</span></p>
                    </div>
                    @if ($plan->is_active)
                        <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full">Active</span>
                    @else
                        <span class="text-xs px-2 py-1 bg-slate-100 text-slate-600 rounded-full">Inactive</span>
                    @endif
                </div>
                <p class="text-sm text-slate-500 mt-2">{{ $plan->description }}</p>
                <p class="text-sm text-slate-600 mt-3">{{ $plan->trial_days }} day free trial</p>
                <div class="mt-4 flex items-center gap-3 text-sm">
                    <button wire:click="view({{ $plan->id }})" class="text-slate-600 hover:text-slate-900">View</button>
                    <button wire:click="edit({{ $plan->id }})" class="text-violet-600 hover:text-violet-700">Edit</button>
                    <button
                        wire:click="deletePlan({{ $plan->id }})"
                        wire:confirm="Delete {{ $plan->name }}?"
                        class="text-red-600 hover:text-red-700">
                        Delete
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</div>
