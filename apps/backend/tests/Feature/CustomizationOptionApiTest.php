<?php

namespace Tests\Feature;

use App\Contracts\CustomizationOptionContract;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomizationOptionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customization_option_api_returns_public_safe_option_groups_and_sku_choices(): void
    {
        $category = ProductCategory::factory()->create([
            'name' => 'Custom Apparel',
            'slug' => 'custom-apparel',
        ]);

        $product = Product::factory()->create([
            'name' => 'Custom Tee',
            'slug' => 'custom-tee',
            'primary_category_id' => $category->id,
            'customization_mode' => Product::CUSTOMIZATION_REQUIRED,
        ]);

        $sizeVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size',
            'code' => 'size',
            'values' => [
                [
                    'code' => 's',
                    'label' => 'Small',
                    'sort_order' => 10,
                    'is_active' => true,
                    'metadata' => ['internal' => 'hidden'],
                ],
                [
                    'code' => 'm',
                    'label' => 'Medium',
                    'sort_order' => 20,
                    'is_active' => true,
                    'metadata' => ['internal' => 'hidden'],
                ],
            ],
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Color',
            'code' => 'color',
            'values' => [
                [
                    'code' => 'black',
                    'label' => 'Black',
                    'sort_order' => 10,
                    'is_active' => true,
                ],
            ],
        ]);

        ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-MEDIUM-BLACK',
            'variant_key' => 'color:black|size:m',
            'option_values' => [
                ['code' => 'black', 'label' => 'Black'],
                ['code' => 'm', 'label' => 'Medium'],
            ],
            'price_minor' => 1899,
            'stock_quantity' => 12,
        ]);

        ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-SMALL-BLACK',
            'variant_key' => 'color:black|size:s',
            'option_values' => [
                ['code' => 'black', 'label' => 'Black'],
                ['code' => 's', 'label' => 'Small'],
            ],
            'price_minor' => 1799,
            'stock_quantity' => 8,
        ]);

        $this->getJson('/api/catalog/products/custom-tee/customization-options')
            ->assertOk()
            ->assertJsonPath('data.product.slug', 'custom-tee')
            ->assertJsonPath('data.product.customization_mode', Product::CUSTOMIZATION_REQUIRED)
            ->assertJsonPath('data.option_groups.0.code', 'size')
            ->assertJsonPath('data.size_options.0.code', 's')
            ->assertJsonPath('data.size_options.1.code', 'm')
            ->assertJsonMissingPath('data.option_groups.0.values.0.metadata')
            ->assertJsonPath('data.print_positions.0.code', 'front')
            ->assertJsonPath('data.print_methods.0.code', 'dtf')
            ->assertJsonPath('data.validation.requires_print_position', true)
            ->assertJsonPath('data.validation.allowed_print_methods.0', 'dtf')
            ->assertJsonPath('data.skus.0.variant_key', 'color:black|size:m');

        $rules = app(CustomizationOptionContract::class);

        $valid = $rules->validateSelection('custom-tee', [
            'selected_options' => [
                'size' => 'm',
                'color' => 'black',
            ],
            'sku_code' => 'SKU-MEDIUM-BLACK',
            'print_position' => 'front',
            'print_method' => 'dtf',
        ]);

        $this->assertTrue($valid['valid']);
        $this->assertSame('color:black|size:m', $valid['resolved_variant_key']);
        $this->assertSame('SKU-MEDIUM-BLACK', $valid['matched_sku']['sku_code']);

        $invalidMethod = $rules->validateSelection('custom-tee', [
            'selected_options' => [
                'size' => 'm',
                'color' => 'black',
            ],
            'sku_code' => 'SKU-MEDIUM-BLACK',
            'print_position' => 'left_chest',
            'print_method' => 'screen_print',
        ]);

        $this->assertFalse($invalidMethod['valid']);
        $this->assertContains('print_method_position_incompatible', $invalidMethod['errors']);

        $invalidSku = $rules->validateSelection('custom-tee', [
            'selected_options' => [
                'size' => 'm',
                'color' => 'black',
            ],
            'sku_code' => 'SKU-SMALL-BLACK',
            'print_position' => 'front',
            'print_method' => 'dtf',
        ]);

        $this->assertFalse($invalidSku['valid']);
        $this->assertContains('sku_code_mismatch', $invalidSku['errors']);
    }

    public function test_customization_options_are_hidden_from_private_products(): void
    {
        $category = ProductCategory::factory()->create();

        $privateProduct = Product::factory()->draft()->create([
            'slug' => 'private-tee',
            'primary_category_id' => $category->id,
        ]);

        $this->getJson('/api/catalog/products/private-tee/customization-options')
            ->assertNotFound();

        $this->assertNull(app(CustomizationOptionContract::class)->product($privateProduct->slug));
    }
}
