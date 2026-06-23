<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Quotation;
use App\Models\QuotationApprovalEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationApprovalTest extends TestCase
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

    public function test_approval_requires_valid_token(): void
    {
        $quotation = Quotation::factory()->create(['status' => 'sent']);

        $response = $this->postJson(route('api.catalog.quotations.approve', $quotation->public_id), [
            'token' => 'invalid-token',
        ]);

        $response->assertStatus(403);
    }

    public function test_reject_requires_valid_token(): void
    {
        $quotation = Quotation::factory()->create(['status' => 'sent']);

        $response = $this->postJson(route('api.catalog.quotations.reject', $quotation->public_id), [
            'token' => 'invalid-token',
        ]);

        $response->assertStatus(403);
    }

    public function test_customer_can_approve_sent_quotation(): void
    {
        $quotation = Quotation::factory()->create(['status' => 'sent']);
        $this->assertNull($quotation->approved_at);

        $response = $this->postJson(route('api.catalog.quotations.approve', $quotation->public_id), [
            'token' => $quotation->approval_token,
            'note' => 'Looks perfect, let us go ahead!',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('quotation.status', 'approved');

        $quotation->refresh();
        $this->assertSame('approved', $quotation->status);
        $this->assertNotNull($quotation->approved_at);

        // Verify the QuotationApprovalEvent row was logged correctly
        $event = QuotationApprovalEvent::query()->where('quotation_id', $quotation->id)->first();
        $this->assertNotNull($event);
        $this->assertSame('approved', $event->event_type);
        $this->assertSame('customer', $event->actor_type);
        $this->assertSame('Looks perfect, let us go ahead!', $event->note);
    }

    public function test_customer_can_reject_sent_quotation(): void
    {
        $quotation = Quotation::factory()->create(['status' => 'sent']);
        $this->assertNull($quotation->rejected_at);

        $response = $this->postJson(route('api.catalog.quotations.reject', $quotation->public_id), [
            'token' => $quotation->approval_token,
            'note' => 'Price is too high.',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('quotation.status', 'rejected');

        $quotation->refresh();
        $this->assertSame('rejected', $quotation->status);
        $this->assertNotNull($quotation->rejected_at);

        $event = QuotationApprovalEvent::query()->where('quotation_id', $quotation->id)->first();
        $this->assertNotNull($event);
        $this->assertSame('rejected', $event->event_type);
        $this->assertSame('customer', $event->actor_type);
        $this->assertSame('Price is too high.', $event->note);
    }

    public function test_cannot_approve_non_sent_quotation(): void
    {
        $quotation = Quotation::factory()->create(['status' => 'draft']);

        $response = $this->postJson(route('api.catalog.quotations.approve', $quotation->public_id), [
            'token' => $quotation->approval_token,
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_reject_non_sent_quotation(): void
    {
        $quotation = Quotation::factory()->create(['status' => 'approved']);

        $response = $this->postJson(route('api.catalog.quotations.reject', $quotation->public_id), [
            'token' => $quotation->approval_token,
        ]);

        $response->assertStatus(422);
    }

    public function test_duplicate_approval_request_does_not_create_second_event(): void
    {
        $quotation = Quotation::factory()->create(['status' => 'sent']);

        $payload = [
            'token' => $quotation->approval_token,
            'idempotency_key' => 'idemp-key-123',
            'note' => 'Approved!',
        ];

        // First attempt
        $response1 = $this->postJson(route('api.catalog.quotations.approve', $quotation->public_id), $payload);
        $response1->assertStatus(200);

        // Second attempt
        $response2 = $this->postJson(route('api.catalog.quotations.approve', $quotation->public_id), $payload);
        $response2->assertStatus(200);
        $response2->assertJsonPath('message', 'Quotation action processed (idempotent).');

        $this->assertSame(1, QuotationApprovalEvent::where('quotation_id', $quotation->id)->count());
    }

    public function test_duplicate_rejection_request_does_not_create_second_event(): void
    {
        $quotation = Quotation::factory()->create(['status' => 'sent']);

        $payload = [
            'token' => $quotation->approval_token,
            'idempotency_key' => 'idemp-key-abc',
            'note' => 'Rejected!',
        ];

        // First attempt
        $response1 = $this->postJson(route('api.catalog.quotations.reject', $quotation->public_id), $payload);
        $response1->assertStatus(200);

        // Second attempt
        $response2 = $this->postJson(route('api.catalog.quotations.reject', $quotation->public_id), $payload);
        $response2->assertStatus(200);
        $response2->assertJsonPath('message', 'Quotation action processed (idempotent).');

        $this->assertSame(1, QuotationApprovalEvent::where('quotation_id', $quotation->id)->count());
    }

    public function test_staff_status_change_logs_approval_event(): void
    {
        $user = $this->createAuthorizedStaffUser();
        $quotation = Quotation::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($user)->patchJson(route('admin.quotations.status.update', $quotation->public_id), [
            'status' => 'sent',
            'note' => 'Sending quote to client.',
        ]);

        $response->assertStatus(200);

        $event = QuotationApprovalEvent::query()->where('quotation_id', $quotation->id)->first();
        $this->assertNotNull($event);
        $this->assertSame('sent', $event->event_type);
        $this->assertSame('staff', $event->actor_type);
        $this->assertSame($user->id, $event->actor_user_id);
        $this->assertSame('Sending quote to client.', $event->note);
    }

    public function test_non_approved_quote_cannot_be_converted(): void
    {
        $quotation = Quotation::factory()->create(['status' => 'sent']);
        $this->assertFalse($quotation->canTransitionTo('converted'));

        $quotationApproved = Quotation::factory()->create(['status' => 'approved']);
        $this->assertTrue($quotationApproved->canTransitionTo('converted'));
    }
}
