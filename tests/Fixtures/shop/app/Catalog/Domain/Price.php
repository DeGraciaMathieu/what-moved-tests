<?php

namespace App\Catalog\Domain;

final readonly class Price
{
    public function __construct(public int $cents) {}

    public function add(Price $other): self
    {
        return new self($this->cents + $other->cents);
    }
}
