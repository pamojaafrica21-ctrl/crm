<?php

use App\Domain\Customers\Models\Customer;
use App\Domain\Properties\Services\PropertyContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal-auth')] class extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(): void
    {
        $propertyId = app(PropertyContext::class)->id();

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $exists = Customer::query()
            ->where('property_id', $propertyId)
            ->where('email', $validated['email'])
            ->whereNotNull('password')
            ->exists();

        if ($exists) {
            $this->addError('email', __('validation.unique', ['attribute' => 'email']));

            return;
        }

        $existing = Customer::query()
            ->where('property_id', $propertyId)
            ->where('email', $validated['email'])
            ->whereNull('password')
            ->first();

        if ($existing) {
            $existing->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'] ?? $existing->phone,
                'password' => $validated['password'],
                'source' => 'portal',
                'email_verified_at' => now(),
            ]);
            $customer = $existing->fresh();
        } else {
            $customer = Customer::create([
                'property_id' => $propertyId,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => $validated['password'],
                'source' => 'portal',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        Auth::guard('guest')->login($customer);
        session()->regenerate();

        \App\Domain\Content\Models\LoyaltyAccount::firstOrCreate(
            [
                'property_id' => $propertyId,
                'customer_id' => $customer->id,
            ],
            [
                'points' => 0,
                'tier' => 'member',
            ]
        );

        $this->redirect(route('portal.dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h1 class="font-display text-3xl text-stone-900">{{ __('portal.auth.register_title') }}</h1>
    <p class="mt-2 text-sm text-stone-500">{{ __('portal.auth.register_subtitle') }}</p>

    <form wire:submit="register" class="mt-8 space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="text-sm font-medium text-stone-700">{{ __('portal.auth.first_name') }}</label>
                <input wire:model="first_name" type="text" class="portal-input mt-1" required>
                @error('first_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-stone-700">{{ __('portal.auth.last_name') }}</label>
                <input wire:model="last_name" type="text" class="portal-input mt-1" required>
                @error('last_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <div>
            <label class="text-sm font-medium text-stone-700">{{ __('portal.auth.email') }}</label>
            <input wire:model="email" type="email" class="portal-input mt-1" required>
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium text-stone-700">{{ __('portal.auth.phone') }}</label>
            <input wire:model="phone" type="text" class="portal-input mt-1">
            @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
        <button type="submit" class="portal-btn w-full">{{ __('portal.auth.sign_up') }}</button>
    </form>

    <p class="mt-6 text-center text-sm text-stone-500">
        {{ __('portal.auth.has_account') }}
        <a href="{{ route('guest.login') }}" wire:navigate class="font-semibold text-stone-900 hover:text-amber-700">{{ __('portal.auth.sign_in') }}</a>
    </p>
</div>
