<?php

namespace App\Catalog\Infrastructure;

use App\Catalog\Domain\Price;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductId;
use App\Catalog\Domain\ProductRepository;
use Illuminate\Support\Facades\DB;

final class EloquentProductRepository implements ProductRepository
{
    public function find(ProductId $id): ?Product
    {
        $row = DB::table('products')->find($id->value);

        return $row === null ? null : new Product($id, $row->name, new Price($row->price));
    }

    public function search(string $query): array
    {
        return DB::table('products')->where('name', 'like', "%{$query}%")->get()
            ->map(fn (object $row): Product => new Product(new ProductId($row->id), $row->name, new Price($row->price)))
            ->all();
    }
}
