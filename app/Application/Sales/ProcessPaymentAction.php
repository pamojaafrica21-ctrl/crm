<?php

namespace App\Application\Sales;

use App\Domain\Sales\Models\Invoice;
use App\Domain\Sales\Models\Payment;
use App\Infrastructure\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ProcessPaymentAction
{
    public function __construct(
        private PaymentGatewayManager $gateways,
    ) {}

    public function execute(
        Invoice $invoice,
        float $amount,
        string $method = 'manual',
        ?string $driver = null,
        ?int $recordedBy = null,
        ?string $notes = null,
    ): Payment {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $outstanding = $invoice->outstandingBalance();
        if ($amount > $outstanding + 0.01) {
            throw new InvalidArgumentException('Payment exceeds outstanding balance.');
        }

        $gateway = $this->gateways->driver($driver ?? $method);
        $result = $gateway->charge([
            'amount' => $amount,
            'currency' => $invoice->currency,
            'reference' => $invoice->invoice_number,
            'description' => "Payment for invoice {$invoice->invoice_number}",
            'metadata' => [
                'invoice_id' => $invoice->id,
                'property_id' => $invoice->property_id,
            ],
        ]);

        if (! ($result['success'] ?? false)) {
            throw new RuntimeException($result['message'] ?? 'Payment failed.');
        }

        return DB::transaction(function () use ($invoice, $amount, $method, $recordedBy, $notes, $result) {
            return Payment::create([
                'property_id' => $invoice->property_id,
                'invoice_id' => $invoice->id,
                'recorded_by' => $recordedBy,
                'amount' => $amount,
                'currency' => $invoice->currency,
                'payment_method' => $method,
                'reference' => $result['reference'] ?? null,
                'payment_date' => now()->toDateString(),
                'notes' => $notes ?? ($result['message'] ?? null),
            ]);
        });
    }
}
