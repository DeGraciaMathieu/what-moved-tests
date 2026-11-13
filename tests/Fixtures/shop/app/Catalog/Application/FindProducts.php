<?php

namespace App\Catalog\Application;

use App\Catalog\Domain\ProductRepository;

final readonly class FindProducts
{
    public function __construct(private ProductRepository $products) {}

    public function __invoke(string $query): array
    {
        return $this->products->search(trim($query));
    }
}
