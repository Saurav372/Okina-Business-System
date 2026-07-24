<?php

namespace Tests\Feature;

use App\Events\AuditEvent;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductMedia;
use App\Models\ProductSeo;
use App\Models\Role;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdminProductSeoTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $seoMarketingUser;

    protected User $unauthorizedUser;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $permManageSeo = Permission::query()->updateOrCreate(['slug' => 'products.manage_seo'], [
            'name' => 'Products Manage SEO',
            'group' => 'products',
            'guard_name' => 'web',
            'description' => 'Manage product SEO metadata',
            'is_sensitive' => false,
        ]);

        $permManage = Permission::query()->updateOrCreate(['slug' => 'products.manage'], [
            'name' => 'Products Manage',
            'group' => 'products',
            'guard_name' => 'web',
            'description' => 'Manage products',
            'is_sensitive' => false,
        ]);

        $permDashboard = Permission::query()->updateOrCreate(['slug' => 'dashboard.access'], [
            'name' => 'Dashboard Access',
            'group' => 'settings',
            'guard_name' => 'web',
            'description' => 'Dashboard Access',
            'is_sensitive' => false,
        ]);

        $adminRole = Role::query()->updateOrCreate(['slug' => Role::ADMIN], [
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);
        $adminRole->permissions()->syncWithoutDetaching([$permManageSeo->id, $permManage->id, $permDashboard->id]);

        $marketingRole = Role::query()->updateOrCreate(['slug' => Role::SALES_STAFF], [
            'name' => 'Marketing',
            'guard_name' => 'web',
        ]);
        $marketingRole->permissions()->syncWithoutDetaching([$permManageSeo->id, $permDashboard->id]);

        $unauthRole = Role::query()->updateOrCreate(['slug' => Role::INVENTORY_STAFF], [
            'name' => 'Inventory Staff',
            'guard_name' => 'web',
        ]);
        $unauthRole->permissions()->syncWithoutDetaching([$permDashboard->id]);

        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($adminRole);

        $this->seoMarketingUser = User::factory()->create();
        $this->seoMarketingUser->roles()->attach($marketingRole);

        $this->unauthorizedUser = User::factory()->create();
        $this->unauthorizedUser->roles()->attach($unauthRole);

        $category = ProductCategory::factory()->create(['name' => 'T-Shirts', 'slug' => 't-shirts']);

        $this->product = Product::factory()->create([
            'primary_category_id' => $category->id,
            'name' => 'Custom Premium Polo T-Shirt',
            'slug' => 'custom-premium-polo-t-shirt',
            'short_description' => 'High quality custom embroidered polo shirt.',
            'description' => 'Full description of custom polo shirt.',
            'base_price_minor' => 129900,
            'currency' => 'INR',
            'status' => Product::STATUS_ACTIVE,
            'visibility' => Product::VISIBILITY_PUBLIC,
        ]);
    }

    public function test_admin_can_view_seo_tab_on_product_edit_screen(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.products.edit', [$this->product, 'tab' => 'seo']));

        $response->assertOk();
        $response->assertSee('SEO &amp; Social', false);
        $response->assertSee('Live Search Engine Snippet Preview');
        $response->assertSee('Custom Premium Polo T-Shirt');
    }

    public function test_admin_can_create_and_update_product_seo_metadata(): void
    {
        $payload = [
            'slug' => 'custom-polo-shirt',
            'meta_title' => 'Custom Polo Shirts Online | Okina Craft',
            'meta_description' => 'Order premium custom embroidered polo shirts in bulk with low MOQs and fast shipping.',
            'focus_keyword' => 'custom polo shirt',
            'canonical_url' => 'https://okinacraft.com/products/custom-polo-shirt',
            'robots_index' => '1',
            'robots_follow' => '1',
            'og_title' => 'Custom Polo Shirts - Bulk Order',
            'og_description' => 'Get premium polo shirts customized with your brand logo.',
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.products.seo.update', $this->product), $payload);

        $response->assertRedirect(route('admin.products.edit', [$this->product, 'tab' => 'seo']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'slug' => 'custom-polo-shirt',
        ]);

        $this->assertDatabaseHas('product_seos', [
            'product_id' => $this->product->id,
            'meta_title' => 'Custom Polo Shirts Online | Okina Craft',
            'meta_description' => 'Order premium custom embroidered polo shirts in bulk with low MOQs and fast shipping.',
            'focus_keyword' => 'custom polo shirt',
            'canonical_url' => 'https://okinacraft.com/products/custom-polo-shirt',
            'robots_index' => 1,
            'robots_follow' => 1,
            'og_title' => 'Custom Polo Shirts - Bulk Order',
        ]);
    }

    public function test_marketing_staff_with_manage_seo_permission_can_update_seo(): void
    {
        $payload = [
            'slug' => $this->product->slug,
            'meta_title' => 'Marketing SEO Title',
            'meta_description' => 'Marketing meta description.',
            'robots_index' => '1',
            'robots_follow' => '1',
        ];

        $response = $this->actingAs($this->seoMarketingUser)
            ->put(route('admin.products.seo.update', $this->product), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('product_seos', [
            'product_id' => $this->product->id,
            'meta_title' => 'Marketing SEO Title',
        ]);
    }

    public function test_user_without_manage_seo_permission_is_forbidden(): void
    {
        $payload = [
            'slug' => $this->product->slug,
            'meta_title' => 'Hacked Title',
        ];

        $response = $this->actingAs($this->unauthorizedUser)
            ->put(route('admin.products.seo.update', $this->product), $payload);

        $response->assertForbidden();
    }

    public function test_seo_update_dispatches_audit_event_with_changed_fields_only(): void
    {
        Event::fake([AuditEvent::class]);

        $payload = [
            'slug' => $this->product->slug,
            'meta_title' => 'Brand New Meta Title',
            'meta_description' => 'Brand new meta description snippet.',
            'robots_index' => '1',
            'robots_follow' => '1',
        ];

        $this->actingAs($this->adminUser)
            ->put(route('admin.products.seo.update', $this->product), $payload);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) {
            return $event->key === 'products.seo_updated'
                && isset($event->payload['changes']['meta_title'])
                && $event->payload['changes']['meta_title']['new'] === 'Brand New Meta Title';
        });
    }

    public function test_validation_rejects_invalid_canonical_url(): void
    {
        $payload = [
            'slug' => $this->product->slug,
            'canonical_url' => 'not-a-valid-url',
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.products.seo.update', $this->product), $payload);

        $response->assertSessionHasErrors('canonical_url');
    }

    public function test_product_slug_update_from_seo_form_sanitizes_and_enforces_uniqueness(): void
    {
        Product::factory()->create(['slug' => 'existing-product-slug']);

        $payload = [
            'slug' => 'Existing Product Slug!',
            'meta_title' => 'Test Title',
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.products.seo.update', $this->product), $payload);

        $response->assertSessionHasErrors('slug');
    }

    public function test_json_ld_schema_generator_returns_valid_product_json(): void
    {
        $presenter = $this->product->seoPresenter();
        $schema = $presenter->jsonLd();

        $this->assertEquals('https://schema.org', $schema['@context']);
        $this->assertEquals('Product', $schema['@type']);
        $this->assertEquals('Custom Premium Polo T-Shirt', $schema['name']);
        $this->assertEquals('High quality custom embroidered polo shirt.', $schema['description']);
        $this->assertEquals(config('app.name', 'Okina Craft'), $schema['brand']['name']);
        $this->assertEquals('1299.00', $schema['offers']['price']);
        $this->assertEquals('INR', $schema['offers']['priceCurrency']);
        $this->assertEquals('https://schema.org/InStock', $schema['offers']['availability']);
    }

    public function test_og_image_and_twitter_image_stored_file_association(): void
    {
        $file1 = StoredFile::factory()->create(['original_filename' => 'og-cover.png']);
        $file2 = StoredFile::factory()->create(['original_filename' => 'twitter-card.png']);

        $payload = [
            'slug' => $this->product->slug,
            'og_image_id' => $file1->id,
            'twitter_image_id' => $file2->id,
            'robots_index' => '1',
            'robots_follow' => '1',
        ];

        $this->actingAs($this->adminUser)
            ->put(route('admin.products.seo.update', $this->product), $payload);

        $this->product->refresh()->load('seo.ogImage', 'seo.twitterImage');
        $presenter = $this->product->seoPresenter();

        $this->assertEquals($file1->id, $presenter->ogImage()['id']);
        $this->assertEquals($file2->id, $presenter->twitterImage()['id']);
    }

    public function test_robots_directives_boolean_casting_and_persistence(): void
    {
        $payload = [
            'slug' => $this->product->slug,
            'robots_index' => '0',
            'robots_follow' => '1',
        ];

        $this->actingAs($this->adminUser)
            ->put(route('admin.products.seo.update', $this->product), $payload);

        $this->product->refresh()->load('seo');
        $presenter = $this->product->seoPresenter();

        $this->assertFalse($presenter->robotsIndex());
        $this->assertTrue($presenter->robotsFollow());
        $this->assertEquals('noindex,follow', $presenter->robots());
    }

    public function test_product_soft_delete_retains_seo_record(): void
    {
        ProductSeo::create([
            'product_id' => $this->product->id,
            'meta_title' => 'Soft Delete SEO Test',
        ]);

        $this->product->delete();

        $this->assertSoftDeleted('products', ['id' => $this->product->id]);
        $this->assertDatabaseHas('product_seos', [
            'product_id' => $this->product->id,
            'meta_title' => 'Soft Delete SEO Test',
            'deleted_at' => null,
        ]);
    }

    public function test_no_op_seo_update_does_not_trigger_duplicate_audit_log(): void
    {
        ProductSeo::create([
            'product_id' => $this->product->id,
            'meta_title' => 'Existing Title',
            'meta_description' => 'Existing Desc',
            'robots_index' => true,
            'robots_follow' => true,
        ]);

        Event::fake([AuditEvent::class]);

        $payload = [
            'slug' => $this->product->slug,
            'meta_title' => 'Existing Title',
            'meta_description' => 'Existing Desc',
            'robots_index' => '1',
            'robots_follow' => '1',
        ];

        $this->actingAs($this->adminUser)
            ->put(route('admin.products.seo.update', $this->product), $payload);

        Event::assertNotDispatched(AuditEvent::class);
    }

    public function test_seo_presenter_falls_back_correctly_when_product_seo_missing(): void
    {
        $presenter = $this->product->seoPresenter();

        $this->assertEquals('Custom Premium Polo T-Shirt', $presenter->metaTitle());
        $this->assertEquals('High quality custom embroidered polo shirt.', $presenter->metaDescription());
        $this->assertEquals(url('/products/'.$this->product->slug), $presenter->canonical());
        $this->assertEquals('index,follow', $presenter->robots());
        $this->assertNull($presenter->focusKeyword());
    }

    public function test_canonical_url_defaults_to_generated_product_url_when_canonical_url_is_null(): void
    {
        ProductSeo::create([
            'product_id' => $this->product->id,
            'canonical_url' => null,
        ]);

        $this->product->load('seo');
        $presenter = $this->product->seoPresenter();
        $this->assertEquals(url('/products/'.$this->product->slug), $presenter->canonical());
    }

    public function test_presenter_correctly_falls_back_to_product_cover_image(): void
    {
        $file = StoredFile::factory()->create(['original_filename' => 'cover-image.jpg']);
        ProductMedia::create([
            'product_id' => $this->product->id,
            'file_id' => $file->id,
            'role' => ProductMedia::ROLE_COVER,
            'sort_order' => 1,
        ]);

        $this->product->load(['coverMedia.file', 'media.file']);
        $presenter = $this->product->seoPresenter();

        $this->assertNotNull($presenter->ogImage());
        $this->assertEquals($file->id, $presenter->ogImage()['id']);
        $this->assertEquals($file->id, $presenter->twitterImage()['id']);
    }

    public function test_presenter_returns_generated_robots_directives_correctly(): void
    {
        ProductSeo::create([
            'product_id' => $this->product->id,
            'robots_index' => false,
            'robots_follow' => false,
        ]);

        $this->product->load('seo');
        $presenter = $this->product->seoPresenter();
        $this->assertEquals('noindex,nofollow', $presenter->robots());
    }

    public function test_product_schema_generator_gracefully_handles_missing_optional_data(): void
    {
        $minimalProduct = Product::factory()->create([
            'name' => 'Minimal Product',
            'short_description' => null,
            'description' => null,
        ]);

        $presenter = $minimalProduct->seoPresenter();
        $schema = $presenter->jsonLd();

        $this->assertEquals('Minimal Product', $schema['name']);
        $this->assertEquals('', $schema['description']);
        $this->assertArrayNotHasKey('category', $schema);
        $this->assertArrayNotHasKey('image', $schema);
    }

    public function test_presenter_uses_generated_canonical_route_when_route_changes(): void
    {
        $this->product->slug = 'updated-route-slug';
        $this->product->save();

        $presenter = $this->product->seoPresenter();
        $this->assertEquals(url('/products/updated-route-slug'), $presenter->canonical());
    }
}
