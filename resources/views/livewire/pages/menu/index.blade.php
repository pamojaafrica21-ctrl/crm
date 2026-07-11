<?php

use App\Domain\Properties\Services\PropertyContext;
use App\Domain\Restaurant\Models\MenuCategory;
use Illuminate\Support\Str;
use Livewire\Volt\Component;

new class extends Component
{
    public ?int $editingId = null;
    public string $name = '';
    public int $sort_order = 0;
    public bool $is_active = true;

    public function save(): void
    {
        $this->authorize('menu.manage');

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $data = [
            'property_id' => app(PropertyContext::class)->id(),
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'sort_order' => $validated['sort_order'],
            'is_active' => $validated['is_active'],
        ];

        if ($this->editingId) {
            MenuCategory::query()->findOrFail($this->editingId)->update($data);
        } else {
            MenuCategory::create($data);
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $this->authorize('menu.manage');
        $category = MenuCategory::query()->findOrFail($id);
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->sort_order = (int) $category->sort_order;
        $this->is_active = (bool) $category->is_active;
    }

    public function delete(int $id): void
    {
        $this->authorize('menu.manage');
        MenuCategory::query()->findOrFail($id)->delete();
        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    public function resetForm(): void
    {
        $this->reset('editingId', 'name');
        $this->sort_order = 0;
        $this->is_active = true;
    }

    public function with(): array
    {
        return [
            'categories' => MenuCategory::query()->withCount('items')->orderBy('sort_order')->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Menu Categories</h1>
        <a href="{{ route('menu.items') }}" wire:navigate class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg">Manage items</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
            <h2 class="font-semibold">{{ $editingId ? 'Edit category' : 'Add category' }}</h2>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                <input type="text" wire:model="name" class="w-full rounded-lg border-slate-200">
                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Sort order</label>
                <input type="number" wire:model="sort_order" class="w-full rounded-lg border-slate-200">
            </div>
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active" class="rounded border-slate-300"> Active</label>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg">Save</button>
                @if($editingId)<button type="button" wire:click="resetForm" class="px-4 py-2 text-sm text-slate-600">Cancel</button>@endif
            </div>
        </form>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Name</th>
                        <th class="text-left px-4 py-3">Items</th>
                        <th class="text-right px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($categories as $category)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $category->name }}</td>
                            <td class="px-4 py-3">{{ $category->items_count }}</td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button type="button" wire:click="edit({{ $category->id }})" class="text-indigo-600">Edit</button>
                                <button type="button" wire:click="delete({{ $category->id }})" wire:confirm="Delete category?" class="text-red-600">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">No categories yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
