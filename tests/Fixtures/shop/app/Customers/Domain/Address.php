<?php

namespace App\Customers\Domain;

final readonly class Address
{
    public function __construct(public string $street, public string $city, public string $country) {}
}
