<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuickCustomerCreateRequest;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function quickStore(QuickCustomerCreateRequest $request)
    {
        $validated = $request->validated();

        $phones = (array) ($validated['phones'] ?? []);
        $primaryPhone = $phones[0] ?? '';

        $sameAsWhatsApp = (bool) ($validated['same_as_whatsapp'] ?? true);
        $whatsappPhone = $sameAsWhatsApp ? $primaryPhone : ($validated['whatsapp_phone'] ?? $primaryPhone);

        $name = trim($validated['name']);
        $brandName = trim($validated['brand_name']);
        $displayName = "{$name} ({$brandName})";

        $customer = DB::transaction(function () use ($validated, $name, $brandName, $displayName, $primaryPhone, $whatsappPhone) {
            $customer = Customer::create([
                'public_id' => 'CUS-' . strtoupper(Str::random(10)),
                'customer_type' => 'business',
                'name' => $name,
                'display_name' => $displayName,
                'company_name' => $brandName,
                'email' => !empty($validated['email']) ? trim($validated['email']) : null,
                'phone' => $primaryPhone,
                'whatsapp_phone' => $whatsappPhone,
                'source' => $validated['lead_source'] ?? 'other',
                'status' => 'active',
                'accepts_marketing' => true,
                'created_by_user_id' => auth()->id(),
            ]);

            $sameAsBilling = (bool) ($validated['same_as_billing'] ?? true);

            // Handle Shipping Address
            $shipping = $validated['shipping_address'] ?? [];
            $hasShipping = !empty($shipping['line_1']) || !empty($shipping['city']);

            if ($hasShipping) {
                CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'address_type' => $sameAsBilling ? CustomerAddress::TYPE_BOTH : CustomerAddress::TYPE_SHIPPING,
                    'contact_name' => $name,
                    'company_name' => $brandName,
                    'phone' => $primaryPhone,
                    'address_line_1' => $shipping['line_1'] ?? '',
                    'address_line_2' => $shipping['line_2'] ?? null,
                    'city' => $shipping['city'] ?? '',
                    'state' => $shipping['state'] ?? '',
                    'postal_code' => $shipping['postal_code'] ?? '',
                    'country_code' => 'IN',
                    'is_default_shipping' => true,
                    'is_default_billing' => $sameAsBilling,
                    'created_by_user_id' => auth()->id(),
                ]);
            }

            // Handle Separate Billing Address if toggle is off
            $billing = $validated['billing_address'] ?? [];
            $hasBilling = !empty($billing['line_1']) || !empty($billing['city']);

            if (!$sameAsBilling && $hasBilling) {
                CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'address_type' => CustomerAddress::TYPE_BILLING,
                    'contact_name' => $name,
                    'company_name' => $brandName,
                    'phone' => $primaryPhone,
                    'address_line_1' => $billing['line_1'] ?? '',
                    'address_line_2' => $billing['line_2'] ?? null,
                    'city' => $billing['city'] ?? '',
                    'state' => $billing['state'] ?? '',
                    'postal_code' => $billing['postal_code'] ?? '',
                    'gstin' => $billing['gstin'] ?? null,
                    'country_code' => 'IN',
                    'is_default_shipping' => false,
                    'is_default_billing' => true,
                    'created_by_user_id' => auth()->id(),
                ]);
            }

            return $customer;
        });

        return response()->json([
            'success' => true,
            'message' => "Customer \"{$customer->display_name}\" registered successfully.",
            'customer' => [
                'id' => $customer->id,
                'public_id' => $customer->public_id,
                'display_name' => $customer->display_name,
                'name' => $customer->name,
                'company_name' => $customer->company_name,
                'phone' => $customer->phone,
                'whatsapp_phone' => $customer->whatsapp_phone,
                'email' => $customer->email,
            ],
        ], 201);
    }
}
