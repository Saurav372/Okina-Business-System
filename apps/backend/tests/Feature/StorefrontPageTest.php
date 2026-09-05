<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use App\Models\ProductVariant;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_home_uses_design_five_shell_and_live_public_catalog_data(): void
    {
        $category = ProductCategory::factory()->create([
            'name' => 'Studio T-Shirts',
            'slug' => 'studio-t-shirts',
            'sort_order' => 1,
        ]);
        $product = Product::factory()->create([
            'name' => 'Studio Heavy Tee',
            'slug' => 'studio-heavy-tee',
            'primary_category_id' => $category->id,
            'base_price_minor' => 49900,
            'sort_order' => 1,
        ]);
        ProductSku::factory()->create([
            'product_id' => $product->id,
            'price_minor' => 44900,
            'track_stock' => false,
        ]);
        Product::factory()->draft()->create([
            'name' => 'Private Prototype',
            'primary_category_id' => $category->id,
        ]);

        app(SettingsService::class)->set('business', 'company_name', 'Okina Studio');

        $this->get(route('storefront.home'))
            ->assertOk()
            ->assertViewIs('storefront.home')
            ->assertViewHas('cartCount', 0)
            ->assertSee('Your idea.')
            ->assertSee('Ready to wear.')
            ->assertSee('Studio T-Shirts')
            ->assertSee('Studio Heavy Tee')
            ->assertSee('₹449')
            ->assertSee('Okina Studio')
            ->assertDontSee('Private Prototype')
            ->assertSee('application/ld+json', false);
    }

    public function test_collection_routes_render_public_data_and_return_real_not_found_statuses(): void
    {
        $public = ProductCategory::factory()->create([
            'name' => 'Teamwear',
            'slug' => 'teamwear',
            'description' => 'Custom team apparel for match day.',
        ]);
        $draft = ProductCategory::factory()->create([
            'slug' => 'hidden-collection',
            'status' => 'draft',
            'published_at' => now()->addDay(),
        ]);
        Product::factory()->create([
            'name' => 'Active Team Tee',
            'slug' => 'active-team-tee',
            'primary_category_id' => $public->id,
        ]);

        $this->get(route('storefront.categories.index'))
            ->assertOk()
            ->assertViewIs('storefront.categories.index')
            ->assertSee('Teamwear')
            ->assertDontSee($draft->name);

        $this->get(route('storefront.categories.show', ['category' => 'teamwear']))
            ->assertOk()
            ->assertViewIs('storefront.categories.show')
            ->assertSee('Custom team apparel for match day.')
            ->assertSee('Active Team Tee')
            ->assertSee('CollectionPage');

        $this->get(route('storefront.categories.show', ['category' => 'hidden-collection']))
            ->assertNotFound();
    }

    public function test_search_is_server_rendered_noindex_and_escapes_catalog_content(): void
    {
        $category = ProductCategory::factory()->create([
            'name' => 'T-Shirts',
            'slug' => 't-shirts',
        ]);
        Product::factory()->create([
            'name' => 'Studio Tee <script>alert(1)</script>',
            'slug' => 'studio-tee',
            'primary_category_id' => $category->id,
        ]);
        Product::factory()->create([
            'name' => 'Event Hoodie',
            'slug' => 'event-hoodie',
            'primary_category_id' => $category->id,
        ]);

        $this->get(route('storefront.search', ['q' => 'studio']))
            ->assertOk()
            ->assertViewIs('storefront.search')
            ->assertSee('Results for “studio”')
            ->assertSee('Studio Tee')
            ->assertDontSee('Event Hoodie')
            ->assertSee('<meta name="robots" content="noindex, follow">', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_product_page_renders_design_five_customizer_with_real_options(): void
    {
        [$product] = $this->customizableProduct();

        $this->get(route('storefront.products.show', ['product' => $product->slug]))
            ->assertOk()
            ->assertViewIs('storefront.products.show')
            ->assertSee('Made for your idea')
            ->assertSee('Choose size')
            ->assertSee('Place your artwork')
            ->assertSee('Medium')
            ->assertSee('₹18.99')
            ->assertSee('Free digital proof')
            ->assertSee('product-customizer-data')
            ->assertSee(route('storefront.products.cart.store', ['product' => $product->slug]), false);

        $this->get(route('storefront.products.show', ['product' => 'not-a-product']))
            ->assertNotFound();
    }

    public function test_product_customizer_adds_a_valid_selection_to_the_existing_cart(): void
    {
        [$product, $sku] = $this->customizableProduct();

        $response = $this->from(route('storefront.products.show', ['product' => $product->slug]))
            ->post(route('storefront.products.cart.store', ['product' => $product->slug]), [
                'sku_code' => $sku->sku_code,
                'quantity' => 5,
                'selected_options' => ['size' => 'm'],
                'print_position' => 'front',
                'print_method' => 'dtf',
                'customer_note' => 'Use the approved red logo.',
            ]);

        $response->assertRedirect(route('storefront.cart'));
        $this->assertDatabaseCount('carts', 1);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'sku_id' => $sku->id,
            'quantity' => 5,
        ]);

        $item = CartItem::query()->firstOrFail();
        $this->assertSame('front', data_get($item->customization_snapshot, 'print_position'));
        $this->assertSame('dtf', data_get($item->customization_snapshot, 'print_method'));
        $this->assertSame('m', data_get($item->customization_snapshot, 'selected_options_snapshot.0.value_code'));
        $this->assertSame('Use the approved red logo.', data_get($item->customization_snapshot, 'customer_note'));
    }

    public function test_product_customizer_rejects_incompatible_print_choices_and_preserves_input(): void
    {
        [$product, $sku] = $this->customizableProduct();

        $this->from(route('storefront.products.show', ['product' => $product->slug]))
            ->post(route('storefront.products.cart.store', ['product' => $product->slug]), [
                'sku_code' => $sku->sku_code,
                'quantity' => 2,
                'selected_options' => ['size' => 'm'],
                'print_position' => 'back',
                'print_method' => 'embroidery',
                'customer_note' => 'Keep this note.',
            ])
            ->assertRedirect(route('storefront.products.show', ['product' => $product->slug]))
            ->assertSessionHasErrors('customization')
            ->assertSessionHasInput('customer_note', 'Keep this note.');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_product_customizer_disables_purchase_when_catalog_stock_is_unavailable(): void
    {
        [$product, $sku] = $this->customizableProduct();
        $sku->update([
            'track_stock' => true,
            'stock_quantity' => 0,
            'allow_backorder' => false,
        ]);

        $this->get(route('storefront.products.show', ['product' => $product->slug]))
            ->assertOk()
            ->assertSee('This product currently requires an enquiry.')
            ->assertSee('disabled data-add-button', false);
    }

    /** @return array{0: Product, 1: ProductSku} */
    private function customizableProduct(): array
    {
        $category = ProductCategory::factory()->create([
            'name' => 'Custom Tees',
            'slug' => 'custom-tees',
        ]);
        $product = Product::factory()->create([
            'name' => 'Proof Ready Tee',
            'slug' => 'proof-ready-tee',
            'primary_category_id' => $category->id,
            'customization_mode' => Product::CUSTOMIZATION_REQUIRED,
            'base_price_minor' => 1999,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size',
            'code' => 'size',
            'display_type' => 'button',
            'values' => [
                ['code' => 'm', 'label' => 'Medium', 'sort_order' => 10, 'is_active' => true],
            ],
            'is_required' => true,
        ]);
        $sku = ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-PROOF-TEE-M',
            'variant_key' => 'size:m',
            'option_values' => [
                ['code' => 'm', 'label' => 'Medium'],
            ],
            'track_stock' => false,
            'price_minor' => 1899,
        ]);

        return [$product, $sku];
    }
}
