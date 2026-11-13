<?php

use Tests\Support\Project;
use Tests\Support\PullRequest;
use Tests\Support\WhatMoved;

/*
 * Each test opens a pull request on the shop of tests/Fixtures/shop: the head is a copy of the base,
 * changed the way a developer would change it, and the test reads what the reviewer is shown.
 */

function shopConfig(): array
{
    return json_decode(file_get_contents(__DIR__.'/../Fixtures/shop/.github/what-moved.json'), true, flags: JSON_THROW_ON_ERROR);
}

function shopPullRequest(callable $change, ?array $config = null): PullRequest
{
    $head = Project::copyOf('shop');
    $change($head);

    return WhatMoved::pullRequest(Project::copyOf('shop')->root, $head->root, $config ?? shopConfig());
}

/**
 * @return array<string, array{dependencies: int, added: int}>
 */
function addedArrows(PullRequest $pullRequest): array
{
    return array_filter($pullRequest->arrows(), fn (array $arrow): bool => $arrow['added'] > 0);
}

it('shows a changed class without any new dependency', function () {
    $pullRequest = shopPullRequest(fn (Project $head) => $head->write(['app/Shipping/Domain/Shipment.php' => <<<'PHP'
        <?php

        namespace App\Shipping\Domain;

        use App\Customers\Domain\Address;

        final readonly class Shipment
        {
            public function __construct(public int $order, public Address $to) {}

            public function label(): string
            {
                return "#{$this->order}, {$this->to->city}";
            }
        }
        PHP]));

    expect($pullRequest->summary)->toContain('**0** classes added, **1** changed, **0** removed')
        ->toContain('**0** dependencies added, **0** removed')
        ->toContain('Contexts touched: `Shipping` (1).')
        ->and($pullRequest->counts())->toBe(['1 of 3 changed'])
        ->and(addedArrows($pullRequest))->toBe([]);
});

it('draws the dependency a pull request adds from one context to another', function () {
    $pullRequest = shopPullRequest(fn (Project $head) => $head->write(['app/Shipping/Application/ShipOrder.php' => <<<'PHP'
        <?php

        namespace App\Shipping\Application;

        use App\Billing\Domain\Invoice;
        use App\Ordering\Domain\Order;
        use App\Ordering\Domain\OrderStatus;
        use App\Shipping\Domain\Shipment;
        use App\Shipping\Infrastructure\CarrierClient;

        final readonly class ShipOrder
        {
            public function __construct(private CarrierClient $carrier) {}

            public function __invoke(int $id, Order $order, ?Invoice $invoice = null): Shipment
            {
                $shipment = new Shipment($id, $order->customer->address);
                $this->carrier->send($shipment);
                $order->status = OrderStatus::Shipped;

                return $shipment;
            }
        }
        PHP]));

    expect($pullRequest->summary)->toContain('**1** dependency added')
        ->and(addedArrows($pullRequest))->toBe(['Shipping → Billing' => ['dependencies' => 1, 'added' => 1]])
        ->and($pullRequest->arrows())->toHaveKey('Shipping → Ordering');
});

it('counts the dependency a pull request removes', function () {
    $pullRequest = shopPullRequest(fn (Project $head) => $head->write(['app/Shipping/Application/ShipOrder.php' => <<<'PHP'
        <?php

        namespace App\Shipping\Application;

        use App\Ordering\Domain\Order;
        use App\Shipping\Domain\Shipment;
        use App\Shipping\Infrastructure\CarrierClient;

        final readonly class ShipOrder
        {
            public function __construct(private CarrierClient $carrier) {}

            public function __invoke(int $id, Order $order): Shipment
            {
                $shipment = new Shipment($id, $order->customer->address);
                $this->carrier->send($shipment);

                return $shipment;
            }
        }
        PHP]));

    expect($pullRequest->summary)->toContain('**0** dependencies added, **1** removed')
        ->and($pullRequest->arrows()['Shipping → Ordering'])->toBe(['dependencies' => 1, 'added' => 0]);
});

it('shows a class added to an existing context', function () {
    $pullRequest = shopPullRequest(fn (Project $head) => $head->write(['app/Billing/Domain/CreditNote.php' => <<<'PHP'
        <?php

        namespace App\Billing\Domain;

        final readonly class CreditNote
        {
            public function __construct(public Invoice $invoice, public Money $amount) {}
        }
        PHP]));

    expect($pullRequest->summary)->toContain('**1** class added, **0** changed, **0** removed')
        ->and($pullRequest->counts())->toBe(['1 of 6 added'])
        ->and($pullRequest->newContexts())->toBe([])
        ->and($pullRequest->reportedClasses())->toContain('App\Billing\Domain\CreditNote');
});

it('marks a context the pull request creates as new, even without a place on the map', function () {
    $pullRequest = shopPullRequest(fn (Project $head) => $head->write([
        'app/Reviews/Domain/Review.php' => <<<'PHP'
            <?php

            namespace App\Reviews\Domain;

            use App\Catalog\Domain\ProductId;
            use App\Customers\Domain\Customer;

            final readonly class Review
            {
                public function __construct(public ProductId $product, public Customer $author, public int $stars) {}
            }
            PHP,
        'app/Reviews/Application/PostReview.php' => <<<'PHP'
            <?php

            namespace App\Reviews\Application;

            use App\Reviews\Domain\Review;

            final class PostReview
            {
                public function __invoke(Review $review): void {}
            }
            PHP,
        'app/Http/Controllers/ProductController.php' => <<<'PHP'
            <?php

            namespace App\Http\Controllers;

            use App\Catalog\Application\FindProducts;
            use App\Reviews\Application\PostReview;
            use Illuminate\Http\Request;

            final class ProductController
            {
                public function index(Request $request, FindProducts $find): array
                {
                    return $find((string) $request->query('q'));
                }

                public function review(Request $request, PostReview $post): void {}
            }
            PHP,
    ]));

    expect($pullRequest->summary)->toContain('Contexts touched: `Http` (1), `Reviews` (2, new context).')
        ->and($pullRequest->newContexts())->toBe(['Reviews'])
        ->and($pullRequest->counts())->toContain('2 of 2 added')
        ->and(array_keys(addedArrows($pullRequest)))->toEqualCanonicalizing(['Http → Reviews', 'Reviews → Catalog', 'Reviews → Customers']);
});

it('counts a class the pull request deletes and drops it from the report', function () {
    $pullRequest = shopPullRequest(fn (Project $head) => $head->delete('app/Ordering/Application/CancelOrder.php')->write([
        'app/Http/Controllers/OrderController.php' => <<<'PHP'
            <?php

            namespace App\Http\Controllers;

            use App\Http\Requests\PlaceOrderRequest;
            use App\Ordering\Application\PlaceOrder;
            use App\Policies\OrderPolicy;
            use Illuminate\Support\Facades\Gate;

            final class OrderController
            {
                public function store(PlaceOrderRequest $request, PlaceOrder $place): mixed
                {
                    return $place($request->customer(), $request->items());
                }

                public function destroy(int $order): void
                {
                    Gate::authorize('cancel', [OrderPolicy::class, $order]);
                }
            }
            PHP,
    ]));

    // OrderController no longer uses CancelOrder, which no longer uses OrderRepository.
    expect($pullRequest->summary)->toContain('**0** classes added, **1** changed, **1** removed')
        ->toContain('**2** removed.')
        ->and($pullRequest->reportedClasses())->not->toContain('App\Ordering\Application\CancelOrder');
});

it('shows a class moved to another context as added there and removed from where it was', function () {
    $pullRequest = shopPullRequest(fn (Project $head) => $head
        ->move('app/Customers/Domain/Address.php', 'app/Shipping/Domain/Address.php', <<<'PHP'
            <?php

            namespace App\Shipping\Domain;

            final readonly class Address
            {
                public function __construct(public string $street, public string $city, public string $country) {}
            }
            PHP)
        ->write([
            'app/Shipping/Domain/Shipment.php' => <<<'PHP'
                <?php

                namespace App\Shipping\Domain;

                final readonly class Shipment
                {
                    public function __construct(public int $order, public Address $to) {}
                }
                PHP,
            'app/Customers/Domain/Customer.php' => <<<'PHP'
                <?php

                namespace App\Customers\Domain;

                use App\Shipping\Domain\Address;

                final class Customer
                {
                    public function __construct(
                        public readonly int $id,
                        public readonly string $email,
                        public Address $address,
                    ) {}
                }
                PHP,
        ]));

    expect($pullRequest->summary)->toContain('**1** class added, **2** changed, **1** removed')
        ->and($pullRequest->reportedClasses())->toContain('App\Shipping\Domain\Address')
        ->not->toContain('App\Customers\Domain\Address')
        ->and(addedArrows($pullRequest))->toHaveKey('Customers → Shipping');
});

it('says so when the pull request touches no mapped class', function (callable $change) {
    expect(shopPullRequest($change)->summary)->toContain('This pull request touches no class in `app/`.');
})->with([
    'a route file' => fn () => fn (Project $head) => $head->write(['routes/web.php' => "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::view('/', 'welcome');\n"]),
    'an excluded service provider' => fn () => fn (Project $head) => $head->write(['app/Providers/AppServiceProvider.php' => "<?php\n\nnamespace App\\Providers;\n\nuse Illuminate\\Support\\ServiceProvider;\n\nfinal class AppServiceProvider extends ServiceProvider {}\n"]),
    'a helper' => fn () => fn (Project $head) => $head->write(['app/helpers.php' => "<?php\n\nfunction euros(int \$cents): float { return \$cents / 100; }\n"]),
]);

function changePrice(Project $head): void
{
    $head->write(['app/Catalog/Domain/Price.php' => <<<'PHP'
        <?php

        namespace App\Catalog\Domain;

        final readonly class Price
        {
            public function __construct(public int $cents) {}
        }
        PHP]);
}

it('shows only the contexts the pull request touched or an arrow links to them', function () {
    // Price is used inside Catalog, and from Ordering by Order and OrderLine.
    $pullRequest = shopPullRequest(changePrice(...));

    expect($pullRequest->contexts())->toEqualCanonicalizing(['Catalog', 'Ordering'])
        ->and($pullRequest->arrows())->toHaveKey('Ordering → Catalog');
});

it('shows every context when the config asks for it', function () {
    $pullRequest = shopPullRequest(changePrice(...), [...shopConfig(), 'show-all-contexts' => true]);

    expect($pullRequest->contexts())->toEqualCanonicalizing(['Billing', 'Catalog', 'Customers', 'Http', 'Ordering', 'Shipping']);
});

it('shows every context when the pull request touches none', function () {
    $pullRequest = shopPullRequest(fn (Project $head) => $head->write(['routes/web.php' => "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::view('/', 'welcome');\n"]));

    expect($pullRequest->contexts())->toEqualCanonicalizing(['Billing', 'Catalog', 'Customers', 'Http', 'Ordering', 'Shipping']);
});

it('draws a project without any config', function () {
    $pullRequest = shopPullRequest(changePrice(...), config: []);

    expect($pullRequest->contexts())->toEqualCanonicalizing(['Catalog', 'Ordering'])
        ->and($pullRequest->counts())->toBe(['1 of 6 changed']);
});
