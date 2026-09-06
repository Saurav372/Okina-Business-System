<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Permission;
use App\Models\ProductSku;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSalesOrderCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_staff_can_view_the_styled_sales_order_form(): void
    {
        $this->withoutVite();

        Permission::query()->updateOrCreate(
            ['slug' => 'orders.manage'],
            [
                'name' => 'Manage Orders',
                'group' => 'orders',
                'guard_name' => 'web',
                'description' => 'Manage orders',
                'is_sensitive' => false,
            ],
        );
        $creatorRole = Role::query()->updateOrCreate(
            ['slug' => 'order_creator'],
            [
                'name' => 'Order Creator',
                'guard_name' => 'web',
                'description' => 'Can create sales orders',
                'is_system' => true,
                'sort_order' => 0,
            ],
        );
        $creatorRole->permissions()->sync(Permission::query()->where('slug', 'orders.manage')->pluck('id'));
        $salesRole = Role::query()->updateOrCreate(
            ['slug' => Role::SALES_STAFF],
            [
                'name' => 'Sales Staff',
                'guard_name' => 'web',
                'description' => 'Sales staff role',
                'is_system' => true,
                'sort_order' => 0,
            ],
        );
        $user = User::factory()->create();
        $user->assignRole($creatorRole);
        $user->assignRole($salesRole);
        Customer::factory()->create(['display_name' => 'Studio Customer']);
        $sku = ProductSku::factory()->create(['sku_code' => 'SKU-STYLED-001']);

        $this->actingAs($user)
            ->get(route('admin.sales_orders.create'))
            ->assertOk()
            ->assertViewIs('admin.orders.create')
            ->assertSee('layout-sidebar', false)
            ->assertSee('x-data="salesOrderForm"', false)
            ->assertSee('Studio Customer')
            ->assertSee('SKU-STYLED-001')
            ->assertSee($sku->product->name)
            ->assertSee('Order checklist')
            ->assertSee('Create sales order');
    }

    public function test_authorized_staff_can_create_sales_order(): void
    {
        Permission::query()->updateOrCreate(
            ['slug' => 'orders.manage'],
            [
                'name' => 'Manage Orders',
                'group' => 'orders',
                'guard_name' => 'web',
                'description' => 'Manage orders',
                'is_sensitive' => false,
            ],
        );

        Permission::query()->updateOrCreate(
            ['slug' => 'orders.view'],
            [
                'name' => 'View Orders',
                'group' => 'orders',
                'guard_name' => 'web',
                'description' => 'View orders',
                'is_sensitive' => false,
            ],
        );

        $role = Role::query()->updateOrCreate(
            ['slug' => 'order_creator'],
            [
                'name' => 'Order Creator',
                'guard_name' => 'web',
                'description' => 'Can create sales orders',
                'is_system' => true,
                'sort_order' => 0,
            ],
        );

        $permissionIds = Permission::query()->whereIn('slug', ['orders.manage', 'orders.view'])->pluck('id')->all();
        $role->permissions()->sync($permissionIds);

        $dashboardRole = Role::query()->updateOrCreate(
            ['slug' => Role::SALES_STAFF],
            [
                'name' => 'Sales Staff',
                'guard_name' => 'web',
                'description' => 'Sales staff role',
                'is_system' => true,
                'sort_order' => 0,
            ],
        );

        $user = User::factory()->create();
        $user->assignRole($role);
        $user->assignRole($dashboardRole);

        $customer = Customer::factory()->create();

        $sku = ProductSku::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.sales_orders.store'), [
                'customer_id' => $customer->id,
                'items' => [
                    [
                        'sku_code' => $sku->sku_code,
                        'quantity' => 2,
                        'customization_snapshot' => [],
                    ],
                ],
                'advance_payment' => [
                    'amount_minor' => 1000,
                    'due_date' => now()->addDays(7)->toDateString(),
                ],
            ])
            ->assertStatus(201)
            ->assertJsonStructure(['public_id', 'order']);

        $this->assertDatabaseHas('orders', [
            'order_type' => 'sales_order',
            'order_source' => 'admin',
        ]);

        $order = Order::query()->first();

        $this->assertNotNull($order);
        $this->assertSame('confirmed', $order->status);
        $this->assertSame(2, $order->items()->first()->quantity);
        $this->assertSame($sku->sku_code, $order->items()->first()->sku_code_snapshot);
        $this->assertIsArray($order->items()->first()->customization_snapshot);

        $decodedNotes = $order->internal_notes ? json_decode($order->internal_notes, true) : [];
        $this->assertArrayHasKey('payment_schedule', $decodedNotes);
        $this->assertSame(1000, $decodedNotes['payment_schedule']['amount_minor']);

        // Verify that /admin/orders/{public_id}/detail redirects gracefully to order show
        $this->actingAs($user)
            ->get("/admin/orders/{$order->public_id}/detail")
            ->assertRedirect(route('admin.orders.show', $order));

        // Verify order show page loads with 200 OK
        $this->actingAs($user)
            ->get(route('admin.orders.show', $order))
            ->assertStatus(200);
    }

    public function test_unauthorized_staff_cannot_create_sales_order(): void
    {
        $user = User::factory()->create();

        $dashboardRole = Role::query()->updateOrCreate(
            ['slug' => Role::SALES_STAFF],
            [
                'name' => 'Sales Staff',
                'guard_name' => 'web',
                'description' => 'Sales staff role',
                'is_system' => true,
                'sort_order' => 0,
            ],
        );

        $user->assignRole($dashboardRole);

        $customer = Customer::factory()->create();
        $sku = ProductSku::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.sales_orders.store'), [
                'customer_id' => $customer->id,
                'items' => [
                    [
                        'sku_code' => $sku->sku_code,
                        'quantity' => 1,
                        'customization_snapshot' => [],
                    ],
                ],
            ])
            ->assertStatus(403);
    }
}
