<?php

namespace App\Http\Controllers;

use App\Catalog\Application\FindProducts;
use Illuminate\Http\Request;

final class ProductController
{
    public function index(Request $request, FindProducts $find): array
    {
        return $find((string) $request->query('q'));
    }
}
