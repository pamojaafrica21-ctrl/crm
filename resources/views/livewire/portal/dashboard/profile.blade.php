<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public string $preferences = '';

    public string $emergency_contact_name = '';

    public string $emergency_contact_phone = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $c = auth('guest')->user();
        $this->first_name = $c->first_name;
        $this->last_name = $c->last_name;
        $this->email = $c->email ?? '';
        $this->phone = $c->phone ?? '';
        $this->address = $c->address ?? '';
        $this->preferences = $c->preferences ?? '';
        $this->emergency_contact_name = $c->emergency_contact_name ?? '';
        $this->emergency_contact_phone = $c->emergency_contact_phone ?? '';
    }

    public function saveProfile(): void
    {
        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'preferences' => ['nullable', 'string', 'max:2000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
        ]);

        auth('guest')->user()->update($validated);
        session()->flash('status', __('portal.profile.updated'));
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $customer = auth('guest')->user();

        if (! Hash::check($this->current_password, $customer->password)) {
            $this->addError('current_password', __('The provided password does not match your current password.'));

            return;
        }

        $customer->update(['password' => $this->password]);
        $this->reset('current_password', 'password', 'password_confirmation');
        session()->flash('password_status', __('portal.profile.updated'));
    }
}; ?>

<div class="bg-stone-100 pt-24 pb-16">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 space-y-8">
        <div>
            <a href="{{ route('portal.dashboard') }}" wire:navigate class="text-sm font-medium text-amber-700">← {{ __('portal.dashboard.title') }}</a>
            <h1 class="mt-3 font-display text-4xl text-stone-900">{{ __('portal.profile.title') }}</h1>
        </div>

        @if (session('status'))
            <div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <form wire:submit="saveProfile" class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium">{{ __('portal.auth.first_name') }}</label>
                    <input wire:model="first_name" class="portal-input mt-1" required>
                </div>
                <div>
                    <label class="text-sm font-medium">{{ __('portal.auth.last_name') }}</label>
                    <input wire:model="last_name" class="portal-input mt-1" required>
                </div>
            </div>
            <div>
                <label class="text-sm font-medium">{{ __('portal.auth.email') }}</label>
                <input value="{{ $email }}" class="portal-input mt-1 bg-stone-50" disabled>
            </div>
            <div>
                <label class="text-sm font-medium">{{ __('portal.auth.phone') }}</label>
                <input wire:model="phone" class="portal-input mt-1">
            </div>
            <div>
                <label class="text-sm font-medium">{{ __('portal.profile.addresses') }}</label>
                <textarea wire:model="address" rows="3" class="portal-input mt-1"></textarea>
            </div>
            <div>
                <label class="text-sm font-medium">{{ __('portal.profile.preferences') }}</label>
                <textarea wire:model="preferences" rows="3" class="portal-input mt-1"></textarea>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium">{{ __('portal.profile.emergency') }} name</label>
                    <input wire:model="emergency_contact_name" class="portal-input mt-1">
                </div>
                <div>
                    <label class="text-sm font-medium">{{ __('portal.profile.emergency') }} phone</label>
                    <input wire:model="emergency_contact_phone" class="portal-input mt-1">
                </div>
            </div>
            <button type="submit" class="portal-btn">{{ __('portal.profile.save') }}</button>
        </form>

        <form wire:submit="updatePassword" class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm space-y-4">
            <h2 class="font-display text-2xl">{{ __('portal.profile.password') }}</h2>
            @if (session('password_status'))
                <div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('password_status') }}</div>
            @endif
            <div>
                <label class="text-sm font-medium">Current password</label>
                <input wire:model="current_password" type="password" class="portal-input mt-1" required>
                @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium">{{ __('portal.auth.password') }}</label>
                <input wire:model="password" type="password" class="portal-input mt-1" required>
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium">{{ __('portal.auth.password_confirm') }}</label>
                <input wire:model="password_confirmation" type="password" class="portal-input mt-1" required>
            </div>
            <button type="submit" class="portal-btn">{{ __('portal.profile.save') }}</button>
        </form>
    </div>
</div>
