<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $salesStaff;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        $this->seed(AccessControlSeeder::class);

        // Create Super Admin user
        $this->superAdmin = User::factory()->create([
            'name' => 'Saurav SuperAdmin',
            'email' => 'superadmin@okina.test',
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->superAdmin->assignRole(Role::SUPER_ADMIN);

        // Create Sales Staff user
        $this->salesStaff = User::factory()->create([
            'name' => 'Aman Sales',
            'email' => 'aman@okina.test',
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->salesStaff->assignRole(Role::SALES_STAFF);
    }

    public function test_unauthorized_guests_and_customers_cannot_access_staff_directory(): void
    {
        $this->get(route('admin.staff.index'))
            ->assertStatus(302)
            ->assertRedirect(route('login'));

        $customer = User::factory()->create([
            'user_type' => User::TYPE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($customer)
            ->get(route('admin.staff.index'))
            ->assertStatus(302)
            ->assertRedirect(route('login'));
    }

    public function test_super_admin_can_view_staff_index_with_metrics_and_roles(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.staff.index'))
            ->assertStatus(200);

        $response->assertSee('Staff & Access');
        $response->assertSee('Saurav SuperAdmin');
        $response->assertSee('Aman Sales');
        $response->assertSee('Super Admin');
        $response->assertSee('Sales Staff');
    }

    public function test_route_binding_rejects_customer_id_with_404(): void
    {
        $customerUser = User::factory()->create([
            'name' => 'Customer User',
            'email' => 'customer@okina.test',
            'user_type' => User::TYPE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->superAdmin)
            ->patch(route('admin.staff.update', $customerUser->id), [
                'name' => 'Hacked Name',
            ])
            ->assertStatus(404);
    }

    public function test_super_admin_can_invite_staff_user_with_expiring_cryptographic_token(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.staff.store'), [
                'name' => 'Priya Finance',
                'email' => 'priya@okina.test',
                'phone' => '+91 9988776655',
                'roles' => [Role::FINANCE_STAFF],
            ]);

        $response->assertStatus(302)
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHas('invitation_url');

        $invited = User::query()->where('email', 'priya@okina.test')->first();
        $this->assertNotNull($invited);
        $this->assertEquals(User::STATUS_INVITED, $invited->status);
        $this->assertEquals(User::TYPE_STAFF, $invited->user_type);
        $this->assertNull($invited->email_verified_at);
        $this->assertNotNull($invited->invitation_token_hash);
        $this->assertTrue($invited->hasValidInvitation());
        $this->assertTrue($invited->hasRole(Role::FINANCE_STAFF));
    }

    public function test_invited_staff_can_activate_account_via_token_and_set_password(): void
    {
        $staff = User::create([
            'name' => 'Rahul Dev',
            'email' => 'rahul@okina.test',
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_INVITED,
            'password' => Hash::make('placeholder'),
        ]);
        $plainToken = $staff->generateInvitationToken(48);

        // 1. Visit activation view
        $this->get(route('staff.invitation.show', $plainToken))
            ->assertStatus(200)
            ->assertSee('Staff Account Activation')
            ->assertSee('Rahul Dev');

        // 2. Complete activation
        $response = $this->post(route('staff.invitation.update', $plainToken), [
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(302)->assertRedirect(route('admin.dashboard'));

        $staff->refresh();
        $this->assertEquals(User::STATUS_ACTIVE, $staff->status);
        $this->assertNotNull($staff->email_verified_at);
        $this->assertNull($staff->invitation_token_hash);
        $this->assertNull($staff->invitation_expires_at);
        $this->assertTrue(Hash::check('SecurePass123!', $staff->password));
    }

    public function test_invitation_fails_for_expired_or_invalid_tokens(): void
    {
        $staff = User::create([
            'name' => 'Expired User',
            'email' => 'expired@okina.test',
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_INVITED,
            'password' => Hash::make('placeholder'),
        ]);
        $plainToken = $staff->generateInvitationToken(48);

        // Force expiration
        $staff->forceFill(['invitation_expires_at' => now()->subHour()])->save();

        $this->get(route('staff.invitation.show', $plainToken))
            ->assertStatus(200)
            ->assertSee('expired');

        $this->post(route('staff.invitation.update', $plainToken), [
            'password' => 'Pass12345!',
            'password_confirmation' => 'Pass12345!',
        ])->assertStatus(200)->assertSee('expired');
    }

    public function test_super_admin_cannot_demote_or_suspend_oneself(): void
    {
        // 1. Cannot self-suspend
        $this->actingAs($this->superAdmin)
            ->post(route('admin.staff.update_status', $this->superAdmin->id), [
                'action' => 'suspend',
            ])
            ->assertSessionHasErrors('action');

        $this->superAdmin->refresh();
        $this->assertEquals(User::STATUS_ACTIVE, $this->superAdmin->status);

        // 2. Cannot remove super_admin from oneself
        $this->actingAs($this->superAdmin)
            ->put(route('admin.staff.update_roles', $this->superAdmin->id), [
                'roles' => [Role::SALES_STAFF],
            ])
            ->assertSessionHasErrors('roles');

        $this->superAdmin->refresh();
        $this->assertTrue($this->superAdmin->hasRole(Role::SUPER_ADMIN));
    }

    public function test_cannot_demote_the_last_operational_super_admin(): void
    {
        // Create second Super Admin but in suspended state
        $suspendedSuperAdmin = User::factory()->create([
            'name' => 'Suspended Admin',
            'email' => 'suspended@okina.test',
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_SUSPENDED,
            'email_verified_at' => now(),
        ]);
        $suspendedSuperAdmin->assignRole(Role::SUPER_ADMIN);

        // Create third active Super Admin
        $targetSuperAdmin = User::factory()->create([
            'name' => 'Target SuperAdmin',
            'email' => 'target@okina.test',
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $targetSuperAdmin->assignRole(Role::SUPER_ADMIN);

        // Now demote Target SuperAdmin -> succeeds because $this->superAdmin is also an active operational super admin (count = 2)
        $this->actingAs($this->superAdmin)
            ->put(route('admin.staff.update_roles', $targetSuperAdmin->id), [
                'roles' => [Role::ADMIN],
            ])
            ->assertSessionHasNoErrors();

        $targetSuperAdmin->refresh();
        $this->assertFalse($targetSuperAdmin->hasRole(Role::SUPER_ADMIN));

        // Now only 1 operational Super Admin remains ($this->superAdmin). Another request to demote them should fail.
        $this->actingAs($this->superAdmin)
            ->put(route('admin.staff.update_roles', $this->superAdmin->id), [
                'roles' => [Role::ADMIN],
            ])
            ->assertSessionHasErrors('roles');
    }

    public function test_suspending_staff_evicts_session_and_blocks_dashboard_immediately(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('admin.staff.update_status', $this->salesStaff->id), [
                'action' => 'suspend',
            ])
            ->assertSessionHasNoErrors();

        $this->salesStaff->refresh();
        $this->assertEquals(User::STATUS_SUSPENDED, $this->salesStaff->status);

        // Now sales staff attempts to access dashboard
        $this->actingAs($this->salesStaff)
            ->get(route('admin.dashboard'))
            ->assertStatus(302)
            ->assertRedirect(route('login'));
    }

    public function test_unlocking_staff_clears_lockout_without_altering_lifecycle_status(): void
    {
        $this->salesStaff->forceFill([
            'status' => User::STATUS_ACTIVE,
            'locked_until' => now()->addMinutes(30),
            'failed_login_attempts' => 5,
        ])->save();

        $this->assertTrue($this->salesStaff->isSecurityLocked());

        $this->actingAs($this->superAdmin)
            ->post(route('admin.staff.update_status', $this->salesStaff->id), [
                'action' => 'unlock',
            ])
            ->assertSessionHasNoErrors();

        $this->salesStaff->refresh();
        $this->assertFalse($this->salesStaff->isSecurityLocked());
        $this->assertNull($this->salesStaff->locked_until);
        $this->assertEquals(0, $this->salesStaff->failed_login_attempts);
        $this->assertEquals(User::STATUS_ACTIVE, $this->salesStaff->status);
    }

    public function test_role_permissions_matrix_renders_and_updates_with_audit_delta(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.roles.index'))
            ->assertStatus(200);

        $response->assertSee('Roles & Permissions');
        $response->assertSee('Domain Capabilities Matrix');
        $response->assertSee('Universal Bypass');

        $salesRole = Role::query()->where('slug', Role::SALES_STAFF)->firstOrFail();

        // Update sales role permissions
        $newPerms = ['dashboard.access', 'orders.view', 'customers.view'];
        $this->actingAs($this->superAdmin)
            ->put(route('admin.roles.update', $salesRole->id), [
                'permissions' => $newPerms,
            ])
            ->assertSessionHasNoErrors();

        $salesRole->refresh();
        $this->assertEqualsCanonicalizing($newPerms, $salesRole->permissions()->pluck('slug')->all());
    }

    public function test_non_super_admin_cannot_access_or_modify_role_permissions(): void
    {
        $this->actingAs($this->salesStaff)
            ->get(route('admin.roles.index'))
            ->assertStatus(403);

        $salesRole = Role::query()->where('slug', Role::SALES_STAFF)->firstOrFail();

        $this->actingAs($this->salesStaff)
            ->put(route('admin.roles.update', $salesRole->id), [
                'permissions' => ['orders.view'],
            ])
            ->assertStatus(403);
    }
}
