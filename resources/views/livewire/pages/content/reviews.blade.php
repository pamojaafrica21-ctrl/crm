<?php

use App\Domain\Content\Models\Review;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public function approve(int $id): void
    {
        $this->authorize('content.manage');
        Review::query()->findOrFail($id)->update(['is_approved' => true]);
    }

    public function reject(int $id): void
    {
        $this->authorize('content.manage');
        Review::query()->findOrFail($id)->update(['is_approved' => false]);
    }

    public function delete(int $id): void
    {
        $this->authorize('content.manage');
        Review::query()->findOrFail($id)->delete();
    }

    public function with(): array
    {
        $this->authorize('content.manage');

        return [
            'reviews' => Review::query()->with(['customer', 'reviewable'])->latest()->paginate(20),
        ];
    }
}; ?>

<div class="space-y-6">
    <h1 class="text-2xl font-bold text-slate-900">Reviews</h1>
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Guest</th>
                    <th class="text-left px-4 py-3">Review</th>
                    <th class="text-left px-4 py-3">Rating</th>
                    <th class="text-right px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($reviews as $review)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $review->customer?->fullName() }}</div>
                            <div class="text-xs text-slate-500">{{ class_basename($review->reviewable_type) }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $review->title ?: '—' }}</div>
                            <div class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($review->body, 120) }}</div>
                            <div class="text-xs mt-1 {{ $review->is_approved ? 'text-emerald-600' : 'text-amber-600' }}">{{ $review->is_approved ? 'Approved' : 'Pending' }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $review->rating }}/5</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <button type="button" wire:click="approve({{ $review->id }})" class="text-indigo-600">Approve</button>
                            <button type="button" wire:click="reject({{ $review->id }})" class="text-slate-600">Reject</button>
                            <button type="button" wire:click="delete({{ $review->id }})" wire:confirm="Delete?" class="text-red-600">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">No reviews.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $reviews->links() }}</div>
    </div>
</div>
