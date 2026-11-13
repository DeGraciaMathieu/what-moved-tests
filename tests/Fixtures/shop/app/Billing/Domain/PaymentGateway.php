<?php

namespace App\Billing\Domain;

interface PaymentGateway
{
    public function charge(Invoice $invoice): bool;
}
