<?php

namespace App\Catalog\Domain;

final readonly class Product
{
    public function __construct(
        public ProductId $id,
        public string $name,
        public Price $price,
    ) {}
}
