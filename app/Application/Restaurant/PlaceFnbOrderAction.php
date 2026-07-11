<?php

namespace App\Application\Restaurant;

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\FnbOrder;
use App\Domain\Properties\Services\PropertyContext;
use App\Domain\Restaurant\Models\FnbOrderItem;
use App\Domain\Restaurant\Models\MenuItem;
use App\Infrastructure\Notifications\GuestNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PlaceFnbOrderAction
{
    public function __construct(
        private PropertyContext $propertyContext,
    ) {}

    /**
     * @param  array<int, array{menu_item_id: int, quantity: int}>  $items
     */
    public function execute(
        Customer $customer,
        array $items,
        string $orderType = 'dine_in',
        ?string $specialRequests = null,
        ?int $reservationId = null,
        float $discountAmount = 0,
    ): FnbOrder {
        if ($items === []) {
            throw new InvalidArgumentException('Order must contain at least one item.');
        }

        if (! in_array($orderType, ['dine_in', 'takeaway', 'room_service'], true)) {
            throw new InvalidArgumentException('Invalid order type.');
        }

        $propertyId = $this->propertyContext->id();
        $currency = $this->propertyContext->property()?->currency ?? 'USD';
        $taxRate = (float) config('portal.tax_rate', 10) / 100;

        return DB::transaction(function () use (
            $customer,
            $items,
            $orderType,
            $specialRequests,
            $reservationId,
            $discountAmount,
            $propertyId,
            $currency,
            $taxRate,
        ) {
            $subtotal = 0.0;
            $resolved = [];

            foreach ($items as $row) {
                $menuItem = MenuItem::query()
                    ->where('is_available', true)
                    ->findOrFail($row['menu_item_id']);
                $qty = max(1, (int) ($row['quantity'] ?? 1));
                $line = (float) $menuItem->price * $qty;
                $subtotal += $line;
                $resolved[] = [
                    'menu_item' => $menuItem,
                    'quantity' => $qty,
                    'unit_price' => (float) $menuItem->price,
                    'line_total' => $line,
                ];
            }

            $discount = min($discountAmount, $subtotal);
            $taxable = max(0, $subtotal - $discount);
            $tax = round($taxable * $taxRate, 2);
            $total = round($taxable + $tax, 2);

            $order = FnbOrder::create([
                'property_id' => $propertyId,
                'customer_id' => $customer->id,
                'reservation_id' => $reservationId,
                'order_number' => 'FNB-'.strtoupper(Str::random(8)),
                'outlet' => 'portal',
                'order_type' => $orderType,
                'subtotal' => round($subtotal, 2),
                'tax_amount' => $tax,
                'discount_amount' => round($discount, 2),
                'total_amount' => $total,
                'currency' => $currency,
                'status' => 'pending',
                'special_requests' => $specialRequests,
                'ordered_at' => now(),
            ]);

            foreach ($resolved as $row) {
                FnbOrderItem::create([
                    'fnb_order_id' => $order->id,
                    'menu_item_id' => $row['menu_item']->id,
                    'name' => $row['menu_item']->name,
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'line_total' => $row['line_total'],
                ]);
            }

            GuestNotification::send(
                $customer,
                'Order received',
                "Your order {$order->order_number} has been placed. Total: {$currency} ".number_format($total, 2),
                route('portal.dashboard.orders')
            );

            return $order->load('items');
        });
    }
}
