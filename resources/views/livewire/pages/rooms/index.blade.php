<?php

use App\Domain\Rooms\Models\RoomType;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public function with(): array
    {
        return [
            'roomTypes' => RoomType::query()
                ->withCount('rooms')
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(15),
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Room Types</h1>
        @can('rooms.manage')
            <a href="{{ route('rooms.create') }}" wire:navigate class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">Add Room Type</a>
        @endcan
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-100">
        <div class="p-4 border-b border-slate-100">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search room types..." class="w-full max-w-md rounded-lg border-slate-200 text-sm">
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium">Name</th>
                        <th class="text-left px-4 py-3 font-medium">Price</th>
                        <th class="text-left px-4 py-3 font-medium">Capacity</th>
                        <th class="text-left px-4 py-3 font-medium">Rooms</th>
                        <th class="text-left px-4 py-3 font-medium">Status</th>
                        <th class="text-right px-4 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($roomTypes as $type)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $type->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ number_format($type->base_price, 2) }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $type->capacity_adults }}A / {{ $type->capacity_children }}C</td>
                            <td class="px-4 py-3 text-slate-600">{{ $type->rooms_count }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs {{ $type->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $type->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('rooms.show', $type) }}" wire:navigate class="text-indigo-600 hover:text-indigo-800">Manage</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No room types yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $roomTypes->links() }}</div>
    </div>
</div>
