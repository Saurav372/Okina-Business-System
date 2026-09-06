<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\ProductCategoryResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;
use App\Policies\ProductCategoryPolicy;
use App\Policies\ProductPolicy;
use App\Support\Admin\AdminResourceCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * A3.2.7 — Admin catalog management screens/resources.
 *
 * Verifies:
 * - ProductResource and ProductCategoryResource are registered in AdminResourceCatalog.
 * - Index and detail catalog definitions contain required field structures.
 * - Permission-gated access: Admin can access and manage; Inventory Staff can view only;
 *   unauthorized roles are denied.
 * - ProductPolicy and ProductCategoryPolicy enforce correct rules.
 */
class AdminCatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Registration
    // -----------------------------------------------------------------------

    public function test_product_resource_is_registered_in_admin_resource_catalog(): void
    {
        $catalog = app(AdminResourceCatalog::class);
        $resource = $catalog->resource('products');

        $this->assertNotNull($resource);
        $this->assertSame(ProductResource::registration(), $resource);
        $this->assertSame('products.view', $resource['permission_view']);
        $this->assertSame('products.manage', $resource['permission_manage']);
        $this->assertContains('view', $resource['allowed_actions']);
        $this->assertContains('create', $resource['allowed_actions']);
        $this->assertContains('edit', $resource['allowed_actions']);
        $this->assertContains('delete', $resource['allowed_actions']);
        $this->assertContains('forceDelete', $resource['blocked_actions']);
        $this->assertContains('finance', $resource['blocked_actions']);
    }

    public function test_product_category_resource_is_registered_in_admin_resource_catalog(): void
    {
        $catalog = app(AdminResourceCatalog::class);
        $resource = $catalog->resource('product_categories');

        $this->assertNotNull($resource);
        $this->assertSame(ProductCategoryResource::registration(), $resource);
        $this->assertSame('product_categories.view', $resource['permission_view']);
        $this->assertSame('product_categories.manage', $resource['permission_manage']);
        $this->assertContains('view', $resource['allowed_actions']);
        $this->assertContains('create', $resource['allowed_actions']);
        $this->assertContains('forceDelete', $resource['blocked_actions']);
    }

    // -----------------------------------------------------------------------
    // Index catalog structure
    // -----------------------------------------------------------------------

    public function test_product_index_catalog_contains_required_columns_and_filters(): void
    {
        $resource = ProductResource::registration();
        $index = $resource['index'];

        $this->assertArrayHasKey('columns', $index);
        $this->assertArrayHasKey('filters', $index);
        $this->assertArrayHasKey('name', $index['columns']);
        $this->assertArrayHasKey('status', $index['columns']);
        $this->assertArrayHasKey('visibility', $index['columns']);
        $this->assertArrayHasKey('status', $index['filters']);
        $this->assertArrayHasKey('visibility', $index['filters']);
    }

    public function test_product_index_catalog_filter_options_match_model_constants(): void
    {
        $index = ProductResource::registration()['index'];
        $options = $index['filters']['status']['options'];

        $this->assertArrayHasKey(Product::STATUS_DRAFT, $options);
        $this->assertArrayHasKey(Product::STATUS_ACTIVE, $options);
        $this->assertArrayHasKey(Product::STATUS_OUT_OF_STOCK, $options);
        $this->assertArrayHasKey(Product::STATUS_BULK_ONLY, $options);
        $this->assertArrayHasKey(Product::STATUS_DISCONTINUED, $options);

        $visOptions = $index['filters']['visibility']['options'];
        $this->assertArrayHasKey(Product::VISIBILITY_PUBLIC, $visOptions);
        $this->assertArrayHasKey(Product::VISIBILITY_PRIVATE, $visOptions);
    }

    public function test_category_index_catalog_contains_required_columns_and_filters(): void
    {
        $resource = ProductCategoryResource::registration();
        $index = $resource['index'];

        $this->assertArrayHasKey('columns', $index);
        $this->assertArrayHasKey('filters', $index);
        $this->assertArrayHasKey('name', $index['columns']);
        $this->assertArrayHasKey('status', $index['columns']);
        $this->assertArrayHasKey('status', $index['filters']);
    }

    // -----------------------------------------------------------------------
    // Detail catalog structure
    // -----------------------------------------------------------------------

    public function test_product_detail_catalog_groups_fields_into_sections(): void
    {
        $detail = ProductResource::registration()['detail'];

        $this->assertArrayHasKey('sections', $detail);
        $this->assertArrayHasKey('core', $detail['sections']);
        $this->assertArrayHasKey('status', $detail['sections']);
        $this->assertArrayHasKey('ordering', $detail['sections']);
        $this->assertArrayHasKey('seo', $detail['sections']);
    }

    public function test_product_detail_catalog_includes_required_core_fields(): void
    {
        $coreFields = ProductResource::registration()['detail']['sections']['core']['fields'];

        $this->assertArrayHasKey('name', $coreFields);
        $this->assertArrayHasKey('slug', $coreFields);
        $this->assertArrayHasKey('primary_category_id', $coreFields);
        $this->assertTrue($coreFields['name']['required']);
    }

    public function test_product_detail_catalog_does_not_expose_finance_sensitive_fields(): void
    {
        $detail = ProductResource::registration()['detail'];
        // cost_price, cost_minor, profit_margin must not appear anywhere in the detail definition
        $serialized = json_encode($detail);

        $this->assertStringNotContainsString('cost_price', $serialized);
        $this->assertStringNotContainsString('profit_margin', $serialized);
    }

    public function test_product_detail_catalog_exposes_variant_and_sku_relations(): void
    {
        $detail = ProductResource::registration()['detail'];
        $relations = $detail['relations'];

        $this->assertArrayHasKey('variants', $relations);
        $this->assertArrayHasKey('skus', $relations);
    }

    public function test_category_detail_catalog_includes_required_fields(): void
    {
        $coreFields = ProductCategoryResource::registration()['detail']['sections']['core']['fields'];

        $this->assertArrayHasKey('name', $coreFields);
        $this->assertArrayHasKey('slug', $coreFields);
        $this->assertArrayHasKey('status', $coreFields);
        $this->assertTrue($coreFields['name']['required']);
    }

    // -----------------------------------------------------------------------
    // ProductResource canAccess / canManage
    // -----------------------------------------------------------------------

    public function test_admin_can_access_and_manage_products(): void
    {
        $admin = $this->makeStaffWithPermissions('admin_role', ['products.view', 'products.manage']);

        $this->assertTrue(ProductResource::canAccess($admin));
        $this->assertTrue(ProductResource::canManage($admin));
    }

    public function test_inventory_staff_can_view_but_not_manage_products(): void
    {
        $inventoryStaff = $this->makeStaffWithPermissions('inventory_role', ['products.view']);

        $this->assertTrue(ProductResource::canAccess($inventoryStaff));
        $this->assertFalse(ProductResource::canManage($inventoryStaff));
    }

    public function test_unauthorized_role_cannot_access_products(): void
    {
        $salesOnly = $this->makeStaffWithPermissions('sales_role', ['orders.view']);

        $this->assertFalse(ProductResource::canAccess($salesOnly));
        $this->assertFalse(ProductResource::canManage($salesOnly));
    }

    public function test_admin_can_access_and_manage_product_categories(): void
    {
        $admin = $this->makeStaffWithPermissions('admin_cat_role', ['product_categories.view', 'product_categories.manage']);

        $this->assertTrue(ProductCategoryResource::canAccess($admin));
        $this->assertTrue(ProductCategoryResource::canManage($admin));
    }

    public function test_unauthorized_role_cannot_access_product_categories(): void
    {
        $financeOnly = $this->makeStaffWithPermissions('finance_role_cat', ['finance.view']);

        $this->assertFalse(ProductCategoryResource::canAccess($financeOnly));
    }

    // -----------------------------------------------------------------------
    // ProductPolicy via Gate
    // -----------------------------------------------------------------------

    public function test_super_admin_role_can_create_and_delete_products(): void
    {
        $superAdmin = $this->makeStaffWithRole(Role::SUPER_ADMIN);
        $product = Product::factory()->create();

        $this->assertTrue(Gate::forUser($superAdmin)->allows('create', Product::class));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('update', $product));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('delete', $product));
        $this->assertFalse(Gate::forUser($superAdmin)->allows('forceDelete', $product));
    }

    public function test_admin_role_can_create_and_update_but_not_delete_products(): void
    {
        $admin = $this->makeStaffWithRole(Role::ADMIN);
        $product = Product::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('create', Product::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $product));
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $product));
    }

    public function test_inventory_staff_can_view_products_but_not_create_or_update(): void
    {
        $inventoryStaff = $this->makeStaffWithRole(Role::INVENTORY_STAFF);
        $product = Product::factory()->create();

        $this->assertTrue(Gate::forUser($inventoryStaff)->allows('view', $product));
        $this->assertFalse(Gate::forUser($inventoryStaff)->allows('create', Product::class));
        $this->assertFalse(Gate::forUser($inventoryStaff)->allows('update', $product));
    }

    public function test_sales_staff_cannot_access_products(): void
    {
        $salesStaff = $this->makeStaffWithRole(Role::SALES_STAFF);
        $product = Product::factory()->create();

        $this->assertFalse(Gate::forUser($salesStaff)->allows('viewAny', Product::class));
        $this->assertFalse(Gate::forUser($salesStaff)->allows('view', $product));
        $this->assertFalse(Gate::forUser($salesStaff)->allows('create', Product::class));
    }

    public function test_production_staff_cannot_access_products(): void
    {
        $productionStaff = $this->makeStaffWithRole(Role::PRODUCTION_STAFF);
        $product = Product::factory()->create();

        $this->assertFalse(Gate::forUser($productionStaff)->allows('viewAny', Product::class));
    }

    // -----------------------------------------------------------------------
    // ProductCategoryPolicy via Gate
    // -----------------------------------------------------------------------

    public function test_super_admin_can_manage_categories(): void
    {
        $superAdmin = $this->makeStaffWithRole(Role::SUPER_ADMIN);
        $category = ProductCategory::factory()->create();

        $this->assertTrue(Gate::forUser($superAdmin)->allows('create', ProductCategory::class));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('update', $category));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('delete', $category));
        $this->assertFalse(Gate::forUser($superAdmin)->allows('forceDelete', $category));
    }

    public function test_admin_can_create_and_update_categories_but_not_delete(): void
    {
        $admin = $this->makeStaffWithRole(Role::ADMIN);
        $category = ProductCategory::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('create', ProductCategory::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $category));
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $category));
    }

    public function test_inventory_staff_can_view_categories_but_not_create(): void
    {
        $inventoryStaff = $this->makeStaffWithRole(Role::INVENTORY_STAFF);
        $category = ProductCategory::factory()->create();

        $this->assertTrue(Gate::forUser($inventoryStaff)->allows('view', $category));
        $this->assertFalse(Gate::forUser($inventoryStaff)->allows('create', ProductCategory::class));
    }

    public function test_sales_staff_cannot_view_categories(): void
    {
        $salesStaff = $this->makeStaffWithRole(Role::SALES_STAFF);
        $category = ProductCategory::factory()->create();

        $this->assertFalse(Gate::forUser($salesStaff)->allows('viewAny', ProductCategory::class));
        $this->assertFalse(Gate::forUser($salesStaff)->allows('view', $category));
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeStaffWithPermissions(string $roleSlug, array $permissionSlugs): User
    {
        foreach ($permissionSlugs as $slug) {
            Permission::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => str($slug)->headline()->toString(),
                    'group' => str($slug)->before('.')->toString(),
                    'guard_name' => 'web',
                    'description' => str($slug)->headline()->toString(),
                    'is_sensitive' => false,
                ],
            );
        }

        $role = Role::query()->updateOrCreate(
            ['slug' => $roleSlug],
            [
                'name' => str($roleSlug)->replace('_', ' ')->headline()->toString(),
                'guard_name' => 'web',
                'description' => str($roleSlug)->replace('_', ' ')->headline()->toString(),
                'is_system' => true,
                'sort_order' => 0,
            ],
        );

        $permissionIds = Permission::query()
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function makeStaffWithRole(string $roleSlug): User
    {
        $role = Role::query()->updateOrCreate(
            ['slug' => $roleSlug],
            [
                'name' => str($roleSlug)->replace('_', ' ')->headline()->toString(),
                'guard_name' => 'web',
                'description' => str($roleSlug)->replace('_', ' ')->headline()->toString(),
                'is_system' => true,
                'sort_order' => 0,
            ],
        );

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
