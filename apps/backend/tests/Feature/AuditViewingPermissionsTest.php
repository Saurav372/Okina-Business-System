<?php

namespace Tests\Feature;

use App\Enums\AuditActorType;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditViewingPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $financeStaff;

    private User $salesStaff;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        $this->seed(AccessControlSeeder::class);

        // 1. Super Admin (has all permissions)
        $this->superAdmin = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->superAdmin->assignRole(Role::SUPER_ADMIN);

        // 2. Finance Staff (has audit.view permission specifically)
        $this->financeStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->financeStaff->assignRole(Role::FINANCE_STAFF);

        // 3. Sales Staff (does NOT have audit.view permission)
        $this->salesStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->salesStaff->assignRole(Role::SALES_STAFF);
    }

    public function test_guests_cannot_view_audit_logs_list_or_details(): void
    {
        $this->getJson(route('admin.audit_logs.index'))
            ->assertStatus(302)
            ->assertRedirect(route('login'));

        $log = AuditLog::create([
            'event_id' => 'evt_123',
            'action' => 'test.action',
            'module' => 'test',
            'actor_type' => AuditActorType::SYSTEM,
            'subject_type' => 'test',
            'occurred_at' => now(),
        ]);

        $this->getJson(route('admin.audit_logs.show', $log->id))
            ->assertStatus(302)
            ->assertRedirect(route('login'));
    }

    public function test_unauthorized_staff_roles_cannot_view_audit_logs_list_or_details(): void
    {
        $log = AuditLog::create([
            'event_id' => 'evt_123',
            'action' => 'test.action',
            'module' => 'test',
            'actor_type' => AuditActorType::SYSTEM,
            'subject_type' => 'test',
            'occurred_at' => now(),
        ]);

        // Sales Staff is unauthorized (does not have audit.view)
        $this->actingAs($this->salesStaff)
            ->getJson(route('admin.audit_logs.index'))
            ->assertStatus(403);

        $this->actingAs($this->salesStaff)
            ->getJson(route('admin.audit_logs.show', $log->id))
            ->assertStatus(403);
    }

    public function test_authorized_staff_roles_can_view_audit_logs_list_and_details(): void
    {
        // Clear auto-generated setup logs for clean count assertions
        DB::table('audit_logs')->delete();

        $log1 = AuditLog::create([
            'event_id' => 'evt_001',
            'action' => 'order.created',
            'module' => 'orders',
            'actor_type' => AuditActorType::USER,
            'actor_user_id' => $this->financeStaff->id,
            'subject_type' => 'order',
            'subject_public_id' => 'ord_001',
            'occurred_at' => now(),
        ]);

        $log2 = AuditLog::create([
            'event_id' => 'evt_002',
            'action' => 'payment.recorded',
            'module' => 'payments',
            'actor_type' => AuditActorType::SYSTEM,
            'subject_type' => 'payment',
            'subject_public_id' => 'pay_002',
            'occurred_at' => now()->subDay(),
        ]);

        // 1. Super Admin access
        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('admin.audit_logs.index'))
            ->assertOk();

        $response->assertJsonCount(2, 'data');

        $this->actingAs($this->superAdmin)
            ->getJson(route('admin.audit_logs.show', $log1->id))
            ->assertOk()
            ->assertJsonPath('event_id', 'evt_001');

        // 2. Finance Staff access
        $response = $this->actingAs($this->financeStaff)
            ->getJson(route('admin.audit_logs.index'))
            ->assertOk();

        $response->assertJsonCount(2, 'data');

        $this->actingAs($this->financeStaff)
            ->getJson(route('admin.audit_logs.show', $log2->id))
            ->assertOk()
            ->assertJsonPath('event_id', 'evt_002');
    }

    public function test_audit_logs_can_be_filtered(): void
    {
        // Clear auto-generated setup logs
        DB::table('audit_logs')->delete();

        AuditLog::create([
            'event_id' => 'evt_001',
            'action' => 'order.created',
            'module' => 'orders',
            'actor_type' => AuditActorType::SYSTEM,
            'subject_type' => 'order',
            'subject_public_id' => 'ord_001',
            'occurred_at' => now(),
        ]);

        AuditLog::create([
            'event_id' => 'evt_002',
            'action' => 'payment.recorded',
            'module' => 'payments',
            'actor_type' => AuditActorType::SYSTEM,
            'subject_type' => 'payment',
            'subject_public_id' => 'pay_002',
            'occurred_at' => now()->subDay(),
        ]);

        // Filter by action
        $this->actingAs($this->superAdmin)
            ->getJson(route('admin.audit_logs.index', ['action' => 'order.created']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.event_id', 'evt_001');

        // Filter by module
        $this->actingAs($this->superAdmin)
            ->getJson(route('admin.audit_logs.index', ['module' => 'payments']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.event_id', 'evt_002');

        // Filter by subject_type
        $this->actingAs($this->superAdmin)
            ->getJson(route('admin.audit_logs.index', ['subject_type' => 'order']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.event_id', 'evt_001');

        // Filter by subject_public_id
        $this->actingAs($this->superAdmin)
            ->getJson(route('admin.audit_logs.index', ['subject_public_id' => 'pay_002']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.event_id', 'evt_002');
    }
}
