<?php

namespace App\Http\Middleware;

use App\Models\CustomerAccount;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ValidateCustomerSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('customer');
        $account = $guard->user();
        if (! $account instanceof CustomerAccount) {
            return $next($request);
        }

        // Real browser sessions must carry the fingerprint captured at login.
        // Identities supplied without a session (e.g. request-local authentication)
        // still pass through the account eligibility check.
        $hasSessionLogin = $request->session()->has($guard->getName()) || $guard->viaRemember();
        $fingerprint = $request->session()->get('customer_password_fingerprint');
        $stale = $hasSessionLogin && (! is_string($fingerprint)
            || ! hash_equals(hash('sha256', $account->getAuthPassword()), $fingerprint));

        if ($stale || ! $account->canAccessCustomerAccount()) {
            $guard->logout();
            $request->session()->forget('customer_password_fingerprint');
            $request->session()->regenerateToken();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Your customer session is no longer valid. Please sign in again.'], $stale ? 401 : 403);
            }

            return redirect()->guest(route('customer.login'));
        }

        return $next($request);
    }
}
