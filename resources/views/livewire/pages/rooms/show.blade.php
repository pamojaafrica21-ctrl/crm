<?php

use App\Domain\Properties\Services\PropertyContext;
use App\Domain\Rooms\Models\Amenity;
use App\Domain\Rooms\Models\Room;
use App\Domain\Rooms\Models\RoomType;
use Livewire\Volt\Component;

new class extends Component
{
    public RoomType $roomType;

    public string $name = '';
    public string $description = '';
    public string $base_price = '';
    public int $capacity_adults = 2;
    public int $capacity_children = 0;
    public string $bed_type = '';
    public string $size = '';
    public bool $is_featured = false;
    public bool $is_active = true;
    public array $amenity_ids = [];

    public string $new_room_number = '';
    public string $new_floor = '';

    public function mount(RoomType $roomType): void
    {
        $this->authorize('rooms.manage');
        $this->roomType = $roomType->load('amenities');
        $this->name = $roomType->name;
        $this->description = (string) $roomType->description;
        $this->base_price = (string) $roomType->base_price;
        $this->capacity_adults = (int) $roomType->capacity_adults;
        $this->capacity_children = (int) $roomType->capacity_children;
        $this->bed_type = (string) $roomType->bed_type;
        $this->size = (string) $roomType->size;
        $this->is_featured = (bool) $roomType->is_featured;
        $this->is_active = (bool) $roomType->is_active;
        $this->amenity_ids = $roomType->amenities->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

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
            'amenity_ids' => 'array',
        ]);

        $this->roomType->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'base_price' => $validated['base_price'],
            'capacity_adults' => $validated['capacity_adults'],
            'capacity_children' => $validated['capacity_children'],
            'bed_type' => $validated['bed_type'] ?: null,
            'size' => $validated['size'] ?: null,
            'is_featured' => $validated['is_featured'],
            'is_active' => $validated['is_active'],
        ]);

        $this->roomType->amenities()->sync($this->amenity_ids);
        session()->flash('status', 'Room type updated.');
    }

    public function addRoom(): void
    {
        $this->authorize('rooms.manage');

        $validated = $this->validate([
            'new_room_number' => 'required|string|max:50',
            'new_floor' => 'nullable|string|max:20',
        ]);

        Room::create([
            'property_id' => app(PropertyContext::class)->id(),
            'room_type_id' => $this->roomType->id,
            'room_number' => $validated['new_room_number'],
            'floor' => $validated['new_floor'] ?: null,
            'is_active' => true,
        ]);

        $this->reset('new_room_number', 'new_floor');
    }

    public function toggleRoom(int $roomId): void
    {
        $this->authorize('rooms.manage');
        $room = Room::query()->where('room_type_id', $this->roomType->id)->findOrFail($roomId);
        $room->update(['is_active' => ! $room->is_active]);
    }

    public function with(): array
    {
        return [
            'rooms' => Room::query()->where('room_type_id', $this->roomType->id)->orderBy('room_number')->get(),
            'amenities' => Amenity::query()->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">{{ $roomType->name }}</h1>
        <a href="{{ route('rooms.index') }}" wire:navigate class="text-sm text-slate-600">Back</a>
    </div>

    @if(session('status'))
        <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
        <h2 class="font-semibold text-slate-900">Room type details</h2>
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                <input type="text" wire:model="name" class="w-full rounded-lg border-slate-200">
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea wire:model="description" rows="3" class="w-full rounded-lg border-slate-200"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Base price</label>
                <input type="number" step="0.01" wire:model="base_price" class="w-full rounded-lg border-slate-200">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Bed type</label>
                <input type="text" wire:model="bed_type" class="w-full rounded-lg border-slate-200">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Adults</label>
                <input type="number" wire:model="capacity_adults" class="w-full rounded-lg border-slate-200">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Children</label>
                <input type="number" wire:model="capacity_children" class="w-full rounded-lg border-slate-200">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Amenities</label>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                @foreach($amenities as $amenity)
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="amenity_ids" value="{{ $amenity->id }}" class="rounded border-slate-300">
                        {{ $amenity->name }}
                    </label>
                @endforeach
            </div>
        </div>
        <div class="flex gap-6">
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_featured" class="rounded border-slate-300"> Featured</label>
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active" class="rounded border-slate-300"> Active</label>
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg">Save changes</button>
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
        <h2 class="font-semibold text-slate-900">Rooms</h2>
        <div class="flex gap-3">
            <input type="text" wire:model="new_room_number" placeholder="Room number" class="rounded-lg border-slate-200 text-sm">
            <input type="text" wire:model="new_floor" placeholder="Floor" class="rounded-lg border-slate-200 text-sm">
            <button type="button" wire:click="addRoom" class="px-3 py-2 bg-slate-900 text-white text-sm rounded-lg">Add room</button>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-3 py-2">Number</th>
                    <th class="text-left px-3 py-2">Floor</th>
                    <th class="text-left px-3 py-2">Status</th>
                    <th class="text-right px-3 py-2">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($rooms as $room)
                    <tr>
                        <td class="px-3 py-2">{{ $room->room_number }}</td>
                        <td class="px-3 py-2">{{ $room->floor ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $room->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-3 py-2 text-right">
                            <button type="button" wire:click="toggleRoom({{ $room->id }})" class="text-indigo-600">Toggle</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
