<?php

use App\Domain\Customers\Models\Reservation;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';

    public function with(): array
    {
        $this->authorize('reservations.view');

        return [
            'reservations' => Reservation::query()
                ->with(['customer', 'roomType'])
                ->when($this->search, fn ($q) => $q->where(function ($q) {
                    $q->where('confirmation_number', 'like', "%{$this->search}%")
                        ->orWhere('group_code', 'like', "%{$this->search}%")
                        ->orWhere('room_type', 'like', "%{$this->search}%");
                }))
                ->when($this->status, fn ($q) => $q->where('status', $this->status))
                ->latest()
                ->paginate(20),
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
}; ?>

<div class="space-y-6">
    <h1 class="text-2xl font-bold text-slate-900">Reservations</h1>

    <div class="bg-white rounded-xl shadow-sm border border-slate-100">
        <div class="p-4 border-b border-slate-100 flex flex-wrap gap-3">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search confirmation, group, room..." class="w-full max-w-md rounded-lg border-slate-200 text-sm">
            <select wire:model.live="status" class="rounded-lg border-slate-200 text-sm">
                <option value="">All statuses</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="checked_in">Checked in</option>
                <option value="checked_out">Checked out</option>
                <option value="cancelled">Cancelled</option>
                <option value="completed">Completed</option>
            </select>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium">Confirmation</th>
                        <th class="text-left px-4 py-3 font-medium">Guest</th>
                        <th class="text-left px-4 py-3 font-medium">Room</th>
                        <th class="text-left px-4 py-3 font-medium">Dates</th>
                        <th class="text-left px-4 py-3 font-medium">Status</th>
                        <th class="text-right px-4 py-3 font-medium">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($reservations as $reservation)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900">{{ $reservation->confirmation_number }}</div>
                                <div class="text-xs text-slate-500">{{ $reservation->group_code }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $reservation->customer?->fullName() ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $reservation->room_type ?: $reservation->roomType?->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $reservation->check_in->format('M j') }} – {{ $reservation->check_out->format('M j, Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-700 uppercase">{{ $reservation->status->value }}</span>
                            </td>
                            <td class="px-4 py-3 text-right font-medium">{{ $reservation->currency }} {{ number_format($reservation->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No reservations found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $reservations->links() }}</div>
    </div>
</div>
