<?php

use App\Domain\Properties\Services\PropertyContext;
use App\Domain\Restaurant\Models\MenuCategory;
use App\Domain\Restaurant\Models\MenuItem;
use Illuminate\Support\Str;
use Livewire\Volt\Component;

new class extends Component
{
    public ?int $editingId = null;
    public ?int $menu_category_id = null;
    public string $name = '';
    public string $description = '';
    public string $ingredients = '';
    public string $price = '';
    public bool $is_available = true;
    public int $sort_order = 0;

    public function save(): void
    {
        $this->authorize('menu.manage');

        $validated = $this->validate([
            'menu_category_id' => 'required|exists:menu_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ingredients' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'is_available' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $data = [
            'property_id' => app(PropertyContext::class)->id(),
            'menu_category_id' => $validated['menu_category_id'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?: null,
            'ingredients' => $validated['ingredients'] ?: null,
            'price' => $validated['price'],
            'is_available' => $validated['is_available'],
            'sort_order' => $validated['sort_order'],
        ];

        if ($this->editingId) {
            MenuItem::query()->findOrFail($this->editingId)->update($data);
        } else {
            MenuItem::create($data);
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $this->authorize('menu.manage');
        $item = MenuItem::query()->findOrFail($id);
        $this->editingId = $item->id;
        $this->menu_category_id = $item->menu_category_id;
        $this->name = $item->name;
        $this->description = (string) $item->description;
        $this->ingredients = (string) $item->ingredients;
        $this->price = (string) $item->price;
        $this->is_available = (bool) $item->is_available;
        $this->sort_order = (int) $item->sort_order;
    }

    public function delete(int $id): void
    {
        $this->authorize('menu.manage');
        MenuItem::query()->findOrFail($id)->delete();
        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    public function resetForm(): void
    {
        $this->reset('editingId', 'name', 'description', 'ingredients', 'price', 'menu_category_id');
        $this->is_available = true;
        $this->sort_order = 0;
    }

    public function with(): array
    {
        return [
            'categories' => MenuCategory::query()->orderBy('sort_order')->get(),
            'items' => MenuItem::query()->with('category')->orderBy('sort_order')->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Menu Items</h1>
        <a href="{{ route('menu.index') }}" wire:navigate class="text-sm text-slate-600">Categories</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
            <h2 class="font-semibold">{{ $editingId ? 'Edit item' : 'Add item' }}</h2>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
                <select wire:model="menu_category_id" class="w-full rounded-lg border-slate-200">
                    <option value="">Select...</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('menu_category_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                <input type="text" wire:model="name" class="w-full rounded-lg border-slate-200">
                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea wire:model="description" rows="2" class="w-full rounded-lg border-slate-200"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Ingredients</label>
                <textarea wire:model="ingredients" rows="2" class="w-full rounded-lg border-slate-200"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Price</label>
                    <input type="number" step="0.01" wire:model="price" class="w-full rounded-lg border-slate-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Sort</label>
                    <input type="number" wire:model="sort_order" class="w-full rounded-lg border-slate-200">
                </div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_available" class="rounded border-slate-300"> Available</label>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg">Save</button>
                @if($editingId)<button type="button" wire:click="resetForm" class="px-4 py-2 text-sm text-slate-600">Cancel</button>@endif
            </div>
        </form>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Item</th>
                        <th class="text-left px-4 py-3">Price</th>
                        <th class="text-right px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $item->name }}</div>
                                <div class="text-xs text-slate-500">{{ $item->category?->name }}</div>
                            </td>
                            <td class="px-4 py-3">{{ number_format($item->price, 2) }}</td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button type="button" wire:click="edit({{ $item->id }})" class="text-indigo-600">Edit</button>
                                <button type="button" wire:click="delete({{ $item->id }})" wire:confirm="Delete item?" class="text-red-600">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">No items yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
