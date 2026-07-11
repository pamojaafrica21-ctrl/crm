<?php

use App\Domain\Customers\Models\Customer;
use App\Domain\Properties\Services\PropertyContext;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal-auth')] class extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = Str::transliterate(Str::lower($this->email).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            event(new Lockout(request()));
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
            ]);
        }

        $propertyId = app(PropertyContext::class)->id();

        $customer = Customer::query()
            ->where('property_id', $propertyId)
            ->where('email', $this->email)
            ->where('is_active', true)
            ->whereNotNull('password')
            ->first();

        if (! $customer || ! Auth::guard('guest')->attempt([
            'email' => $this->email,
            'password' => $this->password,
            'property_id' => $propertyId,
            'is_active' => true,
        ], $this->remember)) {
            RateLimiter::hit($key);
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($key);
        session()->regenerate();

        $this->redirectIntended(default: route('portal.dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h1 class="font-display text-3xl text-stone-900">{{ __('portal.auth.login_title') }}</h1>
    <p class="mt-2 text-sm text-stone-500">{{ __('portal.auth.login_subtitle') }}</p>

    <form wire:submit="login" class="mt-8 space-y-5">
        <div>
            <label class="text-sm font-medium text-stone-700" for="email">{{ __('portal.auth.email') }}</label>
            <input wire:model="email" id="email" type="email" class="portal-input mt-1" required autofocus>
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium text-stone-700" for="password">{{ __('portal.auth.password') }}</label>
            <input wire:model="password" id="password" type="password" class="portal-input mt-1" required>
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-stone-600">
                <input wire:model="remember" type="checkbox" class="rounded border-stone-300 text-amber-600 focus:ring-amber-500">
                {{ __('portal.auth.remember') }}
            </label>
            <a href="{{ route('guest.password.request') }}" wire:navigate class="text-sm text-amber-700 hover:text-amber-800">{{ __('portal.auth.forgot') }}</a>
        </div>
        <button type="submit" class="portal-btn w-full">{{ __('portal.auth.sign_in') }}</button>
    </form>

    <p class="mt-6 text-center text-sm text-stone-500">
        {{ __('portal.auth.no_account') }}
        <a href="{{ route('guest.register') }}" wire:navigate class="font-semibold text-stone-900 hover:text-amber-700">{{ __('portal.auth.sign_up') }}</a>
    </p>
</div>
