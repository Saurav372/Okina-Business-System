<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Storefront\StorefrontViewData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MockupController extends Controller
{
    public function __construct(private readonly StorefrontViewData $viewData) {}

    public function create(Request $request): View
    {
        $products = array_values(array_filter(
            $this->viewData->products(),
            fn (array $product): bool => ($product['customization_mode'] ?? 'none') !== 'none'
                && count($product['skus'] ?? []) > 0,
        ));

        return view('storefront.mockup-generate', [
            ...$this->viewData->base($request),
            'products' => $products,
        ]);
    }
}
