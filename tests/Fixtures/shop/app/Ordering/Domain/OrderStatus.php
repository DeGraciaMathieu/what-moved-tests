<?php

namespace App\Ordering\Domain;

enum OrderStatus: string
{
    case Placed = 'placed';
    case Cancelled = 'cancelled';
    case Shipped = 'shipped';
}
