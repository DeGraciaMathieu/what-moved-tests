<?php

namespace App\Customers\Domain;

final class Customer
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public Address $address,
    ) {}
}
