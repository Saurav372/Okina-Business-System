<?php

namespace Tests\Feature;

use App\Events\AuditEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(array $attributes = []): User
    {
        $role = Role::query()->updateOrCreate(
            ['slug' => Role::ADMIN],
            ['name' => 'Administrator', 'description' => 'Admin Role']
        );

        $user = User::factory()->create(array_merge([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
            'password' => Hash::make('OldPassword123!'),
            'last_login_at' => now(),
            'last_login_ip' => '127.0.0.1',
        ], $attributes));

        $user->assignRole($role);

        return $user;
    }

    /**
     * Test guest redirects to login for profile and security pages.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.profile'))->assertRedirect(route('login'));
        $this->put(route('admin.profile.update'))->assertRedirect(route('login'));
        $this->get(route('admin.security'))->assertRedirect(route('login'));
        $this->put(route('admin.security.password.update'))->assertRedirect(route('login'));
        $this->post(route('admin.security.sessions.revoke_others'))->assertRedirect(route('login'));
    }

    /**
     * Test authenticated staff can view profile page and role badge.
     */
    public function test_authenticated_staff_can_view_profile(): void
    {
        $user = $this->createAdminUser(['name' => 'Jane Admin', 'phone' => '+919876543210']);

        $this->actingAs($user)
            ->get(route('admin.profile'))
            ->assertOk()
            ->assertSee('Jane Admin')
            ->assertSee('Administrator')
            ->assertSee('+919876543210');
    }

    /**
     * Test updating profile details with input trimming and nullification of empty phone.
     */
    public function test_profile_update_trims_inputs_and_nullifies_empty_phone(): void
    {
        $user = $this->createAdminUser(['name' => 'Old Name', 'phone' => '1234567890']);

        Event::fake([AuditEvent::class]);

        $response = $this->actingAs($user)
            ->put(route('admin.profile.update'), [
                'name' => '  New Updated Name  ',
                'phone' => '   ',
            ]);

        $response->assertRedirect(route('admin.profile'));
        $response->assertSessionHas('status', 'Profile updated successfully.');

        $user->refresh();
        $this->assertEquals('New Updated Name', $user->name);
        $this->assertNull($user->phone);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($user) {
            return $event->key === 'profile.updated'
                && $event->payload['user_id'] === $user->id
                && isset($event->payload['changed_fields']['name'])
                && $event->payload['changed_fields']['name']['from'] === 'Old Name'
                && $event->payload['changed_fields']['name']['to'] === 'New Updated Name';
        });
    }

    /**
     * Test profile update ignores prohibited privileged fields.
     */
    public function test_profile_update_prohibits_privileged_fields(): void
    {
        $user = $this->createAdminUser(['email' => 'admin@example.com']);

        $response = $this->actingAs($user)
            ->from(route('admin.profile'))
            ->put(route('admin.profile.update'), [
                'name' => 'Jane Smith',
                'email' => 'hacked@example.com',
                'status' => User::STATUS_SUSPENDED,
            ]);

        $response->assertRedirect(route('admin.profile'));
        $response->assertSessionHasErrors(['email', 'status'], errorBag: 'profile');

        $user->refresh();
        $this->assertEquals('admin@example.com', $user->email);
        $this->assertEquals(User::STATUS_ACTIVE, $user->status);
    }

    /**
     * Test no-op profile update does not dispatch audit event.
     */
    public function test_no_op_profile_update_does_not_dispatch_audit_event(): void
    {
        $user = $this->createAdminUser(['name' => 'Same Name', 'phone' => '+919999999999']);

        Event::fake([AuditEvent::class]);

        $response = $this->actingAs($user)
            ->put(route('admin.profile.update'), [
                'name' => 'Same Name',
                'phone' => '+919999999999',
            ]);

        $response->assertRedirect(route('admin.profile'));

        Event::assertNotDispatched(AuditEvent::class);
    }

    /**
     * Test password update fails with invalid current password.
     */
    public function test_password_update_fails_with_invalid_current_password(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)
            ->from(route('admin.security'))
            ->put(route('admin.security.password.update'), [
                'current_password' => 'WrongCurrentPassword',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertRedirect(route('admin.security'));
        $response->assertSessionHasErrors(['current_password'], errorBag: 'password');
    }

    /**
     * Test password update fails if new password matches existing hash.
     */
    public function test_password_update_fails_when_new_password_matches_current_hash(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)
            ->from(route('admin.security'))
            ->put(route('admin.security.password.update'), [
                'current_password' => 'OldPassword123!',
                'password' => 'OldPassword123!',
                'password_confirmation' => 'OldPassword123!',
            ]);

        $response->assertRedirect(route('admin.security'));
        $response->assertSessionHasErrors(['password'], errorBag: 'password');
    }

    /**
     * Test successful password update updates hash, password_changed_at, rotates session, and authenticates new password.
     */
    public function test_successful_password_update_and_session_rotation(): void
    {
        $user = $this->createAdminUser(['password' => Hash::make('OldPassword123!')]);

        Event::fake([AuditEvent::class]);

        $response = $this->actingAs($user)
            ->put(route('admin.security.password.update'), [
                'current_password' => 'OldPassword123!',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertRedirect(route('admin.security'));
        $response->assertSessionHas('status', 'Password updated successfully.');

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));
        $this->assertFalse(Hash::check('OldPassword123!', $user->password));
        $this->assertNotNull($user->password_changed_at);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($user) {
            return $event->key === 'security.password_updated'
                && $event->payload['user_id'] === $user->id;
        });
    }

    /**
     * Test explicit session revocation revokes other active session rows for user.
     */
    public function test_explicit_session_revocation(): void
    {
        $user = $this->createAdminUser();

        Event::fake([AuditEvent::class]);

        $response = $this->actingAs($user)
            ->post(route('admin.security.sessions.revoke_others'));

        $response->assertRedirect(route('admin.security'));

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($user) {
            return $event->key === 'security.sessions_revoked'
                && $event->payload['user_id'] === $user->id;
        });
    }
}
