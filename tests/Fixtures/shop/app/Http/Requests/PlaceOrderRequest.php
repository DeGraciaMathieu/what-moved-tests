<?php

namespace App\Http\Requests;

use App\Customers\Domain\Customer;
use Illuminate\Foundation\Http\FormRequest;

final class PlaceOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return ['items' => ['required', 'array']];
    }

    public function customer(): Customer
    {
        return $this->user()->customer;
    }

    public function items(): array
    {
        return $this->validated('items');
    }
}
