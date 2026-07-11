<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal-auth')] class extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->string('email')->toString();
    }

    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::broker('customers')->reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($customer) {
                $customer->forceFill([
                    'password' => $this->password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($customer));
            }
        );

        if ($status != Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        session()->flash('status', __($status));
        $this->redirect(route('guest.login', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h1 class="font-display text-3xl text-stone-900">{{ __('portal.auth.reset_title') }}</h1>

    <form wire:submit="resetPassword" class="mt-8 space-y-4">
        <div>
            <label class="text-sm font-medium text-stone-700">{{ __('portal.auth.email') }}</label>
            <input wire:model="email" type="email" class="portal-input mt-1" required>
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium text-stone-700">{{ __('portal.auth.password') }}</label>
            <input wire:model="password" type="password" class="portal-input mt-1" required>
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium text-stone-700">{{ __('portal.auth.password_confirm') }}</label>
            <input wire:model="password_confirmation" type="password" class="portal-input mt-1" required>
        </div>
        <button type="submit" class="portal-btn w-full">{{ __('portal.auth.reset_title') }}</button>
    </form>
</div>
