<?php

namespace App\Services;

use App\Contracts\CustomizationOptionContract;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CustomerAccount;
use App\Models\Product;
use App\Models\ProductSku;
use App\Support\Products\CustomizationSnapshotBuilder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    private const SESSION_KEY = 'cart_token';

    public function __construct(
        private readonly CustomizationOptionContract $customizationRules,
        private readonly CustomizationSnapshotBuilder $snapshots,
    ) {}

    public function current(Request $request, bool $createIfMissing = false): ?Cart
    {
        $customer = $this->currentCustomer($request);
        $sessionToken = $this->currentSessionToken($request);

        if (is_string($sessionToken) && $sessionToken !== '') {
            $cart = Cart::query()
                ->with(['items'])
                ->where('cart_token', $sessionToken)
                ->where('status', Cart::STATUS_ACTIVE)
                ->first();

            if ($cart !== null) {
                return $cart;
            }
        }

        if ($customer instanceof CustomerAccount) {
            $cart = Cart::query()
                ->with(['items'])
                ->where('customer_id', $customer->customer_id)
                ->where('status', Cart::STATUS_ACTIVE)
                ->orderByDesc('last_activity_at')
                ->orderByDesc('id')
                ->first();

            if ($cart !== null) {
                return $cart;
            }
        }

        if (! $createIfMissing) {
            return null;
        }

        return $this->createCurrentCart($request, $customer);
    }

    public function addItem(Request $request, array $input): Cart
    {
        $quantity = $this->normalizeQuantity($input['quantity'] ?? null);
        $productSlug = $this->normalizedString($input['product_slug'] ?? null);
        $skuCode = $this->normalizedString($input['sku_code'] ?? null);
        $rawCustomization = is_array($input['customization_snapshot'] ?? null) ? $input['customization_snapshot'] : [];

        if ($productSlug === null || $skuCode === null) {
            throw ValidationException::withMessages([
                'product_slug' => ['The product reference is required.'],
                'sku_code' => ['The SKU reference is required.'],
            ]);
        }

        $product = Product::query()
            ->publiclyVisible()
            ->with([
                'variants' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'skus' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
            ->where('slug', $productSlug)
            ->first();

        if ($product === null) {
            throw ValidationException::withMessages([
                'product_slug' => ['The selected product is not available.'],
            ]);
        }

        $sku = ProductSku::query()
            ->where('product_id', $product->id)
            ->where('sku_code', $skuCode)
            ->first();

        if ($sku === null) {
            throw ValidationException::withMessages([
                'sku_code' => ['The selected SKU is not available for this product.'],
            ]);
        }

        $this->assertPurchasableSku($sku);
        $this->assertQuantityWithinProductRules($product, $quantity);

        $selection = $this->selectionFromCustomizationSnapshot($skuCode, $rawCustomization);
        $validation = $this->customizationRules->validateSelection($product->slug, $selection);

        if (! ($validation['valid'] ?? false)) {
            throw ValidationException::withMessages([
                'customization_snapshot' => $validation['errors'] ?? ['The customization data is invalid.'],
            ]);
        }

        $normalizedCustomization = $this->normalizeCustomizationSnapshot($rawCustomization);

        if (data_get($normalizedCustomization, 'product.slug') !== null && data_get($normalizedCustomization, 'product.slug') !== $product->slug) {
            throw ValidationException::withMessages([
                'customization_snapshot.product.slug' => ['The customization snapshot does not match the selected product.'],
            ]);
        }

        if (data_get($normalizedCustomization, 'sku_code') !== null && data_get($normalizedCustomization, 'sku_code') !== $sku->sku_code) {
            throw ValidationException::withMessages([
                'customization_snapshot.sku_code' => ['The customization snapshot does not match the selected SKU.'],
            ]);
        }

        $fingerprint = $this->snapshots->customizationFingerprint($normalizedCustomization);

        return DB::transaction(function () use ($request, $quantity, $product, $sku, $normalizedCustomization, $fingerprint): Cart {
            $cart = $this->resolveForMutation($request);
            $this->touchCustomerOwnership($cart, $this->currentCustomer($request));

            $existing = CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('sku_id', $sku->id)
                ->where('customization_fingerprint', $fingerprint)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                $existing->forceFill([
                    'quantity' => $existing->quantity + $quantity,
                ])->save();
            } else {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'sku_id' => $sku->id,
                    'quantity' => $quantity,
                    'product_name_snapshot' => $product->name,
                    'product_slug_snapshot' => $product->slug,
                    'sku_code_snapshot' => $sku->sku_code,
                    'customization_fingerprint' => $fingerprint,
                    'customization_snapshot' => $normalizedCustomization,
                ]);
            }

            $cart->forceFill([
                'last_activity_at' => now(),
            ])->save();

            return $this->freshCart($cart->id);
        });
    }

    public function updateItem(Request $request, string $cartItemPublicId, array $input): Cart
    {
        $quantity = $this->normalizeQuantity($input['quantity'] ?? null);

        return DB::transaction(function () use ($request, $cartItemPublicId, $quantity): Cart {
            $cart = $this->resolveCartForExistingItem($request, $cartItemPublicId);
            $item = $this->cartItemForCart($cart, $cartItemPublicId);
            $product = Product::query()->publiclyVisible()->findOrFail($item->product_id);
            $this->assertQuantityWithinProductRules($product, $quantity);

            $item->forceFill([
                'quantity' => $quantity,
            ])->save();

            $cart->forceFill([
                'last_activity_at' => now(),
            ])->save();

            return $this->freshCart($cart->id);
        });
    }

    public function removeItem(Request $request, string $cartItemPublicId): Cart
    {
        return DB::transaction(function () use ($request, $cartItemPublicId): Cart {
            $cart = $this->resolveCartForExistingItem($request, $cartItemPublicId);
            $item = $this->cartItemForCart($cart, $cartItemPublicId);
            $item->delete();

            $cart->forceFill([
                'last_activity_at' => now(),
            ])->save();

            return $this->freshCart($cart->id);
        });
    }

    private function resolveForMutation(Request $request): Cart
    {
        $customer = $this->currentCustomer($request);
        $sessionToken = $this->currentSessionToken($request);

        if (is_string($sessionToken) && $sessionToken !== '') {
            $cart = Cart::query()
                ->where('cart_token', $sessionToken)
                ->where('status', Cart::STATUS_ACTIVE)
                ->first();

            if ($cart !== null) {
                return $cart;
            }
        }

        if ($customer instanceof CustomerAccount) {
            $cart = Cart::query()
                ->where('customer_id', $customer->customer_id)
                ->where('status', Cart::STATUS_ACTIVE)
                ->orderByDesc('last_activity_at')
                ->orderByDesc('id')
                ->first();

            if ($cart !== null) {
                if (! is_string($cart->cart_token) || $cart->cart_token === '') {
                    $cart->forceFill([
                        'cart_token' => $this->generateCartToken(),
                    ])->save();
                }

                $this->storeSessionToken($request, $cart->cart_token);

                return $cart;
            }
        }

        return $this->createCurrentCart($request, $customer);
    }

    private function resolveCartForExistingItem(Request $request, string $cartItemPublicId): Cart
    {
        $cart = $this->current($request, false);

        if ($cart === null) {
            throw new ModelNotFoundException;
        }

        $itemExists = CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('public_id', $cartItemPublicId)
            ->exists();

        if (! $itemExists) {
            throw new ModelNotFoundException;
        }

        return Cart::query()
            ->whereKey($cart->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function cartItemForCart(Cart $cart, string $cartItemPublicId): CartItem
    {
        return CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('public_id', $cartItemPublicId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function currentCustomer(Request $request): ?CustomerAccount
    {
        $customer = $request->user('customer');

        return $customer instanceof CustomerAccount ? $customer : null;
    }

    private function currentSessionToken(Request $request): ?string
    {
        $token = $request->session()->get(self::SESSION_KEY);

        if (! is_string($token)) {
            return null;
        }

        $token = trim($token);

        return $token === '' ? null : $token;
    }

    private function createCurrentCart(Request $request, ?CustomerAccount $customer): Cart
    {
        $cart = Cart::create([
            'customer_id' => $customer?->customer_id,
            'cart_token' => $this->generateCartToken(),
            'status' => Cart::STATUS_ACTIVE,
            'last_activity_at' => now(),
        ]);

        $this->storeSessionToken($request, $cart->cart_token);

        return $cart->load('items');
    }

    private function freshCart(int $cartId): Cart
    {
        return Cart::query()
            ->with(['items'])
            ->findOrFail($cartId);
    }

    private function storeSessionToken(Request $request, ?string $token): void
    {
        if (! is_string($token) || $token === '') {
            return;
        }

        $request->session()->put(self::SESSION_KEY, $token);
    }

    private function generateCartToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function touchCustomerOwnership(Cart $cart, ?CustomerAccount $customer): void
    {
        if ($customer === null || $cart->customer_id !== null) {
            return;
        }

        $cart->forceFill([
            'customer_id' => $customer->customer_id,
        ])->save();
    }

    private function normalizeQuantity(mixed $quantity): int
    {
        if (! is_int($quantity) && ! (is_string($quantity) && ctype_digit($quantity))) {
            throw ValidationException::withMessages([
                'quantity' => ['The quantity must be an integer.'],
            ]);
        }

        $value = (int) $quantity;

        if ($value < 1) {
            throw ValidationException::withMessages([
                'quantity' => ['The quantity must be at least 1.'],
            ]);
        }

        if ($value > 9999) {
            throw ValidationException::withMessages([
                'quantity' => ['The quantity is too large.'],
            ]);
        }

        return $value;
    }

    private function assertQuantityWithinProductRules(Product $product, int $quantity): void
    {
        $minimum = max(1, (int) ($product->min_order_quantity ?? 1));
        $maximum = $product->max_order_quantity !== null ? (int) $product->max_order_quantity : 9999;

        if ($quantity < $minimum || $quantity > $maximum) {
            throw ValidationException::withMessages([
                'quantity' => ['The quantity is outside the allowed range for this product.'],
            ]);
        }
    }

    private function assertPurchasableSku(ProductSku $sku): void
    {
        if (($sku->status ?? null) !== 'active') {
            throw ValidationException::withMessages([
                'sku_code' => ['The selected SKU is not purchasable.'],
            ]);
        }

        if (! $sku->direct_checkout_enabled) {
            throw ValidationException::withMessages([
                'sku_code' => ['The selected SKU is not available for cart purchase.'],
            ]);
        }
    }

    private function normalizeCustomizationSnapshot(array $snapshot): array
    {
        return $this->snapshots->publicCartSnapshot($snapshot);
    }

    private function selectionFromCustomizationSnapshot(string $skuCode, array $snapshot): array
    {
        $selectedOptions = data_get($snapshot, 'selected_options_snapshot', []);
        $selectedOptionsMap = [];

        if (is_array($selectedOptions)) {
            foreach ($selectedOptions as $option) {
                if (! is_array($option)) {
                    continue;
                }

                $optionCode = $this->normalizedString($option['option_code'] ?? null);
                $valueCode = $this->normalizedString($option['value_code'] ?? null);

                if ($optionCode === null || $valueCode === null) {
                    continue;
                }

                $selectedOptionsMap[$optionCode] = $valueCode;
            }
        }

        return [
            'sku_code' => $skuCode,
            'selected_options' => $selectedOptionsMap,
            'print_position' => $this->normalizedString(data_get($snapshot, 'print_position')),
            'print_method' => $this->normalizedString(data_get($snapshot, 'print_method')),
        ];
    }

    private function normalizedString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $clean = trim((string) $value);

        return $clean === '' ? null : $clean;
    }
}
