<?php

use App\Domain\Properties\Services\PropertyContext;
use App\Domain\Rooms\Models\ExtraService;
use Illuminate\Support\Str;
use Livewire\Volt\Component;

new class extends Component
{
    public ?int $editingId = null;
    public string $name = '';
    public string $description = '';
    public string $price = '';
    public string $pricing_type = 'per_stay';
    public bool $is_active = true;

    public function save(): void
    {
        $this->authorize('rooms.manage');

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'pricing_type' => 'required|in:per_stay,per_night,per_unit',
            'is_active' => 'boolean',
        ]);

        $data = [
            'property_id' => app(PropertyContext::class)->id(),
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?: null,
            'price' => $validated['price'],
            'pricing_type' => $validated['pricing_type'],
            'is_active' => $validated['is_active'],
        ];

        if ($this->editingId) {
            ExtraService::query()->findOrFail($this->editingId)->update($data);
        } else {
            ExtraService::create($data);
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $this->authorize('rooms.manage');
        $extra = ExtraService::query()->findOrFail($id);
        $this->editingId = $extra->id;
        $this->name = $extra->name;
        $this->description = (string) $extra->description;
        $this->price = (string) $extra->price;
        $this->pricing_type = $extra->pricing_type;
        $this->is_active = (bool) $extra->is_active;
    }

    public function delete(int $id): void
    {
        $this->authorize('rooms.manage');
        ExtraService::query()->findOrFail($id)->delete();
        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    public function resetForm(): void
    {
        $this->reset('editingId', 'name', 'description', 'price');
        $this->pricing_type = 'per_stay';
        $this->is_active = true;
    }

    public function with(): array
    {
        return [
            'extras' => ExtraService::query()->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Extra Services</h1>
        <a href="{{ route('rooms.index') }}" wire:navigate class="text-sm text-slate-600">Room types</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
            <h2 class="font-semibold text-slate-900">{{ $editingId ? 'Edit extra' : 'Add extra' }}</h2>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                <input type="text" wire:model="name" class="w-full rounded-lg border-slate-200">
                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea wire:model="description" rows="2" class="w-full rounded-lg border-slate-200"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Price</label>
                    <input type="number" step="0.01" wire:model="price" class="w-full rounded-lg border-slate-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Pricing type</label>
                    <select wire:model="pricing_type" class="w-full rounded-lg border-slate-200">
                        <option value="per_stay">Per stay</option>
                        <option value="per_night">Per night</option>
                        <option value="per_unit">Per unit</option>
                    </select>
                </div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active" class="rounded border-slate-300"> Active</label>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg">Save</button>
                @if($editingId)
                    <button type="button" wire:click="resetForm" class="px-4 py-2 text-sm text-slate-600">Cancel</button>
                @endif
            </div>
        </form>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Name</th>
                        <th class="text-left px-4 py-3">Price</th>
                        <th class="text-right px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($extras as $extra)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $extra->name }}</div>
                                <div class="text-xs text-slate-500">{{ $extra->pricing_type }}</div>
                            </td>
                            <td class="px-4 py-3">{{ number_format($extra->price, 2) }}</td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button type="button" wire:click="edit({{ $extra->id }})" class="text-indigo-600">Edit</button>
                                <button type="button" wire:click="delete({{ $extra->id }})" wire:confirm="Delete this extra?" class="text-red-600">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">No extras yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
