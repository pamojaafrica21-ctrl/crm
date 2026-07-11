<?php

use App\Domain\Content\Models\ContactInquiry;
use App\Domain\Properties\Services\PropertyContext;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $subject = '';
    public string $message = '';
    public string $type = 'inquiry';

    public function mount(): void
    {
        if ($guest = auth('guest')->user()) {
            $this->name = $guest->fullName();
            $this->email = (string) $guest->email;
            $this->phone = (string) $guest->phone;
        }
    }

    public function submit(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
            'type' => 'required|in:inquiry,callback',
        ]);

        ContactInquiry::create([
            'property_id' => app(PropertyContext::class)->id(),
            'customer_id' => auth('guest')->id(),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
            'subject' => $validated['subject'] ?: null,
            'message' => $validated['message'],
            'type' => $validated['type'],
            'status' => 'new',
        ]);

        $this->reset('subject', 'message');
        session()->flash('status', __('portal.contact.sent'));
    }
}; ?>

<div class="bg-stone-100 pt-24 pb-20">
    <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
        <h1 class="font-display text-4xl text-stone-900">{{ __('portal.contact.title') }}</h1>

        @if(session('status'))
            <div class="mt-6 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <form wire:submit="submit" class="mt-8 space-y-4 rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Type</label>
                <select wire:model="type" class="portal-input">
                    <option value="inquiry">{{ __('portal.contact.inquiry') }}</option>
                    <option value="callback">{{ __('portal.contact.callback') }}</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Name</label>
                <input type="text" wire:model="name" class="portal-input">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Email</label>
                <input type="email" wire:model="email" class="portal-input">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Phone</label>
                <input type="text" wire:model="phone" class="portal-input">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Subject</label>
                <input type="text" wire:model="subject" class="portal-input">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Message</label>
                <textarea wire:model="message" rows="4" class="portal-input"></textarea>
                @error('message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="portal-btn w-full">Send</button>
        </form>
    </div>
</div>
