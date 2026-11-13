<?php

namespace App\Billing\Domain;

final readonly class Money
{
    public function __construct(public int $cents, public string $currency = 'EUR') {}
}
