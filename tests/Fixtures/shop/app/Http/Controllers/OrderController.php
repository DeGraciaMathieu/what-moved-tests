<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaceOrderRequest;
use App\Ordering\Application\CancelOrder;
use App\Ordering\Application\PlaceOrder;
use App\Policies\OrderPolicy;
use Illuminate\Support\Facades\Gate;

final class OrderController
{
    public function store(PlaceOrderRequest $request, PlaceOrder $place): mixed
    {
        return $place($request->customer(), $request->items());
    }

    public function destroy(int $order, CancelOrder $cancel): void
    {
        Gate::authorize('cancel', [OrderPolicy::class, $order]);
        $cancel($order);
    }
}
