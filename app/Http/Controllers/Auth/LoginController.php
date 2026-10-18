<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BusinessUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    /**
     * Sign in with email (or username) and password. Answers JSON for the
     * landing-page modal.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['nullable', 'boolean'],
        ], [
            'login.required' => 'Enter your email and password.',
            'password.required' => 'Enter your email and password.',
        ]);

        $login = mb_strtolower(trim($data['login']));
        // Per account, so changing IP address does not reset the counter.
        $throttleKey = 'login|'.Str::transliterate($login);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json(['message' => "Too many sign-in attempts. Please try again in {$seconds} seconds."], 429);
        }

        $email = $this->resolveEmail($login);

        if (! $email || ! Auth::attempt(['email' => $email, 'password' => $data['password']], (bool) ($data['remember'] ?? false))) {
            RateLimiter::hit($throttleKey, 60);

            return response()->json(['message' => 'Invalid login credentials'], 422);
        }

        RateLimiter::clear($throttleKey);

        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_super_admin && ! $user->activeMembership()) {
            $suspended = $user->memberships()->where('status', 'active')->exists();
            Auth::guard('web')->logout();

            return response()->json(['message' => $suspended
                ? 'Your business account is not active. Please contact EazyPOS support.'
                : 'Your account does not have access to an active business. Contact your business owner or EazyPOS support.',
            ], 403);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        $redirect = $user->is_super_admin && ! $user->activeMembership() ? route('admin.dashboard') : route('app');

        return response()->json(['redirect' => $redirect]);
    }

    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->expectsJson()
            ? response()->json(['redirect' => route('home')])
            : redirect()->route('home');
    }

    /**
     * Staff may sign in with their username; usernames are only unique per
     * business, so a username shared by several accounts is not accepted.
     */
    private function resolveEmail(string $login): ?string
    {
        if (str_contains($login, '@')) {
            return $login;
        }

        $userIds = BusinessUser::query()->where('username', $login)->distinct()->pluck('user_id');

        return $userIds->count() === 1 ? User::query()->whereKey($userIds->first())->value('email') : null;
    }
}
