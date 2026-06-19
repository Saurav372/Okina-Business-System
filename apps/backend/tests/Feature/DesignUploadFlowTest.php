<?php

namespace Tests\Feature;

use App\Models\CustomerAccount;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use App\Models\ProductVariant;
use App\Models\StoredFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DesignUploadFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_upload_a_design_file_for_a_customizable_product(): void
    {
        Storage::fake('private');

        $customerAccount = CustomerAccount::factory()->create([
            'status' => CustomerAccount::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $category = ProductCategory::factory()->create([
            'slug' => 'custom-apparel',
        ]);

        $product = Product::factory()->create([
            'slug' => 'custom-tee',
            'primary_category_id' => $category->id,
            'customization_mode' => Product::CUSTOMIZATION_REQUIRED,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Color',
            'code' => 'color',
            'values' => [
                ['code' => 'black', 'label' => 'Black', 'sort_order' => 10, 'is_active' => true],
            ],
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size',
            'code' => 'size',
            'values' => [
                ['code' => 'm', 'label' => 'Medium', 'sort_order' => 10, 'is_active' => true],
            ],
        ]);

        ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-CUSTOM-TEE-M',
            'variant_key' => 'color:black|size:m',
            'option_values' => [
                ['code' => 'black', 'label' => 'Black'],
                ['code' => 'm', 'label' => 'Medium'],
            ],
            'track_stock' => true,
            'stock_quantity' => 9,
            'price_minor' => 1899,
        ]);

        $response = $this->actingAs($customerAccount, 'customer')->postJson('/api/catalog/products/custom-tee/design-upload', [
            'design_file' => UploadedFile::fake()->image('logo-design.png', 1200, 1200),
            'sku_code' => 'SKU-CUSTOM-TEE-M',
            'selected_options' => [
                'color' => 'black',
                'size' => 'm',
            ],
            'print_position' => 'front',
            'print_method' => 'dtf',
            'customer_note' => 'Keep it centered',
            'placement' => [
                'x' => 42,
                'y' => 58,
                'scale' => 0.72,
                'rotation' => 0,
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.product.slug', 'custom-tee')
            ->assertJsonPath('data.selection.sku_code', 'SKU-CUSTOM-TEE-M')
            ->assertJsonPath('data.selection.variant_key', 'color:black|size:m')
            ->assertJsonPath('data.selection.print_position', 'front')
            ->assertJsonPath('data.selection.print_method', 'dtf')
            ->assertJsonPath('data.file.visibility', 'private')
            ->assertJsonPath('data.file.file_kind', 'original_upload')
            ->assertJsonPath('data.file.has_preview', true)
            ->assertJsonPath('data.file.preview.mime_type', 'image/png')
            ->assertJsonPath('data.customization_snapshot.files.0.role', 'original_upload')
            ->assertJsonPath('data.customization_snapshot.print_method', 'dtf')
            ->assertJsonPath('data.customization_snapshot.placement.scale', 0.72)
            ->assertJsonPath('data.mockup_preview_url', fn (string $url): bool => str_contains($url, '/api/catalog/products/custom-tee/design-preview/'))
            ->assertJsonMissingPath('data.file.storage_path')
            ->assertJsonMissingPath('data.file.preview.path');

        $storedFile = StoredFile::query()->where('public_id', $response->json('data.file.public_id'))->firstOrFail();
        Storage::disk('private')->assertExists($storedFile->storage_path);
        Storage::disk('private')->assertExists($storedFile->previewPath());

        $this->get($response->json('data.mockup_preview_url'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8')
            ->assertSee('custom-tee', false)
            ->assertSee('front', false)
            ->assertSee('dtf', false)
            ->assertSee('logo-design', false);
    }

    public function test_customization_snapshot_is_public_safe_and_normalized_for_cart_and_order_steps(): void
    {
        Storage::fake('private');

        $customerAccount = CustomerAccount::factory()->create([
            'status' => CustomerAccount::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $category = ProductCategory::factory()->create([
            'slug' => 'custom-apparel',
        ]);

        $product = Product::factory()->create([
            'slug' => 'custom-tee',
            'primary_category_id' => $category->id,
            'customization_mode' => Product::CUSTOMIZATION_REQUIRED,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size',
            'code' => 'size',
            'values' => [
                ['code' => 'm', 'label' => 'Medium', 'sort_order' => 10, 'is_active' => true],
            ],
        ]);

        ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-CUSTOM-TEE-M',
            'variant_key' => 'size:m',
            'option_values' => [
                ['code' => 'm', 'label' => 'Medium'],
            ],
        ]);

        $response = $this->actingAs($customerAccount, 'customer')->postJson('/api/catalog/products/custom-tee/design-upload', [
            'design_file' => UploadedFile::fake()->image('logo-design.png', 1200, 1200),
            'sku_code' => 'SKU-CUSTOM-TEE-M',
            'selected_options' => [
                'size' => 'm',
            ],
            'print_position' => 'front',
            'print_method' => 'dtf',
            'customer_note' => 'Keep it centered',
            'placement' => [
                'x' => -200,
                'y' => 240,
                'scale' => 9,
                'rotation' => -90,
            ],
        ]);

        $fileId = $response->json('data.file.public_id');

        $response->assertOk()
            ->assertJsonPath('data.customization_snapshot.schema_version', 1)
            ->assertJsonPath('data.customization_snapshot.product.slug', 'custom-tee')
            ->assertJsonPath('data.customization_snapshot.selected_options_snapshot.0.option_code', 'size')
            ->assertJsonPath('data.customization_snapshot.selected_options_snapshot.0.value_code', 'm')
            ->assertJsonPath('data.customization_snapshot.selected_options_snapshot.0.value_label', 'Medium')
            ->assertJsonPath('data.customization_snapshot.placement.x', 0)
            ->assertJsonPath('data.customization_snapshot.placement.y', 100)
            ->assertJsonPath('data.customization_snapshot.placement.scale', 1.5)
            ->assertJsonPath('data.customization_snapshot.placement.rotation', -45)
            ->assertJsonPath('data.customization_snapshot.files.0.public_id', $fileId)
            ->assertJsonPath('data.customization_snapshot.files.0.role', 'original_upload')
            ->assertJsonPath('data.customization_snapshot.files.0.has_preview', true)
            ->assertJsonPath('data.customization_snapshot.mockup_preview.role', 'mockup_preview')
            ->assertJsonPath('data.customization_snapshot.mockup_preview.source_file_public_id', $fileId)
            ->assertJsonMissingPath('data.customization_snapshot.files.0.storage_path')
            ->assertJsonMissingPath('data.customization_snapshot.files.0.preview.path');

        $storedFile = StoredFile::query()->where('public_id', $fileId)->firstOrFail();
        $snapshotJson = json_encode(data_get($storedFile->metadata, 'customization'));

        $this->assertIsString($snapshotJson);
        $this->assertStringNotContainsString($storedFile->storage_path, $snapshotJson);
        $this->assertStringNotContainsString((string) $storedFile->previewPath(), $snapshotJson);
    }

    public function test_invalid_uploads_are_rejected_before_file_storage(): void
    {
        Storage::fake('private');

        $customerAccount = CustomerAccount::factory()->create([
            'status' => CustomerAccount::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $category = ProductCategory::factory()->create();

        $product = Product::factory()->create([
            'slug' => 'custom-tee',
            'primary_category_id' => $category->id,
            'customization_mode' => Product::CUSTOMIZATION_REQUIRED,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size',
            'code' => 'size',
            'values' => [
                ['code' => 'm', 'label' => 'Medium', 'sort_order' => 10, 'is_active' => true],
            ],
        ]);

        ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-CUSTOM-TEE-M',
            'variant_key' => 'size:m',
            'option_values' => [
                ['code' => 'm', 'label' => 'Medium'],
            ],
        ]);

        $this->actingAs($customerAccount, 'customer')->postJson('/api/catalog/products/custom-tee/design-upload', [
            'design_file' => UploadedFile::fake()->create('shell.php.jpg', 10, 'image/jpeg'),
            'sku_code' => 'SKU-CUSTOM-TEE-M',
            'selected_options' => [
                'size' => 'm',
            ],
            'print_position' => 'front',
            'print_method' => 'dtf',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        $this->actingAs($customerAccount, 'customer')->postJson('/api/catalog/products/custom-tee/design-upload', [
            'design_file' => UploadedFile::fake()->image('logo-design.png', 1200, 1200),
            'sku_code' => 'SKU-CUSTOM-TEE-M',
            'selected_options' => [
                'size' => 'xl',
            ],
            'print_position' => 'front',
            'print_method' => 'dtf',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['selection']);
    }

    public function test_mockup_preview_links_reject_tampering_and_expiration(): void
    {
        Storage::fake('private');

        $customerAccount = CustomerAccount::factory()->create([
            'status' => CustomerAccount::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $category = ProductCategory::factory()->create([
            'slug' => 'custom-apparel',
        ]);

        $product = Product::factory()->create([
            'slug' => 'custom-tee',
            'primary_category_id' => $category->id,
            'customization_mode' => Product::CUSTOMIZATION_REQUIRED,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size',
            'code' => 'size',
            'values' => [
                ['code' => 'm', 'label' => 'Medium', 'sort_order' => 10, 'is_active' => true],
            ],
        ]);

        ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-CUSTOM-TEE-M',
            'variant_key' => 'size:m',
            'option_values' => [
                ['code' => 'm', 'label' => 'Medium'],
            ],
        ]);

        $response = $this->actingAs($customerAccount, 'customer')->postJson('/api/catalog/products/custom-tee/design-upload', [
            'design_file' => UploadedFile::fake()->image('logo-design.png', 1200, 1200),
            'sku_code' => 'SKU-CUSTOM-TEE-M',
            'selected_options' => [
                'size' => 'm',
            ],
            'print_position' => 'front',
            'print_method' => 'dtf',
        ]);

        $response->assertOk();

        $signedUrl = $response->json('data.mockup_preview_url');

        $this->get($signedUrl)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8');

        $this->get(str_replace('print_method=dtf', 'print_method=screen', $signedUrl))
            ->assertForbidden();

        $expiredUrl = URL::temporarySignedRoute('catalog.products.mockup-preview', now()->subMinute(), [
            'product' => $product->slug,
            'preview_file' => $response->json('data.file.public_id'),
            'print_position' => 'front',
            'print_method' => 'dtf',
            'placement' => [
                'x' => 50,
                'y' => 50,
                'scale' => 1,
                'rotation' => 0,
            ],
        ]);

        $this->get($expiredUrl)
            ->assertForbidden();
    }

    public function test_preview_link_endpoint_returns_a_fresh_signed_url_for_adjusted_placement(): void
    {
        Storage::fake('private');

        $customerAccount = CustomerAccount::factory()->create([
            'status' => CustomerAccount::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $category = ProductCategory::factory()->create([
            'slug' => 'custom-apparel',
        ]);

        $product = Product::factory()->create([
            'slug' => 'custom-tee',
            'primary_category_id' => $category->id,
            'customization_mode' => Product::CUSTOMIZATION_REQUIRED,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size',
            'code' => 'size',
            'values' => [
                ['code' => 'm', 'label' => 'Medium', 'sort_order' => 10, 'is_active' => true],
            ],
        ]);

        ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-CUSTOM-TEE-M',
            'variant_key' => 'size:m',
            'option_values' => [
                ['code' => 'm', 'label' => 'Medium'],
            ],
        ]);

        $uploadResponse = $this->actingAs($customerAccount, 'customer')->postJson('/api/catalog/products/custom-tee/design-upload', [
            'design_file' => UploadedFile::fake()->image('logo-design.png', 1200, 1200),
            'sku_code' => 'SKU-CUSTOM-TEE-M',
            'selected_options' => [
                'size' => 'm',
            ],
            'print_position' => 'front',
            'print_method' => 'dtf',
        ]);

        $uploadResponse->assertOk();

        $fileId = $uploadResponse->json('data.file.public_id');

        $linkResponse = $this->actingAs($customerAccount, 'customer')->postJson('/api/catalog/products/custom-tee/design-preview/'.$fileId.'/link', [
            'print_position' => 'front',
            'print_method' => 'dtf',
            'placement' => [
                'x' => -15,
                'y' => 140,
                'scale' => 2.25,
                'rotation' => 90,
            ],
        ]);

        $linkResponse->assertRedirect();

        $signedUrl = $linkResponse->headers->get('Location');
        $this->assertIsString($signedUrl);

        $this->get($signedUrl)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8')
            ->assertSee('x 0 / y 100 / scale 1.5 / rotation 45', false);
    }
}
