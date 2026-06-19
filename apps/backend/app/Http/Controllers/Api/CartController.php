<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\StoreCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Services\CartResponsePresenter;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request, CartService $cartService, CartResponsePresenter $presenter): JsonResponse
    {
        return response()->json([
            'data' => $presenter->payload($cartService->current($request, false)),
        ]);
    }

    public function store(StoreCartItemRequest $request, CartService $cartService, CartResponsePresenter $presenter): JsonResponse
    {
        return response()->json([
            'data' => $presenter->payload($cartService->addItem($request, $request->validated())),
        ]);
    }

    public function update(UpdateCartItemRequest $request, string $cartItem, CartService $cartService, CartResponsePresenter $presenter): JsonResponse
    {
        return response()->json([
            'data' => $presenter->payload($cartService->updateItem($request, $cartItem, $request->validated())),
        ]);
    }

    public function destroy(Request $request, string $cartItem, CartService $cartService, CartResponsePresenter $presenter): JsonResponse
    {
        return response()->json([
            'data' => $presenter->payload($cartService->removeItem($request, $cartItem)),
        ]);
    }
}
