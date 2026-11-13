<?php

namespace App\Providers;

use App\Billing\Domain\PaymentGateway;
use App\Billing\Infrastructure\StripeGateway;
use App\Catalog\Domain\ProductRepository;
use App\Catalog\Infrastructure\EloquentProductRepository;
use App\Ordering\Domain\OrderRepository;
use App\Ordering\Infrastructure\EloquentOrderRepository;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public array $bindings = [
        PaymentGateway::class => StripeGateway::class,
        ProductRepository::class => EloquentProductRepository::class,
        OrderRepository::class => EloquentOrderRepository::class,
    ];
}
