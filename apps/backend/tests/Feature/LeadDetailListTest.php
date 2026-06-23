<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadDetailListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to set up a staff user with a specific permission.
     */
    protected function createStaffUserWithPermission(string $permissionSlug): User
    {
        Permission::query()->updateOrCreate(
            ['slug' => $permissionSlug],
            [
                'name' => "Permission: {$permissionSlug}",
                'group' => 'leads',
                'guard_name' => 'web',
                'description' => $permissionSlug,
                'is_sensitive' => false,
            ]
        );

        $role = Role::query()->updateOrCreate(
            ['slug' => "role_{$permissionSlug}"],
            [
                'name' => "Role {$permissionSlug}",
                'guard_name' => 'web',
                'description' => "Role for {$permissionSlug}",
                'is_system' => true,
                'sort_order' => 0,
            ]
        );

        $permissionIds = Permission::query()->whereIn('slug', [$permissionSlug])->pluck('id')->all();
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

    /**
     * Helper to set up an unauthorized staff user (has dashboard access, lacks lead permissions).
     */
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

    /**
     * Guest access is blocked.
     */
    public function test_guest_cannot_access_listing_or_detail(): void
    {
        $lead = Lead::factory()->create();

        $this->getJson(route('admin.leads.index'))->assertStatus(401);
        $this->getJson(route('admin.leads.show', $lead))->assertStatus(401);
    }

    /**
     * Unauthorized staff is blocked.
     */
    public function test_unauthorized_staff_cannot_access_listing_or_detail(): void
    {
        $lead = Lead::factory()->create();
        $staff = $this->createUnauthorizedStaffUser();

        $this->actingAs($staff)->getJson(route('admin.leads.index'))->assertStatus(403);
        $this->actingAs($staff)->getJson(route('admin.leads.show', $lead))->assertStatus(403);
    }

    /**
     * Staff with leads.view can access list and detail.
     */
    public function test_user_with_leads_view_can_access_listing(): void
    {
        $staff = $this->createStaffUserWithPermission('leads.view');
        $this->actingAs($staff)->getJson(route('admin.leads.index'))->assertStatus(200);
    }

    public function test_user_with_leads_view_can_access_detail(): void
    {
        $lead = Lead::factory()->create();
        $staff = $this->createStaffUserWithPermission('leads.view');
        $this->actingAs($staff)->getJson(route('admin.leads.show', $lead))->assertStatus(200);
    }

    /**
     * Staff with leads.manage can access list and detail.
     */
    public function test_user_with_leads_manage_can_access_listing(): void
    {
        $staff = $this->createStaffUserWithPermission('leads.manage');
        $this->actingAs($staff)->getJson(route('admin.leads.index'))->assertStatus(200);
    }

    public function test_user_with_leads_manage_can_access_detail(): void
    {
        $lead = Lead::factory()->create();
        $staff = $this->createStaffUserWithPermission('leads.manage');
        $this->actingAs($staff)->getJson(route('admin.leads.show', $lead))->assertStatus(200);
    }

    /**
     * Lead listing is paginated and enforces limits.
     */
    public function test_lead_list_is_paginated(): void
    {
        Lead::factory()->count(25)->create();
        $staff = $this->createStaffUserWithPermission('leads.view');

        $response = $this->actingAs($staff)
            ->getJson(route('admin.leads.index', ['per_page' => 10]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'current_page',
            'data',
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);

        $this->assertSame(10, count($response->json('data')));
        $this->assertSame(25, $response->json('total'));
        $this->assertSame(10, (int) $response->json('per_page'));
    }

    /**
     * Lead listing orders by created_at DESC (newest first).
     */
    public function test_listing_ordering_newest_leads_first(): void
    {
        $leadA = Lead::factory()->create(['created_at' => now()->subDays(2)]);
        $leadB = Lead::factory()->create(['created_at' => now()->subHours(5)]);
        $leadC = Lead::factory()->create(['created_at' => now()]);

        $staff = $this->createStaffUserWithPermission('leads.view');

        $response = $this->actingAs($staff)->getJson(route('admin.leads.index'));

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertSame($leadC->public_id, $data[0]['public_id']);
        $this->assertSame($leadB->public_id, $data[1]['public_id']);
        $this->assertSame($leadA->public_id, $data[2]['public_id']);
    }

    /**
     * Listing response is public safe and maps fields correctly.
     */
    public function test_listing_response_is_public_safe(): void
    {
        $assignee = User::factory()->create();
        $lead = Lead::factory()->create([
            'assigned_to_user_id' => $assignee->id,
            'utm_source' => 'google',
            'requirements' => 'Custom branding requirements',
            'lost_reason' => 'Too expensive',
        ]);

        $staff = $this->createStaffUserWithPermission('leads.view');
        $response = $this->actingAs($staff)->getJson(route('admin.leads.index'));

        $response->assertStatus(200);
        $data = $response->json('data')[0];

        // Allowed fields
        $this->assertSame($lead->public_id, $data['public_id']);
        $this->assertSame($lead->status, $data['status']);
        $this->assertSame($lead->priority, $data['priority']);
        $this->assertSame($lead->contact_name, $data['contact_name']);
        $this->assertSame($lead->company_name, $data['company_name']);
        $this->assertSame($lead->email, $data['email']);
        $this->assertSame($lead->phone, $data['phone']);
        $this->assertSame($assignee->id, $data['assigned_to_user_id']);

        // Hidden/Forbidden fields
        $this->assertArrayNotHasKey('id', $data);
        $this->assertArrayNotHasKey('customer_id', $data);
        $this->assertArrayNotHasKey('created_by_user_id', $data);
        $this->assertArrayNotHasKey('requirements', $data);
        $this->assertArrayNotHasKey('utm_source', $data);
        $this->assertArrayNotHasKey('utm_medium', $data);
        $this->assertArrayNotHasKey('utm_campaign', $data);
        $this->assertArrayNotHasKey('utm_content', $data);
        $this->assertArrayNotHasKey('utm_term', $data);
        $this->assertArrayNotHasKey('referrer_url', $data);
        $this->assertArrayNotHasKey('landing_page_url', $data);
        $this->assertArrayNotHasKey('lost_reason', $data);
    }

    /**
     * Detail response is public safe and maps fields correctly.
     */
    public function test_detail_response_is_public_safe(): void
    {
        $assignee = User::factory()->create();
        $lead = Lead::factory()->create([
            'assigned_to_user_id' => $assignee->id,
            'utm_source' => 'facebook',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'summer_sale',
            'utm_content' => 'ad_1',
            'utm_term' => 'custom t-shirts',
            'referrer_url' => 'https://facebook.com',
            'landing_page_url' => 'https://okinacraft.com/bulk',
            'requirements' => '100 custom hoodies with gold embroidery',
            'product_interest' => ['hoodies', 't-shirts'],
            'lost_reason' => 'Customer did not reply',
        ]);

        $staff = $this->createStaffUserWithPermission('leads.view');
        $response = $this->actingAs($staff)->getJson(route('admin.leads.show', $lead));

        $response->assertStatus(200);
        $data = $response->json();

        // Allowed summary and detail fields
        $this->assertSame($lead->public_id, $data['public_id']);
        $this->assertSame($lead->status, $data['status']);
        $this->assertSame($lead->priority, $data['priority']);
        $this->assertSame($lead->contact_name, $data['contact_name']);
        $this->assertSame($lead->company_name, $data['company_name']);
        $this->assertSame($lead->email, $data['email']);
        $this->assertSame($lead->phone, $data['phone']);
        $this->assertSame($assignee->id, $data['assigned_to_user_id']);

        $this->assertSame('facebook', $data['utm_source']);
        $this->assertSame('cpc', $data['utm_medium']);
        $this->assertSame('summer_sale', $data['utm_campaign']);
        $this->assertSame('ad_1', $data['utm_content']);
        $this->assertSame('custom t-shirts', $data['utm_term']);
        $this->assertSame('https://facebook.com', $data['referrer_url']);
        $this->assertSame('https://okinacraft.com/bulk', $data['landing_page_url']);
        $this->assertSame('100 custom hoodies with gold embroidery', $data['requirements']);
        $this->assertSame(['hoodies', 't-shirts'], $data['product_interest']);
        $this->assertSame('Customer did not reply', $data['lost_reason']);

        // Hidden/Forbidden fields
        $this->assertArrayNotHasKey('id', $data);
        $this->assertArrayNotHasKey('customer_id', $data);
        $this->assertArrayNotHasKey('created_by_user_id', $data);
    }
}
