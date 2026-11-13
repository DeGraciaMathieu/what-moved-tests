<?php

namespace App\Ordering\Application;

use App\Ordering\Domain\OrderRepository;

final readonly class CancelOrder
{
    public function __construct(private OrderRepository $orders) {}

    public function __invoke(int $id): void
    {
        $order = $this->orders->find($id) ?? throw new \DomainException("No order {$id}.");
        $order->cancel();
        $this->orders->save($order);
    }
}
