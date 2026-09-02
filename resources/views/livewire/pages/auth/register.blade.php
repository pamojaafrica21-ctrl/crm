<?php

use App\Application\Organizations\RegisterOrganizationAction;
use App\Domain\Billing\Services\HomepageContentService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $organization_name = '';
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $password_confirmation = '';
    public int $trialDays = 14;

    public function mount(HomepageContentService $homepage): void
    {
        $this->trialDays = max(1, $homepage->trialDays());
    }

    public function register(RegisterOrganizationAction $action): void
    {
        $validated = $this->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:App\Models\User,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = $action->execute([
            'organization_name' => $validated['organization_name'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
        ]);

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6">
        <h2 class="font-display text-2xl font-semibold text-[#143529]">Start your {{ $trialDays }}-day free trial</h2>
        <p class="text-sm text-stone-600 mt-1">Create your organisation account. No payment required to begin.</p>
    </div>

    <form wire:submit="register" class="space-y-4">
        <div>
            <x-input-label for="organization_name" :value="__('Organisation Name')" />
            <x-text-input wire:model="organization_name" id="organization_name" class="block mt-1 w-full" type="text" required autofocus />
            <x-input-error :messages="$errors->get('organization_name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="name" :value="__('Your Name')" />
            <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" required />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="phone" :value="__('Phone (optional)')" />
            <x-text-input wire:model="phone" id="phone" class="block mt-1 w-full" type="text" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" required />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full" type="password" required />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <a class="text-sm text-indigo-600 hover:text-indigo-700" href="{{ route('login') }}" wire:navigate>
                {{ __('Already registered?') }}
            </a>

            <x-primary-button>
                Start {{ $trialDays }}-day free trial
            </x-primary-button>
        </div>
    </form>
</div>
