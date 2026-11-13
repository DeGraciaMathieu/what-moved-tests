<?php

namespace App\Shipping\Infrastructure;

use App\Shipping\Domain\Shipment;
use Illuminate\Support\Facades\Http;

final class CarrierClient
{
    public function send(Shipment $shipment): void
    {
        Http::post('https://carrier.example/shipments', ['city' => $shipment->to->city]);
    }
}
