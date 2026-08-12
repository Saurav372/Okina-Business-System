<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerAccount;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends Controller
{
    private const LOGIN_ERROR = 'The provided credentials are incorrect or this account cannot sign in.';

    public function register()
    {
        return view('customer.register');
    }

    public function storeRegistration(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $normalizedEmail = CustomerAccount::normalizeEmail($validated['email']);

        if (CustomerAccount::where('normalized_email', $normalizedEmail)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'An account already exists for this email address.',
            ]);
        }

        $account = DB::transaction(function () use ($validated, $normalizedEmail): CustomerAccount {
            $customer = Customer::create([
                'name' => $validated['name'],
                'email' => $normalizedEmail,
            ]);

            return CustomerAccount::create([
                'customer_id' => $customer->id,
                'email' => $validated['email'],
                'normalized_email' => $normalizedEmail,
                'password' => Hash::make($validated['password']),
                'status' => CustomerAccount::STATUS_ACTIVE,
                'email_verified_at' => now(),
                'password_changed_at' => now(),
            ]);
        });

        Auth::guard('customer')->login($account, remember: false);
        $request->session()->regenerate();

        return redirect()->route('customer.account');
    }

    public function login()
    {
        return view('customer.login');
    }

    public function storeLogin(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited($request);

        $normalizedEmail = CustomerAccount::normalizeEmail($request->string('email')->toString());
        $account = CustomerAccount::where('normalized_email', $normalizedEmail)->first();

        if (! $account || ! Hash::check($request->string('password')->toString(), $account->password)) {
            $this->recordFailedLogin($request, $account);
            $this->throwLoginError();
        }

        if (! $account->canAccessCustomerAccount()) {
            $this->recordFailedLogin($request, $account);
            $this->throwLoginError();
        }

        RateLimiter::clear($this->throttleKey($request));
        $account->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        Auth::guard('customer')->login($account, remember: false);
        $request->session()->regenerate();

        return redirect()->intended(route('customer.account'));
    }

    public function forgotPassword()
    {
        return view('customer.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $validated = $request->validate(['email' => ['required', 'email']]);
        $normalizedEmail = CustomerAccount::normalizeEmail($validated['email']);
        $account = CustomerAccount::query()->where('normalized_email', $normalizedEmail)->first();

        if ($account) {
            Password::broker('customer_accounts')->sendResetLink(['email' => $account->email]);
        }

        return back()->with('status', 'If an eligible account exists, a password reset link has been sent.');
    }

    public function resetPassword(Request $request, string $token)
    {
        return view('customer.reset-password', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
            'password_confirmation' => ['required', 'string'],
        ]);

        $normalizedEmail = CustomerAccount::normalizeEmail($validated['email']);
        $account = CustomerAccount::query()->where('normalized_email', $normalizedEmail)->first();

        if (! $account) {
            throw ValidationException::withMessages(['email' => 'This password reset link is invalid or has expired.']);
        }

        $status = Password::broker('customer_accounts')->reset(
            [
                'email' => $account->email,
                'password' => $validated['password'],
                'password_confirmation' => $validated['password_confirmation'],
                'token' => $validated['token'],
            ],
            function (CustomerAccount $customerAccount, string $password): void {
                $customerAccount->forceFill([
                    'password' => Hash::make($password),
                    'password_changed_at' => now(),
                    'failed_login_attempts' => 0,
                    'locked_until' => null,
                ])->save();
                event(new PasswordReset($customerAccount));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'This password reset link is invalid or has expired.']);
        }

        return redirect()->route('customer.login')->with('status', 'Your password has been reset. You can now sign in.');
    }

    public function account()
    {
        $siteUrl = rtrim(env('PUBLIC_SITE_URL', 'http://127.0.0.1:4321'), '/');

        return redirect()->away($siteUrl.'/account');
    }

    public function destroy(Request $request)
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login');
    }

    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $this->throwLoginError();
    }

    private function recordFailedLogin(Request $request, ?CustomerAccount $account): void
    {
        RateLimiter::hit($this->throttleKey($request), 300);

        if (! $account) {
            return;
        }

        $attempts = min($account->failed_login_attempts + 1, 255);

        $account->forceFill([
            'failed_login_attempts' => $attempts,
            'locked_until' => $attempts >= 5 ? now()->addMinutes(5) : $account->locked_until,
        ])->save();
    }

    private function throttleKey(Request $request): string
    {
        return Str::lower($request->string('email')->toString()).'|'.$request->ip();
    }

    private function throwLoginError(): never
    {
        throw ValidationException::withMessages([
            'email' => self::LOGIN_ERROR,
        ]);
    }
}
