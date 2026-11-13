<?php

use Tests\Support\Project;
use Tests\Support\WhatMoved;

/*
 * A class uses another one when the name of the other appears in its code once resolved.
 * Each test writes the code a developer would write, and reads which dependencies the analysis found.
 */

/**
 * @return list<string> what ShipOrder, written as given, uses of a project that also holds an Invoice
 */
function usesOf(string $code): array
{
    $project = Project::with([
        'app/Billing/Domain/Invoice.php' => <<<'PHP'
            <?php

            namespace App\Billing\Domain;

            final class Invoice
            {
                public static function draft(): self
                {
                    return new self;
                }
            }
            PHP,
        'app/Shipping/Application/ShipOrder.php' => $code,
    ]);

    return WhatMoved::analyse($project->root)->dependenciesOf('App\Shipping\Application\ShipOrder');
}

it('finds a dependency in a type', function (string $code) {
    expect(usesOf($code))->toBe(['App\Billing\Domain\Invoice']);
})->with([
    'parameter' => <<<'PHP'
        <?php
        namespace App\Shipping\Application;
        use App\Billing\Domain\Invoice;
        final class ShipOrder { public function __invoke(Invoice $invoice): void {} }
        PHP,
    'return' => <<<'PHP'
        <?php
        namespace App\Shipping\Application;
        use App\Billing\Domain\Invoice;
        final class ShipOrder { public function invoice(): ?Invoice { return null; } }
        PHP,
    'promoted property' => <<<'PHP'
        <?php
        namespace App\Shipping\Application;
        use App\Billing\Domain\Invoice;
        final readonly class ShipOrder { public function __construct(private Invoice $invoice) {} }
        PHP,
    'union type' => <<<'PHP'
        <?php
        namespace App\Shipping\Application;
        use App\Billing\Domain\Invoice;
        final class ShipOrder { public function __invoke(Invoice|string $invoice): void {} }
        PHP,
]);

it('finds a dependency in what the code does with a class', function (string $code) {
    expect(usesOf($code))->toBe(['App\Billing\Domain\Invoice']);
})->with([
    'new' => <<<'PHP'
        <?php
        namespace App\Shipping\Application;
        use App\Billing\Domain\Invoice;
        final class ShipOrder { public function __invoke(): object { return new Invoice; } }
        PHP,
    'static call' => <<<'PHP'
        <?php
        namespace App\Shipping\Application;
        use App\Billing\Domain\Invoice;
        final class ShipOrder { public function __invoke(): object { return Invoice::draft(); } }
        PHP,
    'instanceof' => <<<'PHP'
        <?php
        namespace App\Shipping\Application;
        use App\Billing\Domain\Invoice;
        final class ShipOrder { public function __invoke(object $o): bool { return $o instanceof Invoice; } }
        PHP,
    '::class' => <<<'PHP'
        <?php
        namespace App\Shipping\Application;
        use App\Billing\Domain\Invoice;
        final class ShipOrder { public function __invoke(): string { return Invoice::class; } }
        PHP,
    'attribute' => <<<'PHP'
        <?php
        namespace App\Shipping\Application;
        use App\Billing\Domain\Invoice;
        #[Invoice]
        final class ShipOrder {}
        PHP,
    'fully qualified name without import' => <<<'PHP'
        <?php
        namespace App\Shipping\Application;
        final class ShipOrder { public function __invoke(): object { return new \App\Billing\Domain\Invoice; } }
        PHP,
    'aliased import' => <<<'PHP'
        <?php
        namespace App\Shipping\Application;
        use App\Billing\Domain\Invoice as Bill;
        final class ShipOrder { public function __invoke(Bill $bill): void {} }
        PHP,
]);

it('finds a dependency in what a class is built from', function () {
    $project = Project::with([
        'app/Billing/Domain/Document.php' => '<?php namespace App\Billing\Domain; abstract class Document {}',
        'app/Billing/Domain/Payable.php' => '<?php namespace App\Billing\Domain; interface Payable {}',
        'app/Billing/Domain/HasTotal.php' => '<?php namespace App\Billing\Domain; trait HasTotal {}',
        'app/Billing/Domain/Invoice.php' => <<<'PHP'
            <?php
            namespace App\Billing\Domain;
            final class Invoice extends Document implements Payable { use HasTotal; }
            PHP,
    ]);

    expect(WhatMoved::analyse($project->root)->dependenciesOf('App\Billing\Domain\Invoice'))
        ->toEqualCanonicalizing(['App\Billing\Domain\Document', 'App\Billing\Domain\Payable', 'App\Billing\Domain\HasTotal']);
});

it('finds a dependency in a caught exception', function () {
    $project = Project::with([
        'app/Billing/Domain/PaymentFailed.php' => '<?php namespace App\Billing\Domain; final class PaymentFailed extends \Exception {}',
        'app/Shipping/Application/ShipOrder.php' => <<<'PHP'
            <?php
            namespace App\Shipping\Application;
            use App\Billing\Domain\PaymentFailed;
            final class ShipOrder
            {
                public function __invoke(callable $pay): void
                {
                    try { $pay(); } catch (PaymentFailed) {}
                }
            }
            PHP,
    ]);

    expect(WhatMoved::analyse($project->root)->dependenciesOf('App\Shipping\Application\ShipOrder'))->toBe(['App\Billing\Domain\PaymentFailed']);
});

it('finds the dependencies of enums and interfaces too', function () {
    $project = Project::with([
        'app/Billing/Domain/Money.php' => '<?php namespace App\Billing\Domain; final readonly class Money {}',
        'app/Billing/Domain/InvoiceStatus.php' => <<<'PHP'
            <?php
            namespace App\Billing\Domain;
            enum InvoiceStatus: string
            {
                case Paid = 'paid';
                public function fee(): Money { return new Money; }
            }
            PHP,
        'app/Billing/Domain/Payable.php' => <<<'PHP'
            <?php
            namespace App\Billing\Domain;
            interface Payable { public function total(): Money; }
            PHP,
    ]);
    $analysis = WhatMoved::analyse($project->root);

    expect($analysis->dependenciesOf('App\Billing\Domain\InvoiceStatus'))->toBe(['App\Billing\Domain\Money'])
        ->and($analysis->dependenciesOf('App\Billing\Domain\Payable'))->toBe(['App\Billing\Domain\Money']);
});

it('ignores a name that the code does not use', function (string $code) {
    expect(usesOf($code))->toBe([]);
})->with([
    'unused import' => <<<'PHP'
        <?php
        namespace App\Shipping\Application;
        use App\Billing\Domain\Invoice;
        final class ShipOrder {}
        PHP,
    'docblock' => <<<'PHP'
        <?php
        namespace App\Shipping\Application;
        final class ShipOrder
        {
            /** @param \App\Billing\Domain\Invoice $invoice */
            public function __invoke($invoice): void {}
        }
        PHP,
    'string' => <<<'PHP'
        <?php
        namespace App\Shipping\Application;
        final class ShipOrder { public function __invoke(): string { return 'App\Billing\Domain\Invoice'; } }
        PHP,
    'comment' => <<<'PHP'
        <?php
        namespace App\Shipping\Application;
        // Once shipped, see App\Billing\Domain\Invoice.
        final class ShipOrder {}
        PHP,
]);

it('leaves out the classes that are not in the source folder', function () {
    $project = Project::with([
        'app/Shipping/Application/ShipOrder.php' => <<<'PHP'
            <?php
            namespace App\Shipping\Application;
            use Illuminate\Support\Collection;
            use Tests\Factories\OrderFactory;
            final class ShipOrder { public function __invoke(Collection $orders, OrderFactory $factory): void {} }
            PHP,
        'tests/Factories/OrderFactory.php' => '<?php namespace Tests\Factories; final class OrderFactory {}',
    ]);
    $analysis = WhatMoved::analyse($project->root);

    expect($analysis->classes())->toBe(['App\Shipping\Application\ShipOrder'])
        ->and($analysis->dependenciesOf('App\Shipping\Application\ShipOrder'))->toBe([]);
});

it('does not count a class as depending on itself', function () {
    expect(usesOf(<<<'PHP'
        <?php
        namespace App\Shipping\Application;
        final class ShipOrder { public static function make(): self { return new ShipOrder; } }
        PHP))->toBe([]);
});
