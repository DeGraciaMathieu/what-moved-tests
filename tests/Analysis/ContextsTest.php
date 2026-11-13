<?php

use Tests\Support\Project;
use Tests\Support\WhatMoved;

/*
 * The context of a class is the first segment of its namespace, unless the config declares a prefix for it.
 */

function laravelApp(): Project
{
    return Project::with([
        'app/Http/Controllers/OrderController.php' => '<?php namespace App\Http\Controllers; final class OrderController {}',
        'app/Http/Requests/PlaceOrderRequest.php' => '<?php namespace App\Http\Requests; final class PlaceOrderRequest {}',
        'app/Policies/OrderPolicy.php' => '<?php namespace App\Policies; final class OrderPolicy {}',
        'app/Providers/AppServiceProvider.php' => '<?php namespace App\Providers; final class AppServiceProvider {}',
        'app/Ordering/Cart/Domain/Cart.php' => '<?php namespace App\Ordering\Cart\Domain; final class Cart {}',
        'app/Ordering/Checkout/Domain/Order.php' => '<?php namespace App\Ordering\Checkout\Domain; final class Order {}',
        'app/Ordering/Checkout/Payment/Domain/Payment.php' => '<?php namespace App\Ordering\Checkout\Payment\Domain; final class Payment {}',
        'app/Ordering/Domain/Customer.php' => '<?php namespace App\Ordering\Domain; final class Customer {}',
    ]);
}

it('puts each class in the first segment of its namespace by default', function () {
    expect(WhatMoved::analyse(laravelApp()->root, config: null)->contexts())->toBe([
        'App\Http\Controllers\OrderController' => 'Http',
        'App\Http\Requests\PlaceOrderRequest' => 'Http',
        'App\Ordering\Cart\Domain\Cart' => 'Ordering',
        'App\Ordering\Checkout\Domain\Order' => 'Ordering',
        'App\Ordering\Checkout\Payment\Domain\Payment' => 'Ordering',
        'App\Ordering\Domain\Customer' => 'Ordering',
        'App\Policies\OrderPolicy' => 'Policies',
        'App\Providers\AppServiceProvider' => 'Providers',
    ]);
});

it('merges the folders a context declares as its prefixes', function () {
    $contexts = WhatMoved::analyse(laravelApp()->root, ['contexts' => ['Http' => ['prefixes' => ['Http', 'Policies']]]])->contexts();

    expect($contexts['App\Policies\OrderPolicy'])->toBe('Http')
        ->and($contexts['App\Http\Controllers\OrderController'])->toBe('Http');
});

it('splits a context along the prefixes of its modules, the longest prefix first', function () {
    $contexts = WhatMoved::analyse(laravelApp()->root, ['contexts' => [
        'Cart' => ['prefixes' => ['Ordering\Cart']],
        'Checkout' => ['prefixes' => ['Ordering\Checkout']],
        'Payment' => ['prefixes' => ['Ordering\Checkout\Payment']],
    ]])->contexts();

    expect($contexts['App\Ordering\Cart\Domain\Cart'])->toBe('Cart')
        ->and($contexts['App\Ordering\Checkout\Domain\Order'])->toBe('Checkout')
        ->and($contexts['App\Ordering\Checkout\Payment\Domain\Payment'])->toBe('Payment')
        ->and($contexts['App\Ordering\Domain\Customer'])->toBe('Ordering');
});

it('does not take a prefix for the start of a longer segment', function () {
    $project = Project::with([
        'app/Order/Order.php' => '<?php namespace App\Order; final class Order {}',
        'app/Ordering/Cart.php' => '<?php namespace App\Ordering; final class Cart {}',
    ]);

    expect(WhatMoved::analyse($project->root, ['contexts' => ['Sales' => ['prefixes' => ['Order']]]])->contexts())
        ->toBe(['App\Order\Order' => 'Sales', 'App\Ordering\Cart' => 'Ordering']);
});

it('leaves out the classes of an excluded prefix, and the dependencies to them', function () {
    $project = laravelApp()->write([
        'app/Http/Controllers/OrderController.php' => <<<'PHP'
            <?php
            namespace App\Http\Controllers;
            use App\Providers\AppServiceProvider;
            final class OrderController { public function __invoke(AppServiceProvider $provider): void {} }
            PHP,
    ]);
    $analysis = WhatMoved::analyse($project->root, ['exclude' => ['Providers']]);

    expect($analysis->classes())->not->toContain('App\Providers\AppServiceProvider')
        ->and($analysis->dependenciesOf('App\Http\Controllers\OrderController'))->toBe([]);
});

it('reads a context declared only for its position from its first segment', function () {
    expect(WhatMoved::analyse(laravelApp()->root, ['contexts' => ['Ordering' => ['position' => [100, 100]]]])->contexts()['App\Ordering\Domain\Customer'])
        ->toBe('Ordering');
});

it('maps another folder under another root namespace', function () {
    $project = Project::with([
        'src/Billing/Invoice.php' => '<?php namespace Acme\Billing; final class Invoice {}',
        'src/Shipping/Shipment.php' => <<<'PHP'
            <?php
            namespace Acme\Shipping;
            use Acme\Billing\Invoice;
            final class Shipment { public function __construct(private Invoice $invoice) {} }
            PHP,
        'app/Http/Controller.php' => '<?php namespace App\Http; final class Controller {}',
    ]);
    $analysis = WhatMoved::analyse($project->root, source: 'src', namespace: 'Acme');

    expect($analysis->contexts())->toBe(['Acme\Billing\Invoice' => 'Billing', 'Acme\Shipping\Shipment' => 'Shipping'])
        ->and($analysis->dependenciesOf('Acme\Shipping\Shipment'))->toBe(['Acme\Billing\Invoice']);
});
