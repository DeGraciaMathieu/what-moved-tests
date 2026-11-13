<?php

namespace App\Shipping\Application;

use App\Ordering\Domain\Order;
use App\Ordering\Domain\OrderStatus;
use App\Shipping\Domain\Shipment;
use App\Shipping\Infrastructure\CarrierClient;

final readonly class ShipOrder
{
    public function __construct(private CarrierClient $carrier) {}

    public function __invoke(int $id, Order $order): Shipment
    {
        $shipment = new Shipment($id, $order->customer->address);
        $this->carrier->send($shipment);
        $order->status = OrderStatus::Shipped;

        return $shipment;
    }
}
