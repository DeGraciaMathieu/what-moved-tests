<?php

namespace App\Ordering\Domain;

interface OrderRepository
{
    public function save(Order $order): void;

    public function find(int $id): ?Order;
}
