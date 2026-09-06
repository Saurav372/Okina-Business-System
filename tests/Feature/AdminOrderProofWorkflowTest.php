<?php

namespace Tests\Feature;

use App\Models\CustomerAccount;
use App\Models\Order;
use App\Models\OrderMockup;
use App\Models\Permission;
use App\Models\Role;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminOrderProofWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_upload_a_customer_visible_proof_to_an_order(): void
    {
        Storage::fake('private');

        $account = CustomerAccount::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $account->customer_id,
            'design_status' => 'approved',
            'design_approved' => true,
        ]);
        $staff = $this->staffWithPermissions(['orders.manage', 'orders.view', 'files.download_private']);

        $this->actingAs($staff)
            ->post(route('admin.orders.proofs.store', ['order' => $order->public_id]), [
                'proof_file' => UploadedFile::fake()->image('front-proof.png', 800, 800),
                'display_name' => 'Front print proof v1',
                'notes' => 'Please check the logo size and placement.',
                'is_featured' => '1',
            ])
            ->assertRedirect(route('admin.orders.show', ['order' => $order->public_id]))
            ->assertSessionHas('success');

        $proof = StoredFile::query()->where('file_kind', StoredFile::KIND_PROOF)->firstOrFail();

        $this->assertSame($account->customer_id, $proof->customer_id);
        $this->assertSame(StoredFile::VISIBILITY_CUSTOMER_VISIBLE, $proof->visibility);
        Storage::disk('private')->assertExists($proof->storage_path);

        $this->assertDatabaseHas('order_mockups', [
            'order_id' => $order->id,
            'stored_file_id' => $proof->id,
            'display_name' => 'Front print proof v1',
            'is_featured' => 1,
        ]);

        $this->assertSame('under_review', $order->refresh()->design_status);
        $this->assertFalse($order->design_approved);

        $this->actingAs($staff)
            ->get(route('admin.orders.show', ['order' => $order->public_id]))
            ->assertOk()
            ->assertSee('Artwork & Proofs', false)
            ->assertSee('Front print proof v1')
            ->assertSee('Upload & share with customer', false);
    }

    public function test_customer_order_detail_returns_only_customer_visible_proofs(): void
    {
        $account = CustomerAccount::factory()->create();
        $order = Order::factory()->create(['customer_id' => $account->customer_id]);

        $visibleProof = StoredFile::factory()->create([
            'customer_id' => $account->customer_id,
            'file_kind' => StoredFile::KIND_PROOF,
            'visibility' => StoredFile::VISIBILITY_CUSTOMER_VISIBLE,
            'status' => StoredFile::STATUS_ACTIVE,
            'mime_type' => 'image/png',
            'extension' => 'png',
        ]);
        $privateProof = StoredFile::factory()->create([
            'customer_id' => $account->customer_id,
            'file_kind' => StoredFile::KIND_PROOF,
            'visibility' => StoredFile::VISIBILITY_STAFF_ONLY,
            'status' => StoredFile::STATUS_ACTIVE,
        ]);

        OrderMockup::query()->create([
            'order_id' => $order->id,
            'stored_file_id' => $visibleProof->id,
            'display_name' => 'Customer proof',
            'is_featured' => true,
            'sort_order' => 1,
        ]);
        OrderMockup::query()->create([
            'order_id' => $order->id,
            'stored_file_id' => $privateProof->id,
            'display_name' => 'Internal working file',
            'is_featured' => false,
            'sort_order' => 2,
        ]);

        $this->actingAs($account, 'customer')
            ->getJson('/api/customer/orders/'.$order->public_id)
            ->assertOk()
            ->assertJsonCount(1, 'data.proofs')
            ->assertJsonPath('data.proofs.0.display_name', 'Customer proof')
            ->assertJsonPath('data.proofs.0.is_featured', true)
            ->assertJsonPath('data.proofs.0.mime_type', 'image/png')
            ->assertJsonPath('data.proofs.0.preview_url', fn (string $url): bool => str_contains($url, '/files/'.$visibleProof->public_id.'/preview'))
            ->assertJsonMissing(['Internal working file']);
    }

    public function test_read_only_staff_cannot_upload_a_proof(): void
    {
        Storage::fake('private');

        $order = Order::factory()->create();
        $staff = $this->staffWithPermissions(['orders.view']);

        $this->actingAs($staff)
            ->post(route('admin.orders.proofs.store', ['order' => $order->public_id]), [
                'proof_file' => UploadedFile::fake()->image('proof.png'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('order_mockups', 0);
    }

    private function staffWithPermissions(array $slugs): User
    {
        foreach ($slugs as $slug) {
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
            ['slug' => Role::ADMIN],
            [
                'name' => 'Admin',
                'guard_name' => 'web',
                'description' => 'Admin',
                'is_system' => true,
                'sort_order' => 1,
            ],
        );

        $role->permissions()->syncWithoutDetaching(
            Permission::query()->whereIn('slug', $slugs)->pluck('id')->all()
        );

        $user = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $user->assignRole($role);

        return $user;
    }
}
