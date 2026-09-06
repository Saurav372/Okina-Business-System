<?php

namespace Tests\Feature;

use App\Enums\OrderType;
use App\Support\Orders\OrderTypeCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTypeContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_order_types_expose_the_two_approved_values(): void
    {
        $this->assertSame(['website_order', 'sales_order'], OrderType::values());
        $this->assertTrue(OrderType::WebsiteOrder->isWebsiteOrder());
        $this->assertFalse(OrderType::WebsiteOrder->isSalesOrder());
        $this->assertTrue(OrderType::SalesOrder->isSalesOrder());
        $this->assertFalse(OrderType::SalesOrder->isWebsiteOrder());
    }

    public function test_shared_order_types_provide_labels_and_array_shape(): void
    {
        $this->assertSame('Website Order', OrderType::WebsiteOrder->label());
        $this->assertSame('Sales Order', OrderType::SalesOrder->label());

        $this->assertSame(
            [
                'value' => 'website_order',
                'label' => 'Website Order',
                'is_website_order' => true,
                'is_sales_order' => false,
            ],
            OrderType::WebsiteOrder->toArray(),
        );
    }

    public function test_order_type_catalog_separates_website_and_sales_usage_rules(): void
    {
        $catalog = app(OrderTypeCatalog::class);

        $this->assertSame(['website_order', 'sales_order'], $catalog->keys());

        $website = $catalog->definition(OrderType::WebsiteOrder);
        $sales = $catalog->definition('sales_order');

        $this->assertSame('Website Order', $website['label']);
        $this->assertSame('Customer checkout creates website orders before payment starts.', $website['usage']);
        $this->assertSame(['website', 'checkout'], $website['channels']);
        $this->assertSame(['A5.1.5', 'B3.1.6', 'B3.1.8'], $website['references']);

        $this->assertSame('Sales Order', $sales['label']);
        $this->assertSame('Staff creates sales orders from admin workflows and may manage advance or final payment later.', $sales['usage']);
        $this->assertSame(['admin', 'sales'], $sales['channels']);
        $this->assertSame(['A5.1.6', 'C1.2.6', 'C1.2.8'], $sales['references']);
    }
}
