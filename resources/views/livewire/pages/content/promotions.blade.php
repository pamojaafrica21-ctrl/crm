<?php

use App\Domain\Content\Models\Promotion;
use App\Domain\Properties\Services\PropertyContext;
use Illuminate\Support\Str;
use Livewire\Volt\Component;

new class extends Component
{
    public ?int $editingId = null;
    public string $title = '';
    public string $description = '';
    public string $image_url = '';
    public string $discount_percent = '';
    public string $starts_at = '';
    public string $ends_at = '';
    public bool $is_active = true;

    public function save(): void
    {
        $this->authorize('content.manage');

        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url|max:500',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        $data = [
            'property_id' => app(PropertyContext::class)->id(),
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'description' => $validated['description'] ?: null,
            'image_url' => $validated['image_url'] ?: null,
            'discount_percent' => $validated['discount_percent'] !== '' ? $validated['discount_percent'] : null,
            'starts_at' => $validated['starts_at'] ?: null,
            'ends_at' => $validated['ends_at'] ?: null,
            'is_active' => $validated['is_active'],
        ];

        if ($this->editingId) {
            Promotion::query()->findOrFail($this->editingId)->update($data);
        } else {
            Promotion::create($data);
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $this->authorize('content.manage');
        $promo = Promotion::query()->findOrFail($id);
        $this->editingId = $promo->id;
        $this->title = $promo->title;
        $this->description = (string) $promo->description;
        $this->image_url = (string) $promo->image_url;
        $this->discount_percent = (string) $promo->discount_percent;
        $this->starts_at = $promo->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->ends_at = $promo->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->is_active = (bool) $promo->is_active;
    }

    public function delete(int $id): void
    {
        $this->authorize('content.manage');
        Promotion::query()->findOrFail($id)->delete();
        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    public function resetForm(): void
    {
        $this->reset('editingId', 'title', 'description', 'image_url', 'discount_percent', 'starts_at', 'ends_at');
        $this->is_active = true;
    }

    public function with(): array
    {
        return ['promotions' => Promotion::query()->latest()->get()];
    }
}; ?>

<div class="space-y-6">
    <h1 class="text-2xl font-bold text-slate-900">Promotions</h1>
    <div class="grid gap-6 lg:grid-cols-2">
        <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
            <h2 class="font-semibold">{{ $editingId ? 'Edit promotion' : 'Add promotion' }}</h2>
            <input type="text" wire:model="title" placeholder="Title" class="w-full rounded-lg border-slate-200">
            @error('title') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            <textarea wire:model="description" rows="3" placeholder="Description" class="w-full rounded-lg border-slate-200"></textarea>
            <input type="url" wire:model="image_url" placeholder="Image URL" class="w-full rounded-lg border-slate-200">
            <input type="number" step="0.01" wire:model="discount_percent" placeholder="Discount %" class="w-full rounded-lg border-slate-200">
            <div class="grid grid-cols-2 gap-3">
                <input type="datetime-local" wire:model="starts_at" class="rounded-lg border-slate-200">
                <input type="datetime-local" wire:model="ends_at" class="rounded-lg border-slate-200">
            </div>
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active" class="rounded border-slate-300"> Active</label>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg">Save</button>
                @if($editingId)<button type="button" wire:click="resetForm" class="px-4 py-2 text-sm text-slate-600">Cancel</button>@endif
            </div>
        </form>
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left px-4 py-3">Title</th><th class="text-right px-4 py-3">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($promotions as $promo)
                        <tr>
                            <td class="px-4 py-3"><div class="font-medium">{{ $promo->title }}</div><div class="text-xs text-slate-500">{{ $promo->is_active ? 'Active' : 'Inactive' }}</div></td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button type="button" wire:click="edit({{ $promo->id }})" class="text-indigo-600">Edit</button>
                                <button type="button" wire:click="delete({{ $promo->id }})" wire:confirm="Delete?" class="text-red-600">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-8 text-center text-slate-500">No promotions.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
