<?php

use App\Domain\Properties\Services\PropertyContext;
use App\Domain\Rooms\Models\Room;
use App\Domain\Rooms\Models\RoomType;
use Illuminate\Support\Str;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $description = '';
    public string $base_price = '';
    public int $capacity_adults = 2;
    public int $capacity_children = 0;
    public string $bed_type = 'King';
    public string $size = '';
    public bool $is_featured = false;
    public bool $is_active = true;
    public int $rooms_count = 0;
    public string $room_number_prefix = '';

    public function save(): void
    {
        $this->authorize('rooms.manage');

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'capacity_adults' => 'required|integer|min:1',
            'capacity_children' => 'required|integer|min:0',
            'bed_type' => 'nullable|string|max:100',
            'size' => 'nullable|string|max:50',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'rooms_count' => 'integer|min:0|max:200',
            'room_number_prefix' => 'nullable|string|max:20',
        ]);

        $propertyId = app(PropertyContext::class)->id();

        $type = RoomType::create([
            'property_id' => $propertyId,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?: null,
            'base_price' => $validated['base_price'],
            'capacity_adults' => $validated['capacity_adults'],
            'capacity_children' => $validated['capacity_children'],
            'bed_type' => $validated['bed_type'] ?: null,
            'size' => $validated['size'] ?: null,
            'is_featured' => $validated['is_featured'],
            'is_active' => $validated['is_active'],
        ]);

        $prefix = $validated['room_number_prefix'] ?: strtoupper(Str::substr(Str::slug($type->name), 0, 3));
        for ($i = 1; $i <= $validated['rooms_count']; $i++) {
            Room::create([
                'property_id' => $propertyId,
                'room_type_id' => $type->id,
                'room_number' => $prefix.'-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'is_active' => true,
            ]);
        }

        $this->redirect(route('rooms.show', $type), navigate: true);
    }
}; ?>

<div class="max-w-2xl space-y-6">
    <h1 class="text-2xl font-bold text-slate-900">Create Room Type</h1>
    <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Name *</label>
            <input type="text" wire:model="name" class="w-full rounded-lg border-slate-200">
            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
            <textarea wire:model="description" rows="3" class="w-full rounded-lg border-slate-200"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Base price *</label>
                <input type="number" step="0.01" wire:model="base_price" class="w-full rounded-lg border-slate-200">
                @error('base_price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Bed type</label>
                <input type="text" wire:model="bed_type" class="w-full rounded-lg border-slate-200">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Adults</label>
                <input type="number" min="1" wire:model="capacity_adults" class="w-full rounded-lg border-slate-200">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Children</label>
                <input type="number" min="0" wire:model="capacity_children" class="w-full rounded-lg border-slate-200">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Size</label>
                <input type="text" wire:model="size" class="w-full rounded-lg border-slate-200" placeholder="32 m²">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Generate rooms</label>
                <input type="number" min="0" wire:model="rooms_count" class="w-full rounded-lg border-slate-200">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Room number prefix</label>
                <input type="text" wire:model="room_number_prefix" class="w-full rounded-lg border-slate-200" placeholder="DLX">
            </div>
        </div>
        <div class="flex gap-6">
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_featured" class="rounded border-slate-300"> Featured</label>
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active" class="rounded border-slate-300"> Active</label>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">Save</button>
            <a href="{{ route('rooms.index') }}" wire:navigate class="px-4 py-2 text-sm text-slate-600">Cancel</a>
        </div>
    </form>
</div>
