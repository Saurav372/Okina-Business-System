<?php

namespace Tests\Feature;

use App\Models\CustomerAccount;
use App\Notifications\CustomerResetPasswordNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class CustomerAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_customer_login_page(): void
    {
        $this->get(route('customer.account'))
            ->assertRedirect(route('customer.login'));
    }

    public function test_customers_can_register_and_access_the_account_area(): void
    {
        $this->post(route('customer.register.store'), [
            'name' => 'Saurav Customer',
            'email' => 'Customer@Example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('customer.account'));

        $account = CustomerAccount::first();

        $this->assertNotNull($account);
        $this->assertSame('customer@example.com', $account->normalized_email);
        $this->assertSame('customer@example.com', $account->customer->email);
        $this->assertAuthenticatedAs($account, 'customer');

        $this->get(route('customer.account'))
            ->assertRedirect(rtrim(env('PUBLIC_SITE_URL', 'http://127.0.0.1:4321'), '/').'/account');
    }

    public function test_customer_registration_requires_unique_normalized_email(): void
    {
        CustomerAccount::factory()->create([
            'email' => 'customer@example.com',
            'normalized_email' => 'customer@example.com',
        ]);

        $this->post(route('customer.register.store'), [
            'name' => 'Duplicate Customer',
            'email' => 'Customer@Example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('customer');
    }

    public function test_active_verified_customers_can_log_in(): void
    {
        $account = CustomerAccount::factory()->create([
            'email' => 'customer@example.com',
            'normalized_email' => 'customer@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->post(route('customer.login.store'), [
            'email' => 'CUSTOMER@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('customer.account'));

        $this->assertAuthenticatedAs($account, 'customer');

        $account->refresh();
        $this->assertSame(0, $account->failed_login_attempts);
        $this->assertNotNull($account->last_login_at);
    }

    public function test_inactive_customers_cannot_access_customer_account_routes(): void
    {
        $account = CustomerAccount::factory()->suspended()->create([
            'password' => Hash::make('password123'),
        ]);

        $this->post(route('customer.login.store'), [
            'email' => $account->email,
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('customer');
    }

    public function test_unverified_customers_cannot_access_customer_account_routes(): void
    {
        $account = CustomerAccount::factory()->pendingVerification()->create([
            'password' => Hash::make('password123'),
        ]);

        $this->post(route('customer.login.store'), [
            'email' => $account->email,
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('customer');
    }

    public function test_staff_sessions_do_not_grant_customer_account_access(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)
            ->get(route('customer.account'))
            ->assertRedirect(route('customer.login'));

        $this->assertAuthenticatedAs($staff);
        $this->assertGuest('customer');
    }

    public function test_customers_can_log_out_and_lose_account_access(): void
    {
        $account = CustomerAccount::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($account, 'customer')
            ->post(route('customer.logout'))
            ->assertRedirect(route('customer.login'));

        $this->assertGuest('customer');

        $this->get(route('customer.account'))
            ->assertRedirect(route('customer.login'));
    }

    public function test_customer_can_request_a_password_reset_without_account_enumeration(): void
    {
        Notification::fake();
        $account = CustomerAccount::factory()->create([
            'email' => 'Customer@Example.com',
            'normalized_email' => 'customer@example.com',
        ]);

        $this->post(route('customer.password.email'), ['email' => 'CUSTOMER@example.com'])
            ->assertSessionHas('status', 'If an eligible account exists, a password reset link has been sent.');
        $this->post(route('customer.password.email'), ['email' => 'missing@example.com'])
            ->assertSessionHas('status', 'If an eligible account exists, a password reset link has been sent.');

        Notification::assertSentTo($account, CustomerResetPasswordNotification::class);
    }

    public function test_customer_can_reset_their_password_with_a_valid_token(): void
    {
        $account = CustomerAccount::factory()->create([
            'password' => Hash::make('old-password'),
            'failed_login_attempts' => 4,
            'locked_until' => now()->addMinute(),
        ]);
        $token = Password::broker('customer_accounts')->createToken($account);

        $this->post(route('customer.password.update'), [
            'token' => $token,
            'email' => strtoupper($account->email),
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('customer.login'));

        $account->refresh();
        $this->assertTrue(Hash::check('new-password-123', $account->password));
        $this->assertSame(0, $account->failed_login_attempts);
        $this->assertNull($account->locked_until);
        $this->assertNotNull($account->password_changed_at);
    }
}
