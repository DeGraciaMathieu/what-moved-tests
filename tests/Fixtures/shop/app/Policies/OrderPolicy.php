<?php

namespace App\Policies;

use App\Customers\Domain\Customer;
use App\Ordering\Domain\Order;

final class OrderPolicy
{
    public function cancel(Customer $customer, Order $order): bool
    {
        return $order->customer->id === $customer->id;
    }
}
