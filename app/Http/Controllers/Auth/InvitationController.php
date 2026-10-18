<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Staff invitation links: show the "Finish your EazyPOS account" modal on the
 * landing page and complete the account.
 */
class InvitationController extends Controller
{
    public function __construct(private readonly StaffService $staff) {}

    public function show(string $token): View
    {
        $invitation = $this->staff->findUsableInvitation($token);

        return view('landing', [
            'invite' => [
                'token' => $token,
                'valid' => (bool) $invitation,
                'email' => $invitation?->email,
                'fullName' => $invitation?->full_name,
                'username' => $invitation?->username,
                'business' => $invitation?->business?->name,
                'error' => $invitation ? null : 'This invitation link has expired or has already been used. Please request a new invitation.',
            ],
        ]);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'username' => ['nullable', 'string', 'max:32'],
            'password' => ['required', 'string', 'max:255'],
            'password_confirmation' => ['required', 'string', 'max:255'],
        ], [
            'full_name.required' => 'Enter your full name.',
            'password.required' => 'Password must be at least 8 characters.',
            'password_confirmation.required' => 'Passwords do not match.',
        ]);

        $user = $this->staff->accept($token, $data);

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json(['redirect' => route('app')]);
    }
}
