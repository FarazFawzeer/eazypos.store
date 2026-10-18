<?php

namespace App\Http\Middleware;

use App\Support\BusinessContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the signed-in user's active business membership and binds it as
 * the BusinessContext for this request. Users without one (removed staff,
 * suspended business) are signed out.
 */
class EnsureBusinessMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $membership = $user?->activeMembership();

        if (! $membership) {
            if ($user?->is_super_admin && ! $request->expectsJson()) {
                return redirect()->route('admin.dashboard');
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'Your account does not have access to an active business. Contact your business owner or EazyPOS support.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 401)
                : redirect()->route('home')->with('login_error', $message);
        }

        app()->instance(BusinessContext::class, new BusinessContext($user, $membership, $membership->business));

        return $next($request);
    }
}
