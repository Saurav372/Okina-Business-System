<?php

namespace App\Http\Middleware;

use App\Models\CustomerAccount;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerAccountAccess
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $account = Auth::guard('customer')->user();

        if (! $account instanceof CustomerAccount) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('customer.login');
        }

        if (! $account->canAccessCustomerAccount()) {
            Auth::guard('customer')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Account is suspended or unverified.'], 403);
            }

            return redirect()->route('customer.login');
        }

        return $next($request);
    }
}
