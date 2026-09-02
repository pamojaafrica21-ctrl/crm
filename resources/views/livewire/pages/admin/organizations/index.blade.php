<?php

use App\Application\Organizations\DeleteOrganizationAction;
use App\Domain\Organizations\Models\Organization;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public function with(): array
    {
        $query = Organization::with('subscriptionPlan')->latest();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return [
            'organizations' => $query->paginate(15),
        ];
    }

    public function deleteOrganization(int $id, DeleteOrganizationAction $action): void
    {
        $organization = Organization::findOrFail($id);
        $action->execute($organization);

        session()->flash('status', 'Organisation deleted successfully.');
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Organisations</h1>
            <p class="mt-1 text-sm text-slate-500">Monitor and manage all platform tenants.</p>
        </div>
        <a href="{{ route('admin.organizations.create') }}" wire:navigate
           class="inline-flex items-center px-4 py-2 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700">
            Create Organisation
        </a>
    </div>

    @if (session('status'))
        <div class="bg-green-50 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('status') }}</div>
    @endif

    <div class="flex gap-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search organisations..."
               class="rounded-lg border-slate-200 text-sm flex-1">
        <select wire:model.live="statusFilter" class="rounded-lg border-slate-200 text-sm">
            <option value="">All statuses</option>
            <option value="trial">Trial</option>
            <option value="active">Active</option>
            <option value="past_due">Past Due</option>
            <option value="suspended">Suspended</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Organisation</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Plan</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Trial Ends</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Created</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($organizations as $org)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.organizations.show', $org) }}" wire:navigate class="font-medium text-violet-600 hover:text-violet-700">
                                {{ $org->name }}
                            </a>
                            <div class="text-sm text-slate-500">{{ $org->email }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $org->subscriptionPlan?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-1 rounded-full
                                @if($org->status === 'active') bg-green-100 text-green-700
                                @elseif($org->status === 'trial') bg-blue-100 text-blue-700
                                @elseif($org->status === 'past_due') bg-orange-100 text-orange-700
                                @else bg-slate-100 text-slate-600 @endif">
                                {{ ucfirst(str_replace('_', ' ', $org->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $org->trial_ends_at?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $org->created_at->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                            <a href="{{ route('admin.organizations.show', $org) }}" wire:navigate class="text-slate-600 hover:text-slate-900 mr-3">View</a>
                            <a href="{{ route('admin.organizations.edit', $org) }}" wire:navigate class="text-violet-600 hover:text-violet-700 mr-3">Edit</a>
                            <button
                                wire:click="deleteOrganization({{ $org->id }})"
                                wire:confirm="Delete {{ $org->name }}? This will permanently remove the organisation and its users."
                                class="text-red-600 hover:text-red-700">
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No organisations found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3">{{ $organizations->links() }}</div>
    </div>
</div>
