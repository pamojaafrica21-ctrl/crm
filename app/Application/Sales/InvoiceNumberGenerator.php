<?php

namespace App\Application\Sales;

use App\Domain\Sales\Models\Invoice;

class InvoiceNumberGenerator
{
    public function next(int $propertyId): string
    {
        $count = Invoice::withoutGlobalScope('property')
            ->where('property_id', $propertyId)
            ->count() + 1;

        return sprintf('INV-%04d-%05d', $propertyId, $count);
    }
}
