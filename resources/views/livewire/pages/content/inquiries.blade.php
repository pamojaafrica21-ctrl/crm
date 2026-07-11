<?php

use App\Domain\Content\Models\ContactInquiry;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $status = '';

    public function mark(int $id, string $status): void
    {
        $this->authorize('content.manage');
        ContactInquiry::query()->findOrFail($id)->update(['status' => $status]);
    }

    public function with(): array
    {
        $this->authorize('content.manage');

        return [
            'inquiries' => ContactInquiry::query()
                ->when($this->status, fn ($q) => $q->where('status', $this->status))
                ->latest()
                ->paginate(20),
        ];
    }
}; ?>

<div class="space-y-6">
    <h1 class="text-2xl font-bold text-slate-900">Contact Inquiries</h1>
    <div class="bg-white rounded-xl shadow-sm border border-slate-100">
        <div class="p-4 border-b border-slate-100">
            <select wire:model.live="status" class="rounded-lg border-slate-200 text-sm">
                <option value="">All</option>
                <option value="new">New</option>
                <option value="in_progress">In progress</option>
                <option value="closed">Closed</option>
            </select>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">From</th>
                        <th class="text-left px-4 py-3">Subject</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-right px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($inquiries as $inquiry)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $inquiry->name }}</div>
                                <div class="text-xs text-slate-500">{{ $inquiry->email }} · {{ $inquiry->type }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div>{{ $inquiry->subject ?: '—' }}</div>
                                <div class="text-xs text-slate-500 line-clamp-2">{{ $inquiry->message }}</div>
                            </td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs bg-slate-100">{{ $inquiry->status }}</span></td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button type="button" wire:click="mark({{ $inquiry->id }}, 'in_progress')" class="text-indigo-600">Progress</button>
                                <button type="button" wire:click="mark({{ $inquiry->id }}, 'closed')" class="text-slate-600">Close</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">No inquiries.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $inquiries->links() }}</div>
    </div>
</div>
