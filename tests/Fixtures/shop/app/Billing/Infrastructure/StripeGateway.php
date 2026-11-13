<?php

namespace App\Billing\Infrastructure;

use App\Billing\Domain\Invoice;
use App\Billing\Domain\PaymentGateway;
use Illuminate\Support\Facades\Http;

final class StripeGateway implements PaymentGateway
{
    public function charge(Invoice $invoice): bool
    {
        return Http::post('https://api.stripe.com/v1/charges', ['amount' => $invoice->amount->cents])->successful();
    }
}
