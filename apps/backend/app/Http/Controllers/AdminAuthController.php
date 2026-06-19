<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    private const LOGIN_ERROR = 'The provided credentials are incorrect or your account cannot access the dashboard.';

    public function create()
    {
        return view('admin.login');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited($request);

        $user = User::where('email', $request->string('email')->lower()->toString())->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            $this->recordFailedLogin($request, $user);
            $this->throwDashboardLoginError();
        }

        if (! $user->canAccessDashboard()) {
            $this->recordFailedLogin($request, $user);
            $this->throwDashboardLoginError();
        }

        RateLimiter::clear($this->throttleKey($request));
        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        Auth::login($user, remember: false);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function index()
    {
        return view('admin.dashboard');
    }

    public function forgotPassword()
    {
        return view('admin.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->string('email')->lower()->toString())->first();

        if ($user?->canRequestDashboardPasswordReset()) {
            Password::sendResetLink($request->only('email'));
        }

        return back()->with('status', 'If a dashboard account exists for this email, a reset link has been sent.');
    }

    public function resetPassword(string $token, Request $request)
    {
        return view('admin.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::where('email', $request->string('email')->lower()->toString())->first();

        if (! $user?->canRequestDashboardPasswordReset()) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'This password reset link is invalid or has expired.']);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                    'failed_login_attempts' => 0,
                    'locked_until' => null,
                    'password_changed_at' => now(),
                ])->save();

                DB::table('sessions')->where('user_id', $user->id)->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'This password reset link is invalid or has expired.']);
        }

        return redirect()->route('login')->with('status', 'Your password has been reset. Please sign in again.');
    }

    public function twoFactorChallenge()
    {
        return view('admin.two-factor-challenge');
    }

    public function profile()
    {
        return view('admin.profile');
    }

    public function security()
    {
        return view('admin.security');
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $this->throwDashboardLoginError();
    }

    private function recordFailedLogin(Request $request, ?User $user): void
    {
        RateLimiter::hit($this->throttleKey($request), 300);

        if (! $user) {
            return;
        }

        $attempts = min($user->failed_login_attempts + 1, 255);

        $user->forceFill([
            'failed_login_attempts' => $attempts,
            'locked_until' => $attempts >= 5 ? now()->addMinutes(5) : $user->locked_until,
        ])->save();
    }

    private function throttleKey(Request $request): string
    {
        return Str::lower($request->string('email')->toString()).'|'.$request->ip();
    }

    private function throwDashboardLoginError(): never
    {
        throw ValidationException::withMessages([
            'email' => self::LOGIN_ERROR,
        ]);
    }
}
