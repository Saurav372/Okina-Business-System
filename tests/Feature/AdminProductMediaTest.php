<?php

namespace Tests\Feature;

use App\Events\AuditEvent;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Role;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminProductMediaTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions (mirrors AdminProductSkuTest pattern)
        $permView = Permission::query()->updateOrCreate(['slug' => 'products.view'], [
            'name' => 'Products View',
            'group' => 'products',
            'guard_name' => 'web',
            'description' => 'View products',
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

        $permFilesDownload = Permission::query()->updateOrCreate(['slug' => 'files.download_private'], [
            'name' => 'Files Download Private',
            'group' => 'files',
            'guard_name' => 'web',
            'description' => 'Download private files',
            'is_sensitive' => false,
        ]);

        $roleInventory = Role::query()->updateOrCreate(['slug' => Role::INVENTORY_STAFF], [
            'name' => 'Inventory Staff',
            'guard_name' => 'web',
            'description' => 'Inventory staff role',
            'is_system' => true,
            'sort_order' => 0,
        ]);
        $roleInventory->permissions()->sync(
            Permission::query()->whereIn('slug', ['products.view', 'dashboard.access'])->pluck('id')->all()
        );

        $roleAdmin = Role::query()->updateOrCreate(['slug' => Role::ADMIN], [
            'name' => 'Administrator',
            'guard_name' => 'web',
            'description' => 'Admin role',
            'is_system' => true,
            'sort_order' => 0,
        ]);
        $roleAdmin->permissions()->sync(
            Permission::query()
                ->whereIn('slug', ['products.view', 'products.manage', 'dashboard.access', 'files.download_private'])
                ->pluck('id')
                ->all()
        );

        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->sync([$roleAdmin->id]);

        $this->staffUser = User::factory()->create();
        $this->staffUser->roles()->sync([$roleInventory->id]);
    }

    // ─────────────────────────────── Helpers ───────────────────────────────

    private function product(): Product
    {
        return Product::factory()->create();
    }

    private function fakeImage(string $name = 'photo.jpg', int $kilobytes = 100): UploadedFile
    {
        Storage::fake('private');

        return UploadedFile::fake()->image($name, 800, 600)->size($kilobytes);
    }

    /**
     * Create a ProductMedia + StoredFile pair attached to a product.
     */
    private function attachMedia(Product $product, string $role = ProductMedia::ROLE_GALLERY, int $sortOrder = 0): ProductMedia
    {
        $uid = Str::random(8);
        $file = StoredFile::factory()->create([
            'storage_disk' => 'private',
            'storage_path' => "files/FIL-TEST-{$uid}/file_{$sortOrder}.jpg",
            'file_kind' => StoredFile::KIND_ATTACHMENT,
            'visibility' => StoredFile::VISIBILITY_PUBLIC_SAFE_PREVIEW,
            'mime_type' => 'image/jpeg',
        ]);

        return ProductMedia::create([
            'product_id' => $product->id,
            'file_id' => $file->id,
            'role' => $role,
            'alt_text' => null,
            'sort_order' => $sortOrder,
        ]);
    }

    // ─────────────────────────────── 1. Permission Tests ──────────────────

    public function test_guest_cannot_upload_images(): void
    {
        $product = $this->product();

        $this->post(route('admin.products.media.store', $product), [
            'images' => [$this->fakeImage()],
        ])->assertRedirect(route('login'));
    }

    public function test_inventory_staff_cannot_upload_images(): void
    {
        $product = $this->product();

        $this->actingAs($this->staffUser)
            ->post(route('admin.products.media.store', $product), [
                'images' => [$this->fakeImage()],
            ])->assertForbidden();
    }

    // ─────────────────────────────── 2. Upload: Single ────────────────────

    public function test_admin_uploads_single_image_which_becomes_cover(): void
    {
        Storage::fake('private');
        Event::fake();

        $admin = $this->adminUser;
        $product = $this->product();

        $this->actingAs($admin)
            ->post(route('admin.products.media.store', $product), [
                'images' => [$this->fakeImage('cover.jpg')],
            ])->assertRedirect(route('admin.products.edit', [$product, 'tab' => 'media']));

        $this->assertDatabaseCount('product_media', 1);
        $this->assertDatabaseHas('product_media', [
            'product_id' => $product->id,
            'role' => ProductMedia::ROLE_COVER,
            'sort_order' => 0,
        ]);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($product) {
            return $event->key === 'products.media_uploaded'
                && $event->payload['product_id'] === $product->id
                && $event->payload['file_count'] === 1;
        });
    }

    // ─────────────────────────────── 3. Upload: Multiple ──────────────────

    public function test_admin_uploads_multiple_images_first_becomes_cover(): void
    {
        Storage::fake('private');
        Event::fake();

        $admin = $this->adminUser;
        $product = $this->product();

        $this->actingAs($admin)
            ->post(route('admin.products.media.store', $product), [
                'images' => [
                    $this->fakeImage('a.jpg'),
                    $this->fakeImage('b.png'),
                    $this->fakeImage('c.webp'),
                ],
                'alt_text' => 'Product shot',
            ])->assertRedirect();

        $this->assertDatabaseCount('product_media', 3);

        $media = ProductMedia::where('product_id', $product->id)->orderBy('sort_order')->get();

        $this->assertEquals(ProductMedia::ROLE_COVER, $media[0]->role);
        $this->assertEquals(ProductMedia::ROLE_GALLERY, $media[1]->role);
        $this->assertEquals(ProductMedia::ROLE_GALLERY, $media[2]->role);
        $this->assertEquals('Product shot', $media[0]->alt_text);

        Event::assertDispatched(AuditEvent::class, fn (AuditEvent $e) => $e->key === 'products.media_uploaded'
            && $e->payload['file_count'] === 3
        );
    }

    // ─────────────────────────────── 4. Delete: Cover Auto-Promotion ──────

    public function test_deleting_cover_promotes_next_gallery_image(): void
    {
        Storage::fake('private');
        Event::fake();

        $admin = $this->adminUser;
        $product = $this->product();

        $cover = $this->attachMedia($product, ProductMedia::ROLE_COVER, 0);
        $gallery = $this->attachMedia($product, ProductMedia::ROLE_GALLERY, 1);

        // Add fake disk file so FileUploadService::delete() doesn't error
        Storage::disk('private')->put($cover->file->storage_path, 'fake');

        $this->actingAs($admin)
            ->delete(route('admin.products.media.destroy', [$product, $cover]))
            ->assertRedirect();

        $this->assertDatabaseMissing('product_media', ['id' => $cover->id]);
        $this->assertDatabaseHas('product_media', [
            'id' => $gallery->id,
            'role' => ProductMedia::ROLE_COVER,
        ]);

        Event::assertDispatched(AuditEvent::class, fn (AuditEvent $e) => $e->key === 'products.media_deleted'
            && $e->payload['media_id'] === $cover->id
        );
    }

    // ─────────────────────────────── 5. Delete: Last Image ────────────────

    public function test_deleting_last_image_leaves_no_media(): void
    {
        Storage::fake('private');
        Event::fake();

        $admin = $this->adminUser;
        $product = $this->product();
        $media = $this->attachMedia($product, ProductMedia::ROLE_COVER, 0);

        Storage::disk('private')->put($media->file->storage_path, 'fake');

        $this->actingAs($admin)
            ->delete(route('admin.products.media.destroy', [$product, $media]))
            ->assertRedirect();

        $this->assertDatabaseCount('product_media', 0);
    }

    // ─────────────────────────────── 6. Set Cover ─────────────────────────

    public function test_set_cover_swaps_roles_correctly(): void
    {
        Event::fake();

        $admin = $this->adminUser;
        $product = $this->product();

        $cover = $this->attachMedia($product, ProductMedia::ROLE_COVER, 0);
        $gallery = $this->attachMedia($product, ProductMedia::ROLE_GALLERY, 1);

        $this->actingAs($admin)
            ->post(route('admin.products.media.cover', [$product, $gallery]))
            ->assertRedirect();

        $this->assertDatabaseHas('product_media', ['id' => $cover->id,   'role' => ProductMedia::ROLE_GALLERY]);
        $this->assertDatabaseHas('product_media', ['id' => $gallery->id, 'role' => ProductMedia::ROLE_COVER]);

        Event::assertDispatched(AuditEvent::class, fn (AuditEvent $e) => $e->key === 'products.media_cover_changed'
            && $e->payload['media_id'] === $gallery->id
        );
    }

    // ─────────────────────────────── 7. Reorder: Valid ────────────────────

    public function test_reorder_updates_sort_order_correctly(): void
    {
        $admin = $this->adminUser;
        $product = $this->product();

        $a = $this->attachMedia($product, ProductMedia::ROLE_COVER, 0);
        $b = $this->attachMedia($product, ProductMedia::ROLE_GALLERY, 1);
        $c = $this->attachMedia($product, ProductMedia::ROLE_GALLERY, 2);

        // Reverse order: c, b, a
        $this->actingAs($admin)
            ->postJson(route('admin.products.media.reorder', $product), [
                'ids' => [$c->id, $b->id, $a->id],
            ])->assertNoContent();

        $this->assertDatabaseHas('product_media', ['id' => $c->id, 'sort_order' => 0]);
        $this->assertDatabaseHas('product_media', ['id' => $b->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('product_media', ['id' => $a->id, 'sort_order' => 2]);
    }

    // ─────────────────────────────── 8. Reorder: Duplicate IDs ───────────

    public function test_reorder_with_duplicate_ids_returns_422(): void
    {
        $admin = $this->adminUser;
        $product = $this->product();

        $a = $this->attachMedia($product, ProductMedia::ROLE_COVER, 0);
        $b = $this->attachMedia($product, ProductMedia::ROLE_GALLERY, 1);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.products.media.reorder', $product), [
                'ids' => [$a->id, $a->id, $b->id], // duplicate
            ]);

        $this->assertEquals(422, $response->status(), 'Expected 422 for duplicate IDs, got: '.$response->status().' body: '.$response->content());

        // Order unchanged
        $this->assertDatabaseHas('product_media', ['id' => $a->id, 'sort_order' => 0]);
        $this->assertDatabaseHas('product_media', ['id' => $b->id, 'sort_order' => 1]);
    }

    // ─────────────────────────────── 9. Reorder: Missing IDs ─────────────

    public function test_reorder_with_missing_ids_returns_422(): void
    {
        $admin = $this->adminUser;
        $product = $this->product();

        $a = $this->attachMedia($product, ProductMedia::ROLE_COVER, 0);
        $b = $this->attachMedia($product, ProductMedia::ROLE_GALLERY, 1);
        $c = $this->attachMedia($product, ProductMedia::ROLE_GALLERY, 2);

        // Missing $c — partial list
        $this->actingAs($admin)
            ->postJson(route('admin.products.media.reorder', $product), [
                'ids' => [$a->id, $b->id],
            ])->assertStatus(422)
            ->assertJsonPath('errors.ids.0', fn ($v) => is_string($v));
    }

    // ─────────────────────────────── 10. Reorder: Foreign ID ─────────────

    public function test_reorder_with_foreign_product_id_returns_422(): void
    {
        $admin = $this->adminUser;
        $product1 = $this->product();
        $product2 = $this->product();

        $a = $this->attachMedia($product1, ProductMedia::ROLE_COVER, 0);
        $x = $this->attachMedia($product2, ProductMedia::ROLE_COVER, 0); // belongs to product2

        $this->actingAs($admin)
            ->postJson(route('admin.products.media.reorder', $product1), [
                'ids' => [$a->id, $x->id], // $x is foreign
            ])->assertStatus(422)
            ->assertJsonPath('errors.ids.0', fn ($v) => is_string($v));
    }

    // ─────────────────────────────── 11. Cross-Product 404 ───────────────

    public function test_delete_media_belonging_to_another_product_returns_404(): void
    {
        $admin = $this->adminUser;
        $product1 = $this->product();
        $product2 = $this->product();

        $media = $this->attachMedia($product2, ProductMedia::ROLE_COVER, 0);

        $this->actingAs($admin)
            ->delete(route('admin.products.media.destroy', [$product1, $media]))
            ->assertNotFound();
    }

    // ─────────────────────────────── 12. Upload Limit > 10 ───────────────

    public function test_upload_more_than_10_images_returns_422(): void
    {
        Storage::fake('private');

        $admin = $this->adminUser;
        $product = $this->product();

        $images = array_fill(0, 11, $this->fakeImage());

        $this->actingAs($admin)
            ->post(route('admin.products.media.store', $product), [
                'images' => $images,
            ])->assertRedirect()
            ->assertSessionHasErrors('images');
    }

    // ─────────────────────────────── 13. Upload Redirect Preserves Tab ───

    public function test_upload_validation_error_redirects_to_media_tab(): void
    {
        Storage::fake('private');

        $admin = $this->adminUser;
        $product = $this->product();

        $response = $this->actingAs($admin)
            ->post(route('admin.products.media.store', $product), [
                'images' => [], // empty — will fail min:1
            ]);

        $response->assertRedirect();
        $this->assertStringContainsString(
            'tab=media',
            $response->headers->get('Location')
        );
    }
}
