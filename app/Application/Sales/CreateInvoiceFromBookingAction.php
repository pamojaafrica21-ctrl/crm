<?php

namespace App\Application\Sales;

use App\Domain\Customers\Models\Reservation;
use App\Domain\Sales\Models\Invoice;
use App\Domain\Sales\Models\InvoiceLine;
use App\Domain\Shared\Enums\InvoiceStatus;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateInvoiceFromBookingAction
{
    public function __construct(
        private InvoiceNumberGenerator $invoiceNumbers,
    ) {}

    public function execute(string $groupCode, ?int $createdBy = null): Invoice
    {
        $reservations = Reservation::query()
            ->with(['roomType', 'extraServices'])
            ->where('group_code', $groupCode)
            ->orderBy('id')
            ->get();

        if ($reservations->isEmpty()) {
            throw new InvalidArgumentException("No reservations found for group {$groupCode}.");
        }

        if ($reservations->contains(fn (Reservation $r) => $r->invoice_id)) {
            throw new InvalidArgumentException('An invoice already exists for this booking.');
        }

        return DB::transaction(function () use ($reservations, $groupCode, $createdBy) {
            $first = $reservations->first();
            $taxRate = (float) config('portal.tax_rate', 10);
            $subtotal = 0.0;
            $taxAmount = (float) $reservations->sum('tax_amount');
            $discount = (float) $reservations->sum('discount_amount');
            $sort = 0;
            $lines = [];

            foreach ($reservations as $reservation) {
                $nights = $reservation->nights();
                $roomPrice = (float) ($reservation->roomType?->base_price ?? 0);
                $roomLine = $roomPrice * $nights;
                $subtotal += $roomLine;

                $lines[] = [
                    'description' => sprintf(
                        '%s (%s – %s, %d night%s)',
                        $reservation->room_type ?: ($reservation->roomType?->name ?? 'Room'),
                        $reservation->check_in->format('M j'),
                        $reservation->check_out->format('M j, Y'),
                        $nights,
                        $nights === 1 ? '' : 's'
                    ),
                    'quantity' => $nights,
                    'unit_price' => $roomPrice,
                    'tax_rate' => $taxRate,
                    'line_total' => $roomLine,
                    'sort_order' => $sort++,
                ];

                foreach ($reservation->extraServices as $extra) {
                    $extraTotal = (float) $extra->pivot->total;
                    $subtotal += $extraTotal;
                    $lines[] = [
                        'description' => $extra->name,
                        'quantity' => (float) $extra->pivot->quantity,
                        'unit_price' => (float) $extra->pivot->unit_price,
                        'tax_rate' => $taxRate,
                        'line_total' => $extraTotal,
                        'sort_order' => $sort++,
                    ];
                }
            }

            if ($discount > 0) {
                $subtotal = max(0, $subtotal - $discount);
                $lines[] = [
                    'description' => 'Discount'.($first->coupon_code ? " ({$first->coupon_code})" : ''),
                    'quantity' => 1,
                    'unit_price' => -$discount,
                    'tax_rate' => 0,
                    'line_total' => -$discount,
                    'sort_order' => $sort++,
                ];
            }

            $total = round($subtotal + $taxAmount, 2);

            $invoice = Invoice::create([
                'property_id' => $first->property_id,
                'customer_id' => $first->customer_id,
                'created_by' => $createdBy,
                'invoice_number' => $this->invoiceNumbers->next($first->property_id),
                'status' => InvoiceStatus::Sent,
                'issue_date' => now()->toDateString(),
                'due_date' => $first->check_in->toDateString(),
                'subtotal' => round($subtotal, 2),
                'tax_amount' => round($taxAmount, 2),
                'total_amount' => $total,
                'currency' => $first->currency,
                'notes' => "Booking {$groupCode}",
            ]);

            foreach ($lines as $line) {
                InvoiceLine::create(array_merge($line, ['invoice_id' => $invoice->id]));
            }

            Reservation::query()
                ->where('group_code', $groupCode)
                ->update(['invoice_id' => $invoice->id]);

            activity()->performedOn($invoice)->log("Invoice created from booking {$groupCode}");

            return $invoice->load('lines');
        });
    }
}
