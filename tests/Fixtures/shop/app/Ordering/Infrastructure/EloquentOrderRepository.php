<?php

namespace App\Ordering\Infrastructure;

use App\Ordering\Domain\Order;
use App\Ordering\Domain\OrderRepository;
use Illuminate\Support\Facades\Cache;

final class EloquentOrderRepository implements OrderRepository
{
    public function save(Order $order): void
    {
        Cache::put('order:'.spl_object_id($order), $order);
    }

    public function find(int $id): ?Order
    {
        return Cache::get("order:{$id}");
    }
}
