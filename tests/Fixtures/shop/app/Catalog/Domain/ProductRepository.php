<?php

namespace App\Catalog\Domain;

interface ProductRepository
{
    public function find(ProductId $id): ?Product;

    /** @return list<Product> */
    public function search(string $query): array;
}
