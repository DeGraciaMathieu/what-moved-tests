<?php

namespace App\Ordering\Domain;

use App\Catalog\Domain\Price;
use App\Customers\Domain\Customer;

final class Order
{
    public OrderStatus $status = OrderStatus::Placed;

    /** @param list<OrderLine> $lines */
    public function __construct(public readonly Customer $customer, public readonly array $lines) {}

    public function total(): Price
    {
        return array_reduce($this->lines, fn (Price $total, OrderLine $line): Price => $total->add(
            new Price($line->unitPrice->cents * $line->quantity),
        ), new Price(0));
    }

    public function cancel(): void
    {
        $this->status = OrderStatus::Cancelled;
    }
}
