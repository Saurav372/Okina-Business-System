<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_exist_without_addresses(): void
    {
        $customer = Customer::factory()->create();

        $this->assertCount(0, $customer->addresses);
    }

    public function test_customer_can_have_multiple_addresses(): void
    {
        $customer = Customer::factory()
            ->has(CustomerAddress::factory()->count(2), 'addresses')
            ->create();

        $customer->refresh();

        $this->assertCount(2, $customer->addresses);
        $this->assertTrue($customer->addresses->every(fn (CustomerAddress $address) => $address->customer_id === $customer->id));
    }

    public function test_customer_address_belongs_to_a_customer(): void
    {
        $address = CustomerAddress::factory()->create();

        $this->assertInstanceOf(Customer::class, $address->customer);
        $this->assertSame($address->customer_id, $address->customer->id);
    }

    public function test_customer_factory_sets_shared_profile_fields(): void
    {
        $customer = Customer::factory()->create();

        $this->assertNotEmpty($customer->public_id);
        $this->assertNotEmpty($customer->display_name);
        $this->assertSame('active', $customer->status);
        $this->assertSame('individual', $customer->customer_type);
    }
}
