<?php

namespace App\Application\Sales;

use App\Domain\Sales\Models\Invoice;
use App\Domain\Sales\Models\InvoiceLine;
use App\Domain\Sales\Models\Quote;
use App\Domain\Shared\Enums\InvoiceStatus;
use App\Domain\Shared\Enums\QuoteStatus;
use Illuminate\Support\Facades\DB;

class ConvertQuoteToInvoiceAction
{
    public function __construct(
        private InvoiceNumberGenerator $invoiceNumbers,
    ) {}

    public function execute(Quote $quote, int $userId): Invoice
    {
        return DB::transaction(function () use ($quote, $userId) {
            $invoice = Invoice::create([
                'property_id' => $quote->property_id,
                'customer_id' => $quote->customer_id,
                'quote_id' => $quote->id,
                'created_by' => $userId,
                'invoice_number' => $this->invoiceNumbers->next($quote->property_id),
                'status' => InvoiceStatus::Sent,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'subtotal' => $quote->subtotal,
                'tax_amount' => $quote->tax_amount,
                'total_amount' => $quote->total_amount,
                'currency' => $quote->currency,
                'notes' => $quote->notes,
                'terms' => $quote->terms,
            ]);

            foreach ($quote->lines as $line) {
                InvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'tax_rate' => $line->tax_rate,
                    'line_total' => $line->line_total,
                    'sort_order' => $line->sort_order,
                ]);
            }

            $quote->update(['status' => QuoteStatus::Accepted]);

            activity()->performedOn($invoice)->log('Quote converted to invoice');

            return $invoice;
        });
    }
}
