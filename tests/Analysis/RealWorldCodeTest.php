<?php

use Tests\Support\Project;
use Tests\Support\WhatMoved;

/*
 * The source folder of a real project holds more than tidy classes: helpers, several classes in a file,
 * anonymous classes, code the parser does not know yet.
 */

it('reads a file without any class', function () {
    $project = Project::with([
        'app/helpers.php' => <<<'PHP'
            <?php
            if (! function_exists('money')) {
                function money(int $cents): string { return number_format($cents / 100, 2); }
            }
            PHP,
        'app/Billing/Invoice.php' => '<?php namespace App\Billing; final class Invoice {}',
    ]);

    expect(WhatMoved::analyse($project->root)->classes())->toBe(['App\Billing\Invoice']);
});

it('reads every class of a file that declares several', function () {
    $project = Project::with([
        'app/Billing/Events.php' => <<<'PHP'
            <?php
            namespace App\Billing;
            final class InvoiceIssued {}
            final class InvoicePaid { public function __construct(public InvoiceIssued $issued) {} }
            PHP,
    ]);
    $analysis = WhatMoved::analyse($project->root);

    expect($analysis->classes())->toBe(['App\Billing\InvoiceIssued', 'App\Billing\InvoicePaid'])
        ->and($analysis->dependenciesOf('App\Billing\InvoicePaid'))->toBe(['App\Billing\InvoiceIssued']);
});

it('counts what an anonymous class uses for the class around it', function () {
    $project = Project::with([
        'app/Billing/Invoice.php' => '<?php namespace App\Billing; final class Invoice {}',
        'app/Shipping/Carrier.php' => <<<'PHP'
            <?php
            namespace App\Shipping;
            use App\Billing\Invoice;
            final class Carrier
            {
                public function fake(): object
                {
                    return new class { public function invoice(): Invoice { return new Invoice; } };
                }
            }
            PHP,
    ]);
    $analysis = WhatMoved::analyse($project->root);

    expect($analysis->classes())->toBe(['App\Billing\Invoice', 'App\Shipping\Carrier'])
        ->and($analysis->dependenciesOf('App\Shipping\Carrier'))->toBe(['App\Billing\Invoice']);
});

it('ignores the files that are not PHP', function () {
    $project = Project::with([
        'app/Billing/Invoice.php' => '<?php namespace App\Billing; final class Invoice {}',
        'app/Billing/invoice.blade.php.stub' => '<div>{{ $invoice }}</div>',
        'app/Billing/README.md' => '# Billing',
    ]);

    expect(WhatMoved::analyse($project->root)->classes())->toBe(['App\Billing\Invoice']);
});

it('reads a class in the global namespace without mapping it', function () {
    $project = Project::with([
        'app/Legacy.php' => '<?php final class Legacy {}',
        'app/Billing/Invoice.php' => '<?php namespace App\Billing; final class Invoice extends \Legacy {}',
    ]);
    $analysis = WhatMoved::analyse($project->root);

    expect($analysis->classes())->toBe(['App\Billing\Invoice'])
        ->and($analysis->dependenciesOf('App\Billing\Invoice'))->toBe([]);
});

it('still maps the project when one of its files does not parse', function () {
    $project = Project::with([
        'app/Billing/Invoice.php' => '<?php namespace App\Billing; final class Invoice {}',
        'app/Billing/Broken.php' => '<?php namespace App\Billing; final class Broken { public function __invoke( }',
    ]);

    expect(WhatMoved::analyse($project->root)->classes())->toContain('App\Billing\Invoice');
});
