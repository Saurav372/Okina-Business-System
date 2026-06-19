<?php

namespace App\Services;

use App\Models\CustomerAccount;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;

class CheckoutValidationService
{
    private const BULK_QUANTITY_THRESHOLD = 25;

    public function __construct(
        private readonly CartService $cartService,
        private readonly CartValidationService $cartValidation,
    ) {}

    /**
     * @return array{valid: bool, cart: array<string, mixed>, cart_validation: array<string, mixed>, customer: array<string, mixed>, shipping_address: array<string, mixed>|null, billing_address: array<string, mixed>|null, bulk_handoff: array<string, mixed>, errors: array<int, array<string, mixed>>}
     */
    public function payload(Request $request, array $input): array
    {
        $customer = $this->currentCustomer($request);

        abort_unless($customer instanceof CustomerAccount, 403);

        $cart = $this->cartService->current($request, false);
        $cartValidation = $this->cartValidation->payload($cart);
        $errors = [];
        $bulkHandoff = $this->bulkHandoffPayload((int) ($cartValidation['cart']['item_count'] ?? 0));

        if ($cart === null) {
            $errors[] = $this->error('cart', 'cart_unavailable', 'An active cart is required for checkout.');
        } elseif ($cart->customer_id !== null && $cart->customer_id !== $customer->customer_id) {
            $errors[] = $this->error('cart', 'cart_customer_mismatch', 'The current cart does not belong to the signed-in customer.');
        }

        if ($bulkHandoff['required']) {
            return [
                'valid' => false,
                'cart' => $cartValidation['cart'] ?? [],
                'cart_validation' => [
                    'valid' => $cartValidation['valid'] ?? false,
                    'items' => $cartValidation['items'] ?? [],
                    'errors' => $cartValidation['errors'] ?? [],
                ],
                'customer' => $this->customerPayload($customer),
                'shipping_address' => null,
                'billing_address' => null,
                'bulk_handoff' => $bulkHandoff,
                'errors' => [
                    $this->error(
                        'quantity',
                        'bulk_quantity_threshold_reached',
                        'Orders with 25 or more items are handled through bulk enquiry and quotation.',
                    ),
                ],
            ];
        }

        $shippingAddress = $this->resolveAddress(
            customerId: $customer->customer_id,
            addressId: $input['shipping_address_id'] ?? null,
            field: 'shipping_address_id',
            required: true,
            errors: $errors,
        );

        $billingAddress = array_key_exists('billing_address_id', $input) && $input['billing_address_id'] !== null
            ? $this->resolveAddress(
                customerId: $customer->customer_id,
                addressId: $input['billing_address_id'],
                field: 'billing_address_id',
                required: false,
                errors: $errors,
            )
            : $shippingAddress;

        return [
            'valid' => ($cartValidation['valid'] ?? false) && $errors === [],
            'cart' => $cartValidation['cart'] ?? [],
            'cart_validation' => [
                'valid' => $cartValidation['valid'] ?? false,
                'items' => $cartValidation['items'] ?? [],
                'errors' => $cartValidation['errors'] ?? [],
            ],
            'customer' => $this->customerPayload($customer),
            'shipping_address' => $this->addressPayload($shippingAddress),
            'billing_address' => $this->addressPayload($billingAddress),
            'bulk_handoff' => $bulkHandoff,
            'errors' => $errors,
        ];
    }

    private function currentCustomer(Request $request): ?CustomerAccount
    {
        $customer = $request->user('customer');

        return $customer instanceof CustomerAccount ? $customer : null;
    }

    private function resolveAddress(int $customerId, mixed $addressId, string $field, bool $required, array &$errors): ?CustomerAddress
    {
        if ($addressId === null) {
            if ($required) {
                $errors[] = $this->error($field, 'shipping_address_required', 'A shipping address is required for checkout.');
            }

            return null;
        }

        if (! is_int($addressId) && ! (is_string($addressId) && ctype_digit($addressId))) {
            $errors[] = $this->error($field, $field.'_invalid', 'The selected address is invalid.');

            return null;
        }

        $address = CustomerAddress::query()
            ->whereKey((int) $addressId)
            ->where('customer_id', $customerId)
            ->whereNull('deleted_at')
            ->first();

        if ($address === null) {
            $errors[] = $this->error($field, $field.'_unavailable', 'The selected address is not available for checkout.');

            return null;
        }

        return $address;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function addressPayload(?CustomerAddress $address): ?array
    {
        if ($address === null) {
            return null;
        }

        return [
            'address_type' => $address->address_type,
            'label' => $address->label,
            'contact_name' => $address->contact_name,
            'phone' => $address->phone,
            'company_name' => $address->company_name,
            'address_line_1' => $address->address_line_1,
            'address_line_2' => $address->address_line_2,
            'landmark' => $address->landmark,
            'city' => $address->city,
            'state' => $address->state,
            'postal_code' => $address->postal_code,
            'country_code' => $address->country_code,
            'is_default_shipping' => $address->is_default_shipping,
            'is_default_billing' => $address->is_default_billing,
            'delivery_notes' => $address->delivery_notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function customerPayload(CustomerAccount $customer): array
    {
        $profile = $customer->customer;

        return [
            'public_id' => $profile?->public_id,
            'name' => $profile?->display_name,
            'email' => $profile?->email,
            'phone' => $profile?->phone,
            'company_name' => $profile?->company_name,
            'customer_type' => $profile?->customer_type,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function bulkHandoffPayload(int $itemCount): array
    {
        $required = $itemCount >= self::BULK_QUANTITY_THRESHOLD;

        return [
            'required' => $required,
            'threshold_quantity' => self::BULK_QUANTITY_THRESHOLD,
            'item_count' => $itemCount,
            'message' => $required ? 'Orders with 25 or more items are handled through bulk enquiry and quotation.' : null,
            'next_step' => $required ? 'bulk_enquiry' : 'direct_checkout',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function error(string $field, string $code, string $message): array
    {
        return [
            'field' => $field,
            'code' => $code,
            'message' => $message,
        ];
    }
}
