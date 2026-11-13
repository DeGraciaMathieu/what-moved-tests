<?php

namespace App\Billing\Domain;

final readonly class Invoice
{
    public function __construct(public string $number, public Money $amount) {}
}
