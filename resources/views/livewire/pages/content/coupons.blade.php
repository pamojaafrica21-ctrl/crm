<?php

use App\Domain\Content\Models\Coupon;
use App\Domain\Properties\Services\PropertyContext;
use Livewire\Volt\Component;

new class extends Component
{
    public ?int $editingId = null;
    public string $code = '';
    public string $name = '';
    public string $discount_type = 'percent';
    public string $discount_value = '';
    public string $min_order_amount = '';
    public string $max_redemptions = '';
    public string $starts_at = '';
    public string $ends_at = '';
    public bool $is_active = true;

    public function save(): void
    {
        $this->authorize('content.manage');

        $validated = $this->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'discount_type' => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_redemptions' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        $data = [
            'property_id' => app(PropertyContext::class)->id(),
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'min_order_amount' => $validated['min_order_amount'] !== '' ? $validated['min_order_amount'] : null,
            'max_redemptions' => $validated['max_redemptions'] !== '' ? $validated['max_redemptions'] : null,
            'starts_at' => $validated['starts_at'] ?: null,
            'ends_at' => $validated['ends_at'] ?: null,
            'is_active' => $validated['is_active'],
        ];

        if ($this->editingId) {
            Coupon::query()->findOrFail($this->editingId)->update($data);
        } else {
            Coupon::create($data);
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $this->authorize('content.manage');
        $coupon = Coupon::query()->findOrFail($id);
        $this->editingId = $coupon->id;
        $this->code = $coupon->code;
        $this->name = $coupon->name;
        $this->discount_type = $coupon->discount_type;
        $this->discount_value = (string) $coupon->discount_value;
        $this->min_order_amount = (string) $coupon->min_order_amount;
        $this->max_redemptions = (string) $coupon->max_redemptions;
        $this->starts_at = $coupon->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->ends_at = $coupon->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->is_active = (bool) $coupon->is_active;
    }

    public function delete(int $id): void
    {
        $this->authorize('content.manage');
        Coupon::query()->findOrFail($id)->delete();
        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    public function resetForm(): void
    {
        $this->reset('editingId', 'code', 'name', 'discount_value', 'min_order_amount', 'max_redemptions', 'starts_at', 'ends_at');
        $this->discount_type = 'percent';
        $this->is_active = true;
    }

    public function with(): array
    {
        return ['coupons' => Coupon::query()->latest()->get()];
    }
}; ?>

<div class="space-y-6">
    <h1 class="text-2xl font-bold text-slate-900">Coupons</h1>
    <div class="grid gap-6 lg:grid-cols-2">
        <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
            <h2 class="font-semibold">{{ $editingId ? 'Edit coupon' : 'Add coupon' }}</h2>
            <input type="text" wire:model="code" placeholder="CODE" class="w-full rounded-lg border-slate-200 uppercase">
            <input type="text" wire:model="name" placeholder="Name" class="w-full rounded-lg border-slate-200">
            <div class="grid grid-cols-2 gap-3">
                <select wire:model="discount_type" class="rounded-lg border-slate-200">
                    <option value="percent">Percent</option>
                    <option value="fixed">Fixed</option>
                </select>
                <input type="number" step="0.01" wire:model="discount_value" placeholder="Value" class="rounded-lg border-slate-200">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <input type="number" step="0.01" wire:model="min_order_amount" placeholder="Min order" class="rounded-lg border-slate-200">
                <input type="number" wire:model="max_redemptions" placeholder="Max uses" class="rounded-lg border-slate-200">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <input type="datetime-local" wire:model="starts_at" class="rounded-lg border-slate-200">
                <input type="datetime-local" wire:model="ends_at" class="rounded-lg border-slate-200">
            </div>
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active" class="rounded border-slate-300"> Active</label>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg">Save</button>
                @if($editingId)<button type="button" wire:click="resetForm" class="px-4 py-2 text-sm text-slate-600">Cancel</button>@endif
            </div>
        </form>
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left px-4 py-3">Coupon</th><th class="text-left px-4 py-3">Uses</th><th class="text-right px-4 py-3">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($coupons as $coupon)
                        <tr>
                            <td class="px-4 py-3"><div class="font-medium">{{ $coupon->code }}</div><div class="text-xs text-slate-500">{{ $coupon->name }}</div></td>
                            <td class="px-4 py-3">{{ $coupon->redemption_count }}{{ $coupon->max_redemptions ? '/'.$coupon->max_redemptions : '' }}</td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button type="button" wire:click="edit({{ $coupon->id }})" class="text-indigo-600">Edit</button>
                                <button type="button" wire:click="delete({{ $coupon->id }})" wire:confirm="Delete?" class="text-red-600">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">No coupons.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
