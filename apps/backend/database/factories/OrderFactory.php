<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $customer = Customer::factory()->create();
        $shippingAddress = CustomerAddress::factory()->create(['customer_id' => $customer->id]);
        $billingAddress = CustomerAddress::factory()->billing()->create(['customer_id' => $customer->id]);

        return [
            'order_type' => OrderType::WebsiteOrder->value(),
            'order_source' => 'website',
            'status' => OrderStatus::PendingPayment->value(),
            'customer_id' => $customer->id,
            'shipping_address_id' => $shippingAddress->id,
            'billing_address_id' => $billingAddress->id,
            'customer_snapshot' => [
                'public_id' => $customer->public_id,
                'name' => $customer->display_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'company_name' => $customer->company_name,
                'customer_type' => $customer->customer_type,
            ],
            'shipping_address_snapshot' => [],
            'billing_address_snapshot' => [],
            'subtotal_amount_minor' => 0,
            'discount_amount_minor' => 0,
            'shipping_amount_minor' => 0,
            'tax_amount_minor' => 0,
            'total_amount_minor' => 0,
            'currency' => 'INR',
            'design_approved' => false,
        ];
    }
}
