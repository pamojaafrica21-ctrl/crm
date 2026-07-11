<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal-auth')] class extends Component
{
    public string $email = '';

    public function sendResetLink(): void
    {
        $this->validate(['email' => ['required', 'email']]);

        Password::broker('customers')->sendResetLink(['email' => $this->email]);

        session()->flash('status', __('A reset link will be sent if that email exists.'));
    }
}; ?>

<div>
    <h1 class="font-display text-3xl text-stone-900">{{ __('portal.auth.reset_title') }}</h1>
    <p class="mt-2 text-sm text-stone-500">Enter your email and we will send a reset link.</p>

    @if (session('status'))
        <div class="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <form wire:submit="sendResetLink" class="mt-8 space-y-5">
        <div>
            <label class="text-sm font-medium text-stone-700">{{ __('portal.auth.email') }}</label>
            <input wire:model="email" type="email" class="portal-input mt-1" required>
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="portal-btn w-full">{{ __('portal.auth.send_reset') }}</button>
    </form>

    <p class="mt-6 text-center text-sm">
        <a href="{{ route('guest.login') }}" wire:navigate class="font-semibold text-stone-900">{{ __('portal.auth.sign_in') }}</a>
    </p>
</div>
