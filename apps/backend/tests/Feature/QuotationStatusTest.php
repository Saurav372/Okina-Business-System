<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationStatusTest extends TestCase
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

    public function test_unauthenticated_guest_cannot_update_status(): void
    {
        $quotation = Quotation::factory()->create(['status' => 'draft']);

        $response = $this->patchJson(route('admin.quotations.status.update', $quotation->public_id), [
            'status' => 'sent',
        ]);
        $response->assertStatus(401);
    }

    public function test_unauthorized_staff_cannot_update_status(): void
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

        $quotation = Quotation::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($user)->patchJson(route('admin.quotations.status.update', $quotation->public_id), [
            'status' => 'sent',
        ]);
        $response->assertStatus(403);
    }

    public function test_same_status_transition_is_rejected(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $quotation = Quotation::factory()->create(['status' => 'sent']);

        $response = $this->actingAs($user)->patchJson(route('admin.quotations.status.update', $quotation->public_id), [
            'status' => 'sent',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_converted_quotation_cannot_be_modified(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $quotation = Quotation::factory()->create(['status' => 'converted']);

        $response = $this->actingAs($user)->patchJson(route('admin.quotations.status.update', $quotation->public_id), [
            'status' => 'sent',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_cancelled_quotation_cannot_be_modified(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $quotation = Quotation::factory()->create(['status' => 'cancelled']);

        $response = $this->actingAs($user)->patchJson(route('admin.quotations.status.update', $quotation->public_id), [
            'status' => 'draft',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_valid_transitions_and_timestamp_updates(): void
    {
        $user = $this->createAuthorizedStaffUser();

        // 1. draft -> sent
        $quotation = Quotation::factory()->create(['status' => 'draft']);
        $this->assertNull($quotation->sent_at);

        $response = $this->actingAs($user)->patchJson(route('admin.quotations.status.update', $quotation->public_id), [
            'status' => 'sent',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'quotation' => [
                'public_id',
                'status',
                'sent_at',
                'approved_at',
                'rejected_at',
                'expired_at',
                'converted_at',
                'revised_at',
                'updated_at',
            ],
        ]);
        $response->assertJsonPath('quotation.status', 'sent');
        $this->assertNotNull($response->json('quotation.sent_at'));

        $quotation->refresh();
        $this->assertSame('sent', $quotation->status);
        $this->assertNotNull($quotation->sent_at);

        // 2. sent -> approved
        $response2 = $this->actingAs($user)->patchJson(route('admin.quotations.status.update', $quotation->public_id), [
            'status' => 'approved',
        ]);
        $response2->assertStatus(200);
        $response2->assertJsonPath('quotation.status', 'approved');
        $this->assertNotNull($response2->json('quotation.approved_at'));

        $quotation->refresh();
        $this->assertSame('approved', $quotation->status);
        $this->assertNotNull($quotation->approved_at);
        $this->assertSame($user->id, $quotation->approved_by_user_id);

        // 3. approved -> cancelled
        $response3 = $this->actingAs($user)->patchJson(route('admin.quotations.status.update', $quotation->public_id), [
            'status' => 'cancelled',
        ]);
        $response3->assertStatus(200);
        $response3->assertJsonPath('quotation.status', 'cancelled');

        // 4. sent -> rejected
        $quotationSent = Quotation::factory()->create(['status' => 'sent']);
        $response4 = $this->actingAs($user)->patchJson(route('admin.quotations.status.update', $quotationSent->public_id), [
            'status' => 'rejected',
        ]);
        $response4->assertStatus(200);
        $response4->assertJsonPath('quotation.status', 'rejected');
        $this->assertNotNull($response4->json('quotation.rejected_at'));

        // 5. rejected -> revision_requested
        $response5 = $this->actingAs($user)->patchJson(route('admin.quotations.status.update', $quotationSent->public_id), [
            'status' => 'revision_requested',
        ]);
        $response5->assertStatus(200);

        // 6. revision_requested -> revised
        $response6 = $this->actingAs($user)->patchJson(route('admin.quotations.status.update', $quotationSent->public_id), [
            'status' => 'revised',
        ]);
        $response6->assertStatus(200);
        $this->assertNotNull($response6->json('quotation.revised_at'));

        // 7. revised -> sent
        $response7 = $this->actingAs($user)->patchJson(route('admin.quotations.status.update', $quotationSent->public_id), [
            'status' => 'sent',
        ]);
        $response7->assertStatus(200);

        // 8. sent -> expired -> revised
        $quotationExpired = Quotation::factory()->create(['status' => 'sent']);
        $response8 = $this->actingAs($user)->patchJson(route('admin.quotations.status.update', $quotationExpired->public_id), [
            'status' => 'expired',
        ]);
        $response8->assertStatus(200);
        $this->assertNotNull($response8->json('quotation.expired_at'));

        $response9 = $this->actingAs($user)->patchJson(route('admin.quotations.status.update', $quotationExpired->public_id), [
            'status' => 'revised',
        ]);
        $response9->assertStatus(200);
    }
}
