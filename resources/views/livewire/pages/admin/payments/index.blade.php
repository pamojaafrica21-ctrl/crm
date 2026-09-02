<?php

use App\Domain\Billing\Models\MpesaTransaction;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin')] class extends Component
{
    use WithPagination;

    public string $providerFilter = '';
    public string $statusFilter = '';

    public function with(): array
    {
        $mpesa = MpesaTransaction::with(['organization', 'subscriptionPlan'])
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest();

        return [
            'mpesaPayments' => ($this->providerFilter === '' || $this->providerFilter === 'mpesa')
                ? $mpesa->paginate(15)
                : collect(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Payments</h1>
        <p class="mt-1 text-sm text-slate-500">Monitor subscription payments across all organisations.</p>
    </div>

    <div class="flex gap-3">
        <select wire:model.live="providerFilter" class="rounded-lg border-slate-200 text-sm">
            <option value="">All providers</option>
            <option value="mpesa">M-Pesa</option>
            <option value="stripe">Stripe</option>
        </select>
        <select wire:model.live="statusFilter" class="rounded-lg border-slate-200 text-sm">
            <option value="">All statuses</option>
            <option value="pending">Pending</option>
            <option value="completed">Completed</option>
            <option value="failed">Failed</option>
        </select>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Organisation</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Provider</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Plan</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @if ($providerFilter === 'stripe')
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">
                            Stripe payments appear here once Stripe is configured and organisations subscribe.
                        </td>
                    </tr>
                @else
                    @forelse ($mpesaPayments as $payment)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-sm">{{ $payment->organization?->name ?? '—' }}</td>
                            <td class="px-4 py-3"><span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full">M-Pesa</span></td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $payment->subscriptionPlan?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm font-medium">{{ strtoupper($payment->currency) }} {{ number_format($payment->amount, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded-full
                                    @if($payment->status === 'completed') bg-green-100 text-green-700
                                    @elseif($payment->status === 'pending') bg-yellow-100 text-yellow-700
                                    @else bg-red-100 text-red-700 @endif">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $payment->created_at->format('M j, Y g:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No payments found.</td>
                        </tr>
                    @endforelse
                @endif
            </tbody>
        </table>
        @if ($providerFilter !== 'stripe')
            <div class="px-4 py-3">{{ $mpesaPayments->links() }}</div>
        @endif
    </div>
</div>
