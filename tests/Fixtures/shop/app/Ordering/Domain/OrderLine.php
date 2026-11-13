<?php

namespace App\Ordering\Domain;

use App\Catalog\Domain\Price;
use App\Catalog\Domain\ProductId;

final readonly class OrderLine
{
    public function __construct(public ProductId $product, public int $quantity, public Price $unitPrice) {}
}
