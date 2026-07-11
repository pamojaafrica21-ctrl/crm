<?php

use App\Domain\Content\Models\Faq;
use App\Domain\Properties\Services\PropertyContext;
use Livewire\Volt\Component;

new class extends Component
{
    public ?int $editingId = null;
    public string $question = '';
    public string $answer = '';
    public string $category = 'General';
    public int $sort_order = 0;
    public bool $is_active = true;

    public function save(): void
    {
        $this->authorize('content.manage');

        $validated = $this->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $data = array_merge($validated, [
            'property_id' => app(PropertyContext::class)->id(),
            'category' => $validated['category'] ?: 'General',
        ]);

        if ($this->editingId) {
            Faq::query()->findOrFail($this->editingId)->update($data);
        } else {
            Faq::create($data);
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $this->authorize('content.manage');
        $faq = Faq::query()->findOrFail($id);
        $this->editingId = $faq->id;
        $this->question = $faq->question;
        $this->answer = $faq->answer;
        $this->category = (string) $faq->category;
        $this->sort_order = (int) $faq->sort_order;
        $this->is_active = (bool) $faq->is_active;
    }

    public function delete(int $id): void
    {
        $this->authorize('content.manage');
        Faq::query()->findOrFail($id)->delete();
        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    public function resetForm(): void
    {
        $this->reset('editingId', 'question', 'answer');
        $this->category = 'General';
        $this->sort_order = 0;
        $this->is_active = true;
    }

    public function with(): array
    {
        return ['faqs' => Faq::query()->orderBy('sort_order')->get()];
    }
}; ?>

<div class="space-y-6">
    <h1 class="text-2xl font-bold text-slate-900">FAQs</h1>
    <div class="grid gap-6 lg:grid-cols-2">
        <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
            <h2 class="font-semibold">{{ $editingId ? 'Edit FAQ' : 'Add FAQ' }}</h2>
            <input type="text" wire:model="question" placeholder="Question" class="w-full rounded-lg border-slate-200">
            <textarea wire:model="answer" rows="4" placeholder="Answer" class="w-full rounded-lg border-slate-200"></textarea>
            <div class="grid grid-cols-2 gap-3">
                <input type="text" wire:model="category" placeholder="Category" class="rounded-lg border-slate-200">
                <input type="number" wire:model="sort_order" placeholder="Sort" class="rounded-lg border-slate-200">
            </div>
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active" class="rounded border-slate-300"> Active</label>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg">Save</button>
                @if($editingId)<button type="button" wire:click="resetForm" class="px-4 py-2 text-sm text-slate-600">Cancel</button>@endif
            </div>
        </form>
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left px-4 py-3">Question</th><th class="text-right px-4 py-3">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($faqs as $faq)
                        <tr>
                            <td class="px-4 py-3"><div class="font-medium">{{ $faq->question }}</div><div class="text-xs text-slate-500">{{ $faq->category }}</div></td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button type="button" wire:click="edit({{ $faq->id }})" class="text-indigo-600">Edit</button>
                                <button type="button" wire:click="delete({{ $faq->id }})" wire:confirm="Delete?" class="text-red-600">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-8 text-center text-slate-500">No FAQs.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
