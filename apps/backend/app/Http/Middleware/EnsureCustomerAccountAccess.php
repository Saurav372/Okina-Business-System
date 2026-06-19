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
            return redirect()->route('customer.login');
        }

        if (! $account->canAccessCustomerAccount()) {
            Auth::guard('customer')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('customer.login');
        }

        return $next($request);
    }
}
