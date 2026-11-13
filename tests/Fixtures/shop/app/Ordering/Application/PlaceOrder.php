<?php

namespace App\Ordering\Application;

use App\Catalog\Domain\Product;
use App\Customers\Domain\Customer;
use App\Ordering\Domain\Order;
use App\Ordering\Domain\OrderLine;
use App\Ordering\Domain\OrderRepository;

final readonly class PlaceOrder
{
    public function __construct(private OrderRepository $orders) {}

    /** @param array<int, array{product: Product, quantity: int}> $items */
    public function __invoke(Customer $customer, array $items): Order
    {
        $order = new Order($customer, array_map(
            fn (array $item): OrderLine => new OrderLine($item['product']->id, $item['quantity'], $item['product']->price),
            $items,
        ));
        $this->orders->save($order);

        return $order;
    }
}
