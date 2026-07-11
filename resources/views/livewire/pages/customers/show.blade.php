<?php

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerCommunication;
use App\Domain\Customers\Models\CustomerNote;
use Livewire\Volt\Component;

new class extends Component
{
    public Customer $customer;
    public string $noteBody = '';
    public string $commChannel = 'email';
    public string $commDirection = 'outbound';
    public string $commSubject = '';
    public string $commBody = '';

    public function mount(Customer $customer): void
    {
        $this->authorize('customers.view');
        $this->customer = $customer->load(['notes.user', 'communications.user', 'tags', 'reservations', 'fnbOrders', 'eventBookings', 'segments']);
    }

    public function addNote(): void
    {
        $this->authorize('customers.update');
        $this->validate(['noteBody' => 'required|string']);

        CustomerNote::create([
            'customer_id' => $this->customer->id,
            'user_id' => auth()->id(),
            'body' => $this->noteBody,
        ]);

        $this->noteBody = '';
        $this->customer->load('notes.user');
    }

    public function addCommunication(): void
    {
        $this->authorize('customers.update');
        $this->validate([
            'commChannel' => 'required|string',
            'commDirection' => 'required|string',
            'commBody' => 'nullable|string',
        ]);

        CustomerCommunication::create([
            'customer_id' => $this->customer->id,
            'user_id' => auth()->id(),
            'channel' => $this->commChannel,
            'direction' => $this->commDirection,
            'subject' => $this->commSubject,
            'body' => $this->commBody,
            'communicated_at' => now(),
        ]);

        $this->commSubject = '';
        $this->commBody = '';
        $this->customer->load('communications.user');
    }
}; ?>

<div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('customers.index') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-800">← Back to Customers</a>
                <h1 class="text-2xl font-bold text-slate-900 mt-1">{{ $customer->fullName() }}</h1>
                <p class="text-slate-500 text-sm">{{ $customer->email }} · {{ $customer->phone }}</p>
            </div>
            @if ($customer->vip_level)
                <span class="px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-sm font-medium">{{ $customer->vip_level }} VIP</span>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <h2 class="font-semibold text-slate-900 mb-4">Booking History</h2>
                    @forelse ($customer->reservations as $reservation)
                        <div class="flex justify-between py-3 border-b border-slate-50 last:border-0">
                            <div>
                                <div class="font-medium text-sm">{{ $reservation->confirmation_number }} — {{ $reservation->room_type }}</div>
                                <div class="text-xs text-slate-500">{{ $reservation->check_in->format('M j') }} → {{ $reservation->check_out->format('M j, Y') }}</div>
                            </div>
                            <div class="text-sm font-medium">${{ number_format($reservation->total_amount, 2) }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No reservations synced from HMS.</p>
                    @endforelse
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <h2 class="font-semibold text-slate-900 mb-4">Notes</h2>
                    @can('customers.update')
                        <form wire:submit="addNote" class="mb-4">
                            <textarea wire:model="noteBody" rows="2" placeholder="Add a note..." class="w-full rounded-lg border-slate-200 text-sm"></textarea>
                            <button type="submit" class="mt-2 px-3 py-1.5 bg-indigo-600 text-white text-xs rounded-lg">Add Note</button>
                        </form>
                    @endcan
                    @forelse ($customer->notes->sortByDesc('created_at') as $note)
                        <div class="py-3 border-b border-slate-50 last:border-0">
                            <div class="text-sm text-slate-800">{{ $note->body }}</div>
                            <div class="text-xs text-slate-400 mt-1">{{ $note->user->name }} · {{ $note->created_at->diffForHumans() }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No notes yet.</p>
                    @endforelse
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <h2 class="font-semibold text-slate-900 mb-4">Communications</h2>
                    @can('customers.update')
                        <form wire:submit="addCommunication" class="mb-4 space-y-2">
                            <div class="grid grid-cols-2 gap-2">
                                <select wire:model="commChannel" class="rounded-lg border-slate-200 text-sm">
                                    <option value="email">Email</option>
                                    <option value="phone">Phone</option>
                                    <option value="sms">SMS</option>
                                    <option value="in_person">In Person</option>
                                </select>
                                <select wire:model="commDirection" class="rounded-lg border-slate-200 text-sm">
                                    <option value="outbound">Outbound</option>
                                    <option value="inbound">Inbound</option>
                                </select>
                            </div>
                            <input type="text" wire:model="commSubject" placeholder="Subject" class="w-full rounded-lg border-slate-200 text-sm">
                            <textarea wire:model="commBody" rows="2" placeholder="Message..." class="w-full rounded-lg border-slate-200 text-sm"></textarea>
                            <button type="submit" class="px-3 py-1.5 bg-indigo-600 text-white text-xs rounded-lg">Log Communication</button>
                        </form>
                    @endcan
                    @forelse ($customer->communications->sortByDesc('communicated_at') as $comm)
                        <div class="py-3 border-b border-slate-50 last:border-0">
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <span class="uppercase">{{ $comm->channel }}</span> · {{ $comm->direction }}
                            </div>
                            @if ($comm->subject)<div class="font-medium text-sm">{{ $comm->subject }}</div>@endif
                            <div class="text-sm text-slate-700">{{ $comm->body }}</div>
                            <div class="text-xs text-slate-400 mt-1">{{ $comm->communicated_at->format('M j, Y g:i A') }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No communications logged.</p>
                    @endforelse
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <h2 class="font-semibold text-slate-900 mb-3">Details</h2>
                    <dl class="space-y-2 text-sm">
                        <div><dt class="text-slate-500">Company</dt><dd>{{ $customer->company ?? '—' }}</dd></div>
                        <div><dt class="text-slate-500">Nationality</dt><dd>{{ $customer->nationality ?? '—' }}</dd></div>
                        <div><dt class="text-slate-500">Source</dt><dd class="uppercase">{{ $customer->source }}</dd></div>
                    </dl>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <h2 class="font-semibold text-slate-900 mb-3">F&B Orders</h2>
                    @forelse ($customer->fnbOrders->take(5) as $order)
                        <div class="text-sm py-2 border-b border-slate-50">{{ $order->outlet }} — ${{ number_format($order->total_amount, 2) }}</div>
                    @empty
                        <p class="text-sm text-slate-500">None</p>
                    @endforelse
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <h2 class="font-semibold text-slate-900 mb-3">Events</h2>
                    @forelse ($customer->eventBookings->take(5) as $event)
                        <div class="text-sm py-2 border-b border-slate-50">{{ $event->event_name }} — {{ $event->starts_at->format('M j, Y') }}</div>
                    @empty
                        <p class="text-sm text-slate-500">None</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
