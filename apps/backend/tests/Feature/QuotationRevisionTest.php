<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Quotation;
use App\Models\QuotationRevision;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationRevisionTest extends TestCase
{
    use RefreshDatabase;

    protected function createAuthorizedStaffUser(): User
    {
        Permission::query()->updateOrCreate(
            ['slug' => 'quotations.manage'],
            [
                'name' => 'Manage Quotations',
                'group' => 'quotations',
                'guard_name' => 'web',
                'description' => 'Manage quotations',
                'is_sensitive' => false,
            ]
        );

        $role = Role::query()->updateOrCreate(
            ['slug' => 'quotation_manager'],
            [
                'name' => 'Quotation Manager',
                'guard_name' => 'web',
                'description' => 'Can manage quotations',
                'is_system' => true,
                'sort_order' => 0,
            ]
        );

        $permissionIds = Permission::query()->whereIn('slug', ['quotations.manage'])->pluck('id')->all();
        $role->permissions()->sync($permissionIds);

        $dashboardRole = Role::query()->updateOrCreate(
            ['slug' => Role::SALES_STAFF],
            [
                'name' => 'Sales Staff',
                'guard_name' => 'web',
                'description' => 'Sales staff role',
                'is_system' => true,
                'sort_order' => 0,
            ]
        );

        $user = User::factory()->create();
        $user->assignRole($role);
        $user->assignRole($dashboardRole);

        return $user;
    }

    protected function createUnauthorizedStaffUser(): User
    {
        $dashboardRole = Role::query()->updateOrCreate(
            ['slug' => Role::SALES_STAFF],
            [
                'name' => 'Sales Staff',
                'guard_name' => 'web',
                'description' => 'Sales staff role',
                'is_system' => true,
                'sort_order' => 0,
            ]
        );

        $user = User::factory()->create();
        $user->assignRole($dashboardRole);

        return $user;
    }

    public function test_revision_snapshot_preserves_previous_terms(): void
    {
        $user = $this->createAuthorizedStaffUser();

        // Create a quotation with status 'revision_requested' (represents revision 1 terms)
        $quotation = Quotation::factory()->create([
            'status' => Quotation::STATUS_REVISION_REQUESTED,
            'current_revision_number' => 1,
            'quotation_type' => Quotation::TYPE_MANUAL,
            'valid_until' => '2026-12-31',
            'customer_note' => 'Old note about shipping terms.',
            'subtotal_amount_minor' => 5000,
            'discount_amount_minor' => 500,
            'shipping_amount_minor' => 100,
            'tax_amount_minor' => 810,
            'total_amount_minor' => 5410,
            'currency' => 'INR',
            'customer_snapshot' => [
                'contact_name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'approval_token' => 'SECRET_TOKEN_DO_NOT_SNAPSHOT',
            ],
        ]);

        // Add line items
        $item = $quotation->items()->create([
            'product_id_snapshot' => 10,
            'product_name_snapshot' => 'Custom T-Shirt',
            'sku_code_snapshot' => 'TSHIRT-CUST',
            'item_name' => 'Custom Printed Blue T-Shirt',
            'quantity' => 5,
            'unit_price_minor' => 1000,
            'line_subtotal_minor' => 5000,
            'line_total_minor' => 5000,
            'currency' => 'INR',
            'sort_order' => 0,
        ]);

        // Transition to 'revised' (archiving revision 1)
        $response = $this->actingAs($user)->patchJson(route('admin.quotations.status.update', $quotation->public_id), [
            'status' => Quotation::STATUS_REVISED,
            'note' => 'Applying 10% customer discount as requested.',
        ]);

        $response->assertStatus(200);

        // Assert quotation counter is now incremented to 2
        $quotation->refresh();
        $this->assertSame(Quotation::STATUS_REVISED, $quotation->status);
        $this->assertSame(2, $quotation->current_revision_number);
        $this->assertNotNull($quotation->revised_at);

        // Assert an archived revision record is stored for revision 1
        $revision = QuotationRevision::query()->where('quotation_id', $quotation->id)->first();
        $this->assertNotNull($revision);
        $this->assertSame(1, $revision->revision_number);
        $this->assertSame(Quotation::STATUS_REVISION_REQUESTED, $revision->previous_status);
        $this->assertSame(Quotation::TYPE_MANUAL, $revision->quotation_type);
        $this->assertSame('2026-12-31', $revision->valid_until->toDateString());
        $this->assertSame('Old note about shipping terms.', $revision->customer_note);
        $this->assertSame(5000, $revision->subtotal_amount_minor);
        $this->assertSame(500, $revision->discount_amount_minor);
        $this->assertSame(100, $revision->shipping_amount_minor);
        $this->assertSame(810, $revision->tax_amount_minor);
        $this->assertSame(5410, $revision->total_amount_minor);
        $this->assertSame('INR', $revision->currency);
        $this->assertSame('Applying 10% customer discount as requested.', $revision->reason);
        $this->assertSame($user->id, $revision->created_by_user_id);

        // Verify items snapshot contents
        $itemsSnapshot = $revision->items_snapshot;
        $this->assertCount(1, $itemsSnapshot);
        $this->assertSame('Custom Printed Blue T-Shirt', $itemsSnapshot[0]['item_name']);
        $this->assertSame(5, $itemsSnapshot[0]['quantity']);
        $this->assertSame(1000, $itemsSnapshot[0]['unit_price_minor']);

        // Verify customer snapshot does NOT leak approval_token
        $custSnapshot = $revision->customer_snapshot;
        $this->assertSame('Jane Smith', $custSnapshot['contact_name']);
        $this->assertSame('jane@example.com', $custSnapshot['email']);
        $this->assertArrayNotHasKey('approval_token', $custSnapshot);
    }

    public function test_revision_reason_is_stored(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $quotation = Quotation::factory()->create([
            'status' => Quotation::STATUS_REVISION_REQUESTED,
            'current_revision_number' => 1,
        ]);

        $this->actingAs($user)->patchJson(route('admin.quotations.status.update', $quotation->public_id), [
            'status' => Quotation::STATUS_REVISED,
            'note' => 'Corrected a typo in pricing terms.',
        ])->assertStatus(200);

        $revision = QuotationRevision::query()->where('quotation_id', $quotation->id)->first();
        $this->assertSame('Corrected a typo in pricing terms.', $revision->reason);
    }

    public function test_revision_number_increments_sequentially(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $quotation = Quotation::factory()->create([
            'status' => Quotation::STATUS_REVISION_REQUESTED,
            'current_revision_number' => 1,
        ]);

        $this->actingAs($user)->patchJson(route('admin.quotations.status.update', $quotation->public_id), [
            'status' => Quotation::STATUS_REVISED,
            'note' => 'Revision 1 done.',
        ])->assertStatus(200);

        $quotation->refresh();
        $this->assertSame(2, $quotation->current_revision_number);
    }

    public function test_multiple_revisions_create_unique_revision_numbers(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $quotation = Quotation::factory()->create([
            'status' => Quotation::STATUS_REVISION_REQUESTED,
            'current_revision_number' => 1,
        ]);

        // First revision transition (revision 1 archived, counter becomes 2)
        $this->actingAs($user)->patchJson(route('admin.quotations.status.update', $quotation->public_id), [
            'status' => Quotation::STATUS_REVISED,
            'note' => 'Archive first revision.',
        ])->assertStatus(200);

        // Move to sent (counter is 2)
        $this->actingAs($user)->patchJson(route('admin.quotations.status.update', $quotation->public_id), [
            'status' => Quotation::STATUS_SENT,
        ])->assertStatus(200);

        // Request changes again (counter is 2)
        $this->actingAs($user)->patchJson(route('admin.quotations.status.update', $quotation->public_id), [
            'status' => Quotation::STATUS_REVISION_REQUESTED,
        ])->assertStatus(200);

        // Second revision transition (revision 2 archived, counter becomes 3)
        $this->actingAs($user)->patchJson(route('admin.quotations.status.update', $quotation->public_id), [
            'status' => Quotation::STATUS_REVISED,
            'note' => 'Archive second revision.',
        ])->assertStatus(200);

        $quotation->refresh();
        $this->assertSame(3, $quotation->current_revision_number);

        // Verify we have 2 archived revision rows
        $revisions = QuotationRevision::query()->where('quotation_id', $quotation->id)->orderBy('revision_number')->get();
        $this->assertCount(2, $revisions);

        $this->assertSame(1, $revisions[0]->revision_number);
        $this->assertSame('Archive first revision.', $revisions[0]->reason);

        $this->assertSame(2, $revisions[1]->revision_number);
        $this->assertSame('Archive second revision.', $revisions[1]->reason);
    }

    public function test_concurrency_and_unique_constraint(): void
    {
        $quotation = Quotation::factory()->create([
            'status' => Quotation::STATUS_REVISION_REQUESTED,
            'current_revision_number' => 1,
        ]);

        // Attempting to manually create duplicate revisions for the same number fails
        $this->expectException(UniqueConstraintViolationException::class);

        QuotationRevision::create([
            'quotation_id' => $quotation->id,
            'revision_number' => 1,
            'previous_status' => Quotation::STATUS_REVISION_REQUESTED,
            'quotation_type' => Quotation::TYPE_MANUAL,
            'subtotal_amount_minor' => 1000,
            'discount_amount_minor' => 0,
            'shipping_amount_minor' => 0,
            'tax_amount_minor' => 0,
            'total_amount_minor' => 1000,
            'items_snapshot' => [],
        ]);

        QuotationRevision::create([
            'quotation_id' => $quotation->id,
            'revision_number' => 1,
            'previous_status' => Quotation::STATUS_REVISION_REQUESTED,
            'quotation_type' => Quotation::TYPE_MANUAL,
            'subtotal_amount_minor' => 1000,
            'discount_amount_minor' => 0,
            'shipping_amount_minor' => 0,
            'tax_amount_minor' => 0,
            'total_amount_minor' => 1000,
            'items_snapshot' => [],
        ]);
    }

    public function test_unauthorized_staff_cannot_revise_quotation(): void
    {
        $unauthorizedStaff = $this->createUnauthorizedStaffUser();
        $quotation = Quotation::factory()->create([
            'status' => Quotation::STATUS_REVISION_REQUESTED,
            'current_revision_number' => 1,
        ]);

        $response = $this->actingAs($unauthorizedStaff)->patchJson(route('admin.quotations.status.update', $quotation->public_id), [
            'status' => Quotation::STATUS_REVISED,
            'note' => 'Attemping unauthorized revision.',
        ]);

        $response->assertStatus(403);
    }
}
