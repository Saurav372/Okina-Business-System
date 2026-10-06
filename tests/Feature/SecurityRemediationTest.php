<?php

namespace Tests\Feature;

use App\Models\CustomerAccount;
use App\Models\ExpenseCategory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\StoredFile;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorOrderItem;
use App\Notifications\StaffInvitationNotification;
use App\Services\FileUploadService;
use App\Services\ProtectedMockupService;
use App\Support\GoogleSheets\GoogleSheetsClient;
use App\Support\Security\CsvCell;
use App\Support\Security\ImagePixelBudget;
use Database\Seeders\AccessControlSeeder;
use Database\Seeders\DatabaseSeeder;
use Google\Service\Sheets;
use Google\Service\Sheets\Resource\SpreadsheetsValues;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class SecurityRemediationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(AccessControlSeeder::class);
    }

    private function staff(string $role = Role::SUPER_ADMIN): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_privileged_invitation_is_denied_without_rotating_token(): void
    {
        Notification::fake();
        $target = $this->staff();
        $target->forceFill(['status' => User::STATUS_INVITED])->save();
        $target->generateInvitationToken(48);
        $hash = $target->invitation_token_hash;
        $this->actingAs($this->staff(Role::ADMIN))->post(route('admin.staff.resend_invitation', $target))->assertForbidden();
        $this->assertSame($hash, $target->refresh()->invitation_token_hash);
        Notification::assertNothingSent();
        $this->actingAs($this->staff())->post(route('admin.staff.resend_invitation', $target))
            ->assertRedirect()->assertSessionMissing('invitation_url');
        Notification::assertSentTo($target, StaffInvitationNotification::class);
        $this->assertNotSame($hash, $target->refresh()->invitation_token_hash);
    }

    public function test_default_seed_has_no_privileged_factory_account_and_explicit_provisioning_works(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('users', 0);
        $this->artisan('admin:create', ['email' => 'owner@example.test', '--name' => 'Owner'])
            ->expectsQuestion('Password (at least 12 characters, letters and numbers)', 'UniquePass12345')
            ->expectsQuestion('Confirm password', 'UniquePass12345')->assertSuccessful();
        $owner = User::firstOrFail();
        $this->assertTrue($owner->hasRole(Role::SUPER_ADMIN));
        $this->assertTrue(Hash::check('UniquePass12345', $owner->password));
    }

    public function test_reset_rejects_old_customer_session_without_revoking_staff_identity(): void
    {
        $account = CustomerAccount::factory()->create(['password' => Hash::make('Original123')]);
        $staff = $this->staff();
        Auth::guard('web')->login($staff);
        $guard = Auth::guard('customer');
        $session = [$guard->getName() => $account->id, Auth::guard('web')->getName() => $staff->id, 'customer_password_fingerprint' => hash('sha256', $account->password)];
        $token = Password::broker('customer_accounts')->createToken($account);
        $this->post(route('customer.password.update'), [
            'token' => $token, 'email' => $account->email,
            'password' => 'Replacement123', 'password_confirmation' => 'Replacement123',
        ])->assertRedirect(route('customer.login'));
        Auth::forgetGuards();
        $this->withSession($session)->getJson('/api/customer/profile')->assertUnauthorized();
        $this->assertAuthenticatedAs($staff, 'web');
        $this->post(route('customer.login.store'), ['email' => $account->email, 'password' => 'Replacement123'])->assertRedirect();
        $this->getJson('/api/customer/profile')->assertOk();
    }

    public function test_legacy_customer_session_requires_fresh_login(): void
    {
        $account = CustomerAccount::factory()->create();
        $this->withSession([Auth::guard('customer')->getName() => $account->id])
            ->getJson('/api/customer/profile')->assertUnauthorized();
    }

    public function test_locked_existing_customer_cannot_checkout_or_upload(): void
    {
        foreach ([['locked_until' => now()->addMinutes(5)], ['status' => 'suspended'], ['status' => 'disabled'], ['email_verified_at' => null]] as $state) {
            $account = CustomerAccount::factory()->create($state);
            $product = Product::factory()->create();
            $source = StoredFile::factory()->create(['customer_id' => $account->customer_id]);
            foreach (['/api/cart/checkout', '/api/cart/checkout/validation',
                '/api/catalog/products/'.$product->slug.'/design-upload',
                '/api/catalog/products/'.$product->slug.'/protected-mockup/'.$source->public_id,
                '/api/catalog/products/'.$product->slug.'/design-preview/'.$source->public_id.'/link'] as $path) {
                $this->actingAs($account->fresh(), 'customer')->postJson($path, [])->assertForbidden();
            }
        }
    }

    public function test_purchase_items_reject_mismatched_parent_and_ignore_protected_fields(): void
    {
        $this->actingAs($this->staff());
        $vendor = Vendor::create(['name' => 'Supplier', 'vendor_code' => 'VND-SECURITY', 'status' => 'active']);
        $parent = VendorOrder::create(['public_id' => 'PO-SECURE1', 'vendor_id' => $vendor->id, 'status' => 'draft', 'payment_status' => 'unpaid', 'currency' => 'INR']);
        $other = VendorOrder::create(['public_id' => 'PO-SECURE2', 'vendor_id' => $vendor->id, 'status' => 'ordered', 'payment_status' => 'unpaid', 'currency' => 'INR']);
        $sku = ProductSku::factory()->create();
        $item = VendorOrderItem::create(['vendor_order_id' => $other->id, 'product_sku_id' => $sku->id, 'quantity_ordered' => 5, 'quantity_received' => 0, 'unit_cost_minor' => 100, 'currency' => 'INR']);
        $this->putJson(route('admin.purchase_orders.items.update', [$parent, $item]), ['quantity_ordered' => 1])->assertNotFound();
        $this->deleteJson(route('admin.purchase_orders.items.destroy', [$parent, $item]))->assertNotFound();
        $item->forceFill(['vendor_order_id' => $parent->id])->save();
        $this->putJson(route('admin.purchase_orders.items.update', [$parent, $item]), [
            'quantity_ordered' => 4, 'quantity_received' => 4, 'vendor_order_id' => $other->id,
            'product_sku_id' => ProductSku::factory()->create()->id,
        ])->assertOk();
        $this->assertSame(4, $item->refresh()->quantity_ordered);
        $this->assertSame(0, $item->quantity_received);
        $this->assertSame($parent->id, $item->vendor_order_id);
        $this->assertSame($sku->id, $item->product_sku_id);
    }

    public function test_large_compressed_image_is_rejected_before_storage(): void
    {
        Storage::fake('private');
        $image = imagecreatetruecolor(3000, 3000);
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);
        try {
            app(FileUploadService::class)->store(UploadedFile::fake()->createWithContent('large.png', $bytes), $this->staff());
            $this->fail('Oversized pixel budget was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('file', $e->errors());
        }
        $this->assertDatabaseCount('files', 0);
        $this->assertSame([], Storage::disk('private')->allFiles());
        $source = StoredFile::factory()->create(['storage_disk' => 'private', 'storage_path' => 'legacy/artwork.png', 'metadata' => []]);
        Storage::disk('private')->put($source->storage_path, $bytes);
        try {
            app(ProtectedMockupService::class)->create(Product::factory()->create(), $source, CustomerAccount::factory()->create(), 'ink', 'front', []);
            $this->fail('Oversized legacy artwork reached mockup decoding.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('file', $e->errors());
        }
        $thin = UploadedFile::fake()->image('thin.png', 8001, 1);
        try {
            ImagePixelBudget::validateBytes($thin->getContent());
            $this->fail('Oversized dimension was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('file', $e->errors());
        }
        ImagePixelBudget::validateBytes(UploadedFile::fake()->image('valid.png', 1200, 900)->getContent());
    }

    public function test_sheet_append_and_update_send_literal_text_and_native_numbers(): void
    {
        config(['sheets.spreadsheet_id' => 'isolated-test-sheet']);
        $values = Mockery::mock(SpreadsheetsValues::class);
        foreach (['append', 'update'] as $operation) {
            $values->shouldReceive($operation)->once()->withArgs(function ($id, $range, $body, $options) {
                return $id === 'isolated-test-sheet' && $options['valueInputOption'] === 'RAW'
                    && $body->getValues() === [['=1+1', 42, '2026-10-06T00:00:00Z']];
            });
        }
        $service = Mockery::mock(Sheets::class);
        $service->spreadsheets_values = $values;
        $client = Mockery::mock(GoogleSheetsClient::class)->makePartial();
        $client->shouldReceive('getSheetsService')->andReturn($service);
        $client->appendRow('Customers', ['=1+1', 42, '2026-10-06T00:00:00Z']);
        $client->updateRow('Customers', 2, ['=1+1', 42, '2026-10-06T00:00:00Z']);
    }

    public function test_manifest_neutralizes_customer_text_and_preserves_numbers(): void
    {
        $order = Order::factory()->create(['shipping_address_snapshot' => ['address_line_1' => '=1+1', 'city' => "\t@SUM(1)"]]);
        $response = $this->actingAs($this->staff())->post(route('admin.orders.bulk.manifest'), ['order_ids' => [$order->public_id]])->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=1+1", $csv);
        $this->assertStringContainsString("'\t@SUM(1)", $csv);
        foreach (['=1+1', ' +SUM(1)', "\r=1", "\n@SUM(1)", '-1', '@SUM(1)'] as $value) {
            $this->assertSame("'".$value, CsvCell::literal($value));
        }
        $this->assertSame(42, CsvCell::literal(42));
        $this->assertSame('Normal address', CsvCell::literal('Normal address'));
    }

    public function test_refund_query_is_validated_and_flashed_text_is_inert_javascript(): void
    {
        $this->actingAs($this->staff())->getJson('/admin/refunds?payment_id='.urlencode("'+alert(1)+'"))->assertUnprocessable();
        $payload = "'+alert(1)+'\"</script>";
        $html = $this->withSession(['_old_input' => ['payment_id' => $payload, 'amount_rupees' => $payload]])->get('/admin/refunds')->assertOk()->getContent();
        $this->assertSame(1, preg_match('/<div class="space-y-6" x-data="(.*?)"/s', $html, $match));
        $expression = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $process = new Process(['node', '-e', 'global.alert=()=>{throw Error("script executed")}; const result=Function("return ("+process.argv[1]+")")(); if(result.selectedPaymentId!==process.argv[2]) process.exit(2);', $expression, $payload]);
        $process->mustRun();
        $this->assertSame(0, $process->getExitCode());
    }

    public function test_stored_category_variant_and_sku_text_is_inert_on_actions(): void
    {
        $payload = "'+alert(1)+'\"</script>";
        ExpenseCategory::factory()->create(['name' => $payload]);
        $html = $this->actingAs($this->staff())->get('/admin/expenses')->assertOk()->getContent();
        preg_match_all('/data-confirm="([^"]*)" onsubmit="([^"]*)"/', $html, $forms, PREG_SET_ORDER);
        $this->assertNotEmpty($forms);
        foreach ($forms as $form) {
            $message = html_entity_decode($form[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $handler = html_entity_decode($form[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $process = new Process(['node', '-e', 'global.alert=()=>{throw Error("script executed")}; global.confirm=()=>true; if(!Function(process.argv[1]).call({dataset:{confirm:process.argv[2]}})) process.exit(2);', $handler, $message]);
            $process->mustRun();
            $this->assertSame(0, $process->getExitCode());
        }

        $product = Product::factory()->create();
        ProductVariant::factory()->create([
            'product_id' => $product->id, 'name' => $payload, 'code' => $payload,
            'values' => [['code' => 'one', 'label' => $payload]],
        ]);
        ProductSku::factory()->create(['product_id' => $product->id, 'sku_code' => $payload, 'barcode' => $payload]);
        $html = $this->get(route('admin.products.edit', $product))->assertOk()->getContent();
        preg_match_all('/@click="([^"]*)"/s', $html, $handlers);
        $evaluated = 0;
        foreach ($handlers[1] as $handler) {
            if (! str_contains($handler, 'activeVariant =') && ! str_contains($handler, 'activeSku =')) {
                continue;
            }
            $expression = html_entity_decode($handler, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $process = new Process(['node', '-e', 'global.alert=()=>{throw Error("script executed")}; let activeVariant,activeSku; const $dispatch=()=>{}; eval(process.argv[1]); const result=activeVariant||activeSku; if((result.name||result.sku_code)!==process.argv[2]) process.exit(2);', $expression, $payload]);
            $process->mustRun();
            $evaluated++;
        }
        $this->assertSame(2, $evaluated);
    }

    public function test_failed_invitation_delivery_can_be_retried_without_exposing_token(): void
    {
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('Delivery unavailable'));
        $this->actingAs($this->staff())->post(route('admin.staff.store'), [
            'name' => 'Delivery retry', 'email' => 'retry@example.test', 'roles' => [Role::FINANCE_STAFF],
        ])->assertStatus(500)->assertSessionMissing('invitation_url');
        $target = User::where('email', 'retry@example.test')->firstOrFail();
        $this->assertSame(User::STATUS_INVITED, $target->status);
        Notification::fake();
        $this->post(route('admin.staff.resend_invitation', $target))->assertRedirect()->assertSessionMissing('invitation_url');
        Notification::assertSentTo($target, StaffInvitationNotification::class);
    }
}
