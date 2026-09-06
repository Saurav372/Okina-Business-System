<?php

namespace Tests\Feature;

use App\Models\CustomerAccount;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\ProductVariant;
use App\Models\StoredFile;
use App\Services\ProtectedMockupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProtectedMockupGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_generate_a_private_watermarked_png_from_their_artwork(): void
    {
        Storage::fake('private');

        $customer = CustomerAccount::factory()->create([
            'status' => CustomerAccount::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $product = $this->customTee();

        $upload = $this->actingAs($customer, 'customer')->postJson('/api/catalog/products/'.$product->slug.'/design-upload', [
            'design_file' => UploadedFile::fake()->image('transparent-logo.png', 900, 900),
            'sku_code' => 'MOCKUP-INK-M',
            'selected_options' => ['color' => 'ink', 'size' => 'm'],
            'print_position' => 'front',
            'print_method' => 'dtf',
            'placement' => ['x' => 54, 'y' => 46, 'scale' => 0.9, 'rotation' => 0],
        ])->assertOk();

        $sourcePublicId = $upload->json('data.file.public_id');
        $response = $this->actingAs($customer, 'customer')->postJson(
            '/api/catalog/products/'.$product->slug.'/protected-mockup/'.$sourcePublicId,
            [
                'color_code' => 'ink',
                'print_position' => 'front',
                'placement' => ['x' => 54, 'y' => 46, 'scale' => 0.9],
            ]
        );

        $response->assertCreated()
            ->assertJsonPath('data.file.role', 'protected_mockup')
            ->assertJsonPath('data.file.file_kind', StoredFile::KIND_MOCKUP)
            ->assertJsonPath('data.file.visibility', StoredFile::VISIBILITY_CUSTOMER_VISIBLE)
            ->assertJsonPath('data.watermark.applied', true)
            ->assertJsonPath('data.watermark.version', ProtectedMockupService::WATERMARK_VERSION)
            ->assertJsonPath('data.preview_url', fn (string $url): bool => str_contains($url, '/files/'));

        $mockup = StoredFile::query()
            ->where('public_id', $response->json('data.file.public_id'))
            ->firstOrFail();
        $bytes = Storage::disk('private')->get($mockup->storage_path);

        $this->assertSame("\x89PNG\r\n\x1a\n", substr($bytes, 0, 8));
        $this->assertSame($sourcePublicId, data_get($mockup->metadata, 'protected_mockup.source_file_public_id'));
        $this->assertSame(ProtectedMockupService::WATERMARK_VERSION, data_get($mockup->metadata, 'protected_mockup.watermark_version'));
        $this->assertSame(1200, imagesx(imagecreatefromstring($bytes)));
        Storage::disk('private')->assertExists($mockup->storage_path);
    }

    public function test_customer_cannot_generate_a_mockup_from_another_customers_artwork(): void
    {
        Storage::fake('private');

        $owner = CustomerAccount::factory()->create();
        $otherCustomer = CustomerAccount::factory()->create();
        $product = $this->customTee();
        $source = StoredFile::factory()->create([
            'customer_id' => $owner->customer_id,
            'uploaded_by_customer_id' => $owner->customer_id,
            'file_kind' => StoredFile::KIND_ORIGINAL_UPLOAD,
            'visibility' => StoredFile::VISIBILITY_PRIVATE,
            'status' => StoredFile::STATUS_ACTIVE,
            'storage_disk' => 'private',
            'storage_path' => 'files/owner/artwork.png',
            'mime_type' => 'image/png',
            'extension' => 'png',
        ]);
        Storage::disk('private')->put($source->storage_path, UploadedFile::fake()->image('artwork.png')->getContent());

        $this->actingAs($otherCustomer, 'customer')
            ->postJson('/api/catalog/products/'.$product->slug.'/protected-mockup/'.$source->public_id, [
                'color_code' => 'ink',
                'print_position' => 'front',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('files', 1);
    }

    private function customTee(): Product
    {
        $product = Product::factory()->create([
            'slug' => 'protected-mockup-tee',
            'name' => 'Protected Mockup Tee',
            'status' => Product::STATUS_ACTIVE,
            'visibility' => Product::VISIBILITY_PUBLIC,
            'customization_mode' => Product::CUSTOMIZATION_REQUIRED,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Colour',
            'code' => 'color',
            'values' => [
                ['code' => 'ink', 'label' => 'Ink Black', 'sort_order' => 10, 'is_active' => true],
            ],
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size',
            'code' => 'size',
            'values' => [
                ['code' => 'm', 'label' => 'M', 'sort_order' => 10, 'is_active' => true],
            ],
        ]);
        ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku_code' => 'MOCKUP-INK-M',
            'variant_key' => 'color:ink|size:m',
            'option_values' => [
                ['code' => 'ink', 'label' => 'Ink Black'],
                ['code' => 'm', 'label' => 'M'],
            ],
            'price_minor' => 69900,
        ]);

        return $product;
    }
}
