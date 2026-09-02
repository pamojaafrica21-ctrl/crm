<?php

use App\Application\Billing\StripePlanSyncService;
use App\Domain\Billing\Models\PlatformSetting;
use App\Infrastructure\Billing\MpesaService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    public string $stripe_public_key = '';
    public string $stripe_secret_key = '';
    public string $mpesa_consumer_key = '';
    public string $mpesa_consumer_secret = '';
    public string $mpesa_passkey = '';
    public string $mpesa_shortcode = '';
    public string $mpesa_environment = 'sandbox';
    public string $mpesa_callback_url = '';
    public string $mpesa_usd_to_kes_rate = '130';
    public int $default_trial_days = 14;

    public ?string $testResult = null;

    public function mount(): void
    {
        $this->stripe_public_key = PlatformSetting::getValue('stripe_public_key', '') ?? '';
        $this->stripe_secret_key = PlatformSetting::getValue('stripe_secret_key') ? '••••••••' : '';
        $this->mpesa_consumer_key = PlatformSetting::getValue('mpesa_consumer_key') ? '••••••••' : '';
        $this->mpesa_consumer_secret = PlatformSetting::getValue('mpesa_consumer_secret') ? '••••••••' : '';
        $this->mpesa_passkey = PlatformSetting::getValue('mpesa_passkey') ? '••••••••' : '';
        $this->mpesa_shortcode = PlatformSetting::getValue('mpesa_shortcode', '') ?? '';
        $this->mpesa_environment = PlatformSetting::getValue('mpesa_environment', 'sandbox') ?? 'sandbox';
        $this->mpesa_callback_url = PlatformSetting::getValue('mpesa_callback_url', '') ?? '';
        $this->mpesa_usd_to_kes_rate = (string) (PlatformSetting::getValue('mpesa_usd_to_kes_rate', '130') ?? '130');
        $this->default_trial_days = (int) PlatformSetting::getValue('default_trial_days', 14);
    }

    public function save(): void
    {
        PlatformSetting::setValue('stripe_public_key', $this->stripe_public_key);

        if ($this->stripe_secret_key && $this->stripe_secret_key !== '••••••••') {
            PlatformSetting::setValue('stripe_secret_key', $this->stripe_secret_key, encrypt: true);
        }

        if ($this->mpesa_consumer_key && $this->mpesa_consumer_key !== '••••••••') {
            PlatformSetting::setValue('mpesa_consumer_key', $this->mpesa_consumer_key, encrypt: true);
        }

        if ($this->mpesa_consumer_secret && $this->mpesa_consumer_secret !== '••••••••') {
            PlatformSetting::setValue('mpesa_consumer_secret', $this->mpesa_consumer_secret, encrypt: true);
        }

        if ($this->mpesa_passkey && $this->mpesa_passkey !== '••••••••') {
            PlatformSetting::setValue('mpesa_passkey', $this->mpesa_passkey, encrypt: true);
        }

        PlatformSetting::setValue('mpesa_shortcode', $this->mpesa_shortcode);
        PlatformSetting::setValue('mpesa_environment', $this->mpesa_environment);
        PlatformSetting::setValue('mpesa_callback_url', $this->mpesa_callback_url);
        PlatformSetting::setValue('mpesa_usd_to_kes_rate', $this->mpesa_usd_to_kes_rate);
        PlatformSetting::setValue('default_trial_days', (string) $this->default_trial_days);

        session()->flash('status', 'Settings saved successfully.');
    }

    public function testMpesa(MpesaService $mpesa): void
    {
        if (! $mpesa->isConfigured()) {
            $this->testResult = 'M-Pesa is not fully configured. Missing: '.implode(', ', $mpesa->missingCredentials()).'.';

            return;
        }

        $this->testResult = $mpesa->testConnection()
            ? 'M-Pesa connection successful. STK push is ready to send.'
            : 'M-Pesa connection failed. Check your credentials and environment.';
    }

    public function testStripe(StripePlanSyncService $stripe): void
    {
        try {
            $stripe->configureStripe();
            $secret = PlatformSetting::getValue('stripe_secret_key');
            if (! $secret) {
                $this->testResult = 'Stripe secret key not configured.';

                return;
            }
            $client = new \Stripe\StripeClient($secret);
            $client->products->all(['limit' => 1]);
            $this->testResult = 'Stripe connection successful.';
        } catch (\Throwable $e) {
            $this->testResult = 'Stripe connection failed: '.$e->getMessage();
        }
    }
}; ?>

<div class="space-y-6 max-w-2xl">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Platform Settings</h1>
        <p class="mt-1 text-sm text-slate-500">Configure payment providers and default trial settings.</p>
    </div>

    @if (session('status'))
        <div class="bg-green-50 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('status') }}</div>
    @endif

    @if ($testResult)
        <div class="bg-blue-50 text-blue-700 px-4 py-3 rounded-lg text-sm">{{ $testResult }}</div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
            <h2 class="font-semibold text-slate-900">Stripe</h2>
            <div>
                <label class="block text-sm font-medium text-slate-700">Publishable Key</label>
                <input type="text" wire:model="stripe_public_key" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Secret Key</label>
                <input type="password" wire:model="stripe_secret_key" placeholder="sk_..." class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
            </div>
            <button type="button" wire:click="testStripe" class="text-sm text-violet-600 hover:text-violet-700">Test Stripe connection</button>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
            <h2 class="font-semibold text-slate-900">M-Pesa (Safaricom Daraja)</h2>
            <p class="text-sm text-slate-500">STK push requires valid Daraja credentials. For local testing, expose your app with a public HTTPS URL (e.g. ngrok) and set the callback URL below.</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Consumer Key</label>
                    <input type="password" wire:model="mpesa_consumer_key" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Consumer Secret</label>
                    <input type="password" wire:model="mpesa_consumer_secret" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Passkey</label>
                    <input type="password" wire:model="mpesa_passkey" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Shortcode / Paybill</label>
                    <input type="text" wire:model="mpesa_shortcode" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Environment</label>
                    <select wire:model="mpesa_environment" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                        <option value="sandbox">Sandbox</option>
                        <option value="production">Production</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">USD → KES Rate</label>
                    <input type="number" step="0.01" wire:model="mpesa_usd_to_kes_rate" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                    <p class="mt-1 text-xs text-slate-500">Used when a plan is priced in USD.</p>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">STK Callback URL (optional)</label>
                <input type="url" wire:model="mpesa_callback_url" placeholder="{{ route('mpesa.callback') }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
                <p class="mt-1 text-xs text-slate-500">Must be a public HTTPS URL Safaricom can reach. Leave blank to use {{ route('mpesa.callback') }}.</p>
            </div>
            <button type="button" wire:click="testMpesa" class="text-sm text-violet-600 hover:text-violet-700">Test M-Pesa connection</button>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
            <h2 class="font-semibold text-slate-900">Defaults</h2>
            <div>
                <label class="block text-sm font-medium text-slate-700">Default Trial Days (for new signups)</label>
                <input type="number" wire:model="default_trial_days" min="0" max="365" class="mt-1 block w-full rounded-lg border-slate-200 text-sm">
            </div>
        </div>

        <button type="submit" class="px-4 py-2 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700">
            Save Settings
        </button>
    </form>
</div>
