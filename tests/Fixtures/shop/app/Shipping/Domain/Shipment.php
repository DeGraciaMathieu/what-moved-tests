<?php

namespace App\Shipping\Domain;

use App\Customers\Domain\Address;

final readonly class Shipment
{
    public function __construct(public int $order, public Address $to) {}
}
