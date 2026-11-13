<?php

namespace App\Billing\Application;

use App\Billing\Domain\Invoice;
use App\Billing\Domain\Money;
use App\Billing\Domain\PaymentGateway;
use App\Ordering\Domain\Order;

final readonly class IssueInvoice
{
    public function __construct(private PaymentGateway $gateway) {}

    public function __invoke(Order $order): Invoice
    {
        $invoice = new Invoice(uniqid('INV-'), new Money($order->total()->cents));
        $this->gateway->charge($invoice);

        return $invoice;
    }
}
