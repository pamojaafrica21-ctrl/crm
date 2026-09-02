<?php

use App\Livewire\Forms\LoginForm;
use App\Domain\Billing\Services\HomepageContentService;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;
    public int $trialDays = 14;

    public function mount(HomepageContentService $homepage): void
    {
        $this->trialDays = max(1, $homepage->trialDays());
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        if (auth()->user()?->is_super_admin) {
            $this->redirectIntended(default: route('admin.dashboard', absolute: false), navigate: true);

            return;
        }

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-8">
        <h2 class="font-display text-3xl font-semibold text-[#143529] tracking-tight">{{ __('Sign in') }}</h2>
        <p class="mt-2 text-sm text-stone-600 leading-relaxed">
            {{ __('Enter your staff email and password to open the CRM.') }}
        </p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        <div>
            <label for="email" class="block text-sm font-medium text-stone-700 mb-1.5">{{ __('Email') }}</label>
            <input wire:model="form.email" id="email" type="email" name="email" required autofocus autocomplete="username"
                   class="block w-full rounded-xl border-stone-300 bg-white/80 shadow-sm focus:border-[#143529] focus:ring-[#143529] text-stone-900">
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-stone-700 mb-1.5">{{ __('Password') }}</label>
            <input wire:model="form.password" id="password" type="password" name="password" required autocomplete="current-password"
                   class="block w-full rounded-xl border-stone-300 bg-white/80 shadow-sm focus:border-[#143529] focus:ring-[#143529] text-stone-900">
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between gap-3 pt-1">
            <label for="remember" class="inline-flex items-center gap-2">
                <input wire:model="form.remember" id="remember" type="checkbox" name="remember"
                       class="rounded border-stone-300 text-[#143529] shadow-sm focus:ring-[#143529]">
                <span class="text-sm text-stone-600">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-[#1a4535] hover:text-[#0f2f24] underline-offset-4 hover:underline"
                   href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <div class="pt-2">
            <button type="submit"
                    class="w-full inline-flex items-center justify-center rounded-xl bg-[#143529] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#0f2f24] focus:outline-none focus:ring-2 focus:ring-[#143529] focus:ring-offset-2 focus:ring-offset-[#f3efe6]">
                {{ __('Log in') }}
            </button>
        </div>

        @if (Route::has('register'))
            <p class="text-center text-sm text-stone-600 pt-2">
                {{ __('New organisation?') }}
                <a class="font-medium text-[#1a4535] hover:text-[#0f2f24] underline-offset-4 hover:underline"
                   href="{{ route('register') }}" wire:navigate>
                    Start {{ $trialDays }}-day free trial
                </a>
            </p>
            <p class="text-center text-xs text-stone-500 pt-1">
                <a href="{{ route('home') }}" wire:navigate class="hover:text-[#143529] underline-offset-4 hover:underline">View features & pricing</a>
            </p>
        @endif
    </form>
</div>
