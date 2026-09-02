<?php

namespace App\Infrastructure\Billing;

use App\Domain\Billing\Models\MpesaTransaction;
use App\Domain\Billing\Models\PlatformSetting;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Organizations\Models\Organization;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MpesaService
{
    public function initiateStkPush(Organization $org, SubscriptionPlan $plan, string $phone): MpesaTransaction
    {
        $phone = $this->normalizePhone($phone);
        $amount = $this->resolveAmountKes($plan);

        $transaction = MpesaTransaction::create([
            'organization_id' => $org->id,
            'subscription_plan_id' => $plan->id,
            'phone' => $phone,
            'amount' => $amount,
            'currency' => 'KES',
            'status' => MpesaTransaction::STATUS_PENDING,
        ]);

        $missing = $this->missingCredentials();
        if ($missing !== []) {
            $message = 'M-Pesa is not configured. Missing: '.implode(', ', $missing).'. Set credentials in Admin → Settings.';

            $transaction->update([
                'status' => MpesaTransaction::STATUS_FAILED,
                'result_description' => $message,
            ]);

            return $transaction->fresh();
        }

        if ($amount < 1) {
            $transaction->update([
                'status' => MpesaTransaction::STATUS_FAILED,
                'result_description' => 'Invalid payment amount. Plan price must be at least 1 KES.',
            ]);

            return $transaction->fresh();
        }

        $token = $this->getAccessToken();

        if (! $token) {
            $transaction->update([
                'status' => MpesaTransaction::STATUS_FAILED,
                'result_description' => 'Failed to obtain M-Pesa access token. Check consumer key/secret and environment.',
            ]);

            return $transaction->fresh();
        }

        $timestamp = now()->format('YmdHis');
        $shortcode = $this->shortcode();
        $password = base64_encode($shortcode.$this->passkey().$timestamp);
        $callbackUrl = $this->callbackUrl();

        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => $shortcode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $callbackUrl,
            'AccountReference' => Str::limit('ORG-'.$org->id, 12, ''),
            'TransactionDesc' => Str::limit('Sub '.$plan->name, 13, ''),
        ];

        try {
            $response = Http::timeout(30)
                ->withToken($token)
                ->acceptJson()
                ->post($this->baseUrl().'/mpesa/stkpush/v1/processrequest', $payload);

            $body = $response->json() ?? [];

            Log::info('M-Pesa STK push response', [
                'organization_id' => $org->id,
                'phone' => $phone,
                'amount' => $amount,
                'status' => $response->status(),
                'body' => $body,
            ]);

            $responseCode = (string) ($body['ResponseCode'] ?? '');

            if ($response->successful() && $responseCode === '0') {
                $transaction->update([
                    'merchant_request_id' => $body['MerchantRequestID'] ?? null,
                    'checkout_request_id' => $body['CheckoutRequestID'] ?? null,
                    'result_description' => $body['CustomerMessage'] ?? $body['ResponseDescription'] ?? 'STK push sent.',
                ]);
            } else {
                $errorMessage = $body['errorMessage']
                    ?? $body['ResponseDescription']
                    ?? $body['errorMessage']
                    ?? ('STK push failed (HTTP '.$response->status().').');

                $transaction->update([
                    'status' => MpesaTransaction::STATUS_FAILED,
                    'result_description' => $errorMessage,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('M-Pesa STK push exception: '.$e->getMessage(), [
                'organization_id' => $org->id,
                'phone' => $phone,
            ]);

            $transaction->update([
                'status' => MpesaTransaction::STATUS_FAILED,
                'result_description' => 'STK push request failed: '.$e->getMessage(),
            ]);
        }

        return $transaction->fresh();
    }

    public function handleCallback(array $payload): void
    {
        Log::info('M-Pesa callback received', ['payload' => $payload]);

        $callback = $payload['Body']['stkCallback'] ?? null;

        if (! $callback) {
            return;
        }

        $checkoutRequestId = $callback['CheckoutRequestID'] ?? null;
        $transaction = MpesaTransaction::where('checkout_request_id', $checkoutRequestId)->first();

        if (! $transaction) {
            Log::warning('M-Pesa callback for unknown checkout request', [
                'checkout_request_id' => $checkoutRequestId,
            ]);

            return;
        }

        if ((int) ($callback['ResultCode'] ?? 1) === 0) {
            $items = collect($callback['CallbackMetadata']['Item'] ?? []);
            $receipt = $items->firstWhere('Name', 'MpesaReceiptNumber')['Value'] ?? null;

            $transaction->update([
                'status' => MpesaTransaction::STATUS_COMPLETED,
                'mpesa_receipt_number' => $receipt,
                'result_description' => $callback['ResultDesc'] ?? 'Success',
            ]);

            $org = $transaction->organization;
            $plan = $transaction->subscriptionPlan;

            if ($org && $plan) {
                $org->update([
                    'status' => Organization::STATUS_ACTIVE,
                    'subscription_plan_id' => $plan->id,
                    'trial_ends_at' => null,
                ]);
            }
        } else {
            $transaction->update([
                'status' => MpesaTransaction::STATUS_FAILED,
                'result_description' => $callback['ResultDesc'] ?? 'Payment failed.',
            ]);
        }
    }

    public function testConnection(): bool
    {
        return $this->getAccessToken() !== null;
    }

    public function isConfigured(): bool
    {
        return $this->missingCredentials() === [];
    }

    /**
     * @return list<string>
     */
    public function missingCredentials(): array
    {
        $missing = [];

        if (! $this->consumerKey()) {
            $missing[] = 'consumer key';
        }
        if (! $this->consumerSecret()) {
            $missing[] = 'consumer secret';
        }
        if (! $this->passkey()) {
            $missing[] = 'passkey';
        }
        if (! $this->shortcode()) {
            $missing[] = 'shortcode';
        }

        return $missing;
    }

    private function resolveAmountKes(SubscriptionPlan $plan): int
    {
        $price = (float) $plan->price;
        $currency = strtoupper($plan->currency ?? 'KES');

        if ($currency === 'KES') {
            return max(1, (int) round($price));
        }

        $rate = (float) PlatformSetting::getValue('mpesa_usd_to_kes_rate', 130);

        return max(1, (int) round($price * $rate));
    }

    private function getAccessToken(): ?string
    {
        $key = $this->consumerKey();
        $secret = $this->consumerSecret();

        if (! $key || ! $secret) {
            return null;
        }

        try {
            $response = Http::timeout(20)
                ->withBasicAuth($key, $secret)
                ->get($this->baseUrl().'/oauth/v1/generate?grant_type=client_credentials');

            if (! $response->successful()) {
                Log::error('M-Pesa token HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json('access_token');
        } catch (\Throwable $e) {
            Log::error('M-Pesa token error: '.$e->getMessage());

            return null;
        }
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone) ?? '';

        if (Str::startsWith($phone, '0')) {
            $phone = '254'.substr($phone, 1);
        }

        if (Str::startsWith($phone, '+')) {
            $phone = ltrim($phone, '+');
        }

        if (Str::startsWith($phone, '7') || Str::startsWith($phone, '1')) {
            $phone = '254'.$phone;
        }

        return $phone;
    }

    private function callbackUrl(): string
    {
        $configured = PlatformSetting::getValue('mpesa_callback_url');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return route('mpesa.callback', absolute: true);
    }

    private function baseUrl(): string
    {
        $env = PlatformSetting::getValue('mpesa_environment', 'sandbox');

        return $env === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    private function consumerKey(): ?string
    {
        return PlatformSetting::getValue('mpesa_consumer_key');
    }

    private function consumerSecret(): ?string
    {
        return PlatformSetting::getValue('mpesa_consumer_secret');
    }

    private function passkey(): ?string
    {
        return PlatformSetting::getValue('mpesa_passkey');
    }

    private function shortcode(): ?string
    {
        return PlatformSetting::getValue('mpesa_shortcode');
    }
}
