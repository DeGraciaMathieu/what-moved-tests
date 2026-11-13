<?php

namespace App\Catalog\Domain;

final readonly class ProductId
{
    public function __construct(public int $value) {}
}
