<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * EazyPOS team console: create businesses for approved (paid) customers,
 * suspend or reactivate them, reset owner passwords and review inquiries.
 */
class AdminController extends Controller
{
    public function index(): View
    {
        $businesses = Business::query()
            ->with('owner:id,name,email,last_login_at')
            ->withCount('members')
            ->orderByDesc('created_at')
            ->get();

        $inquiries = Inquiry::query()
            ->orderByRaw("CASE status WHEN 'new' THEN 0 WHEN 'contacted' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return view('admin.dashboard', compact('businesses', 'inquiries'));
    }

    public function storeBusiness(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:120'],
            'country' => ['required', 'string', 'max:120'],
            'currency' => ['required', 'in:LKR,USD,GBP,EUR'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:2000'],
            'owner_name' => ['required', 'string', 'max:120'],
            'owner_email' => ['required', 'email', 'max:255'],
            'owner_username' => ['nullable', 'string', 'regex:/^[a-z0-9][a-z0-9._-]{2,31}$/'],
            'owner_password' => ['nullable', Password::min(8)],
        ], [
            'owner_username.regex' => 'Username must be 3-32 characters and use only letters, numbers, dot, underscore or hyphen.',
        ]);

        $email = mb_strtolower(trim($data['owner_email']));
        $existing = User::query()->where('email', $email)->first();
        if (! $existing && empty($data['owner_password'])) {
            return back()->withInput()->withErrors(['owner_password' => 'Set a starting password for the new owner account.']);
        }

        $business = DB::transaction(function () use ($data, $email, $existing) {
            $owner = $existing ?? tap(User::query()->create([
                'name' => $data['owner_name'],
                'email' => $email,
                'password' => $data['owner_password'],
            ]), fn (User $u) => $u->forceFill(['email_verified_at' => now()])->save());

            $business = Business::query()->create([
                'name' => trim($data['name']),
                'business_type' => ($data['business_type'] ?? null) ?: null,
                'country' => trim($data['country']),
                'currency' => $data['currency'],
                'email' => $email,
                'phone' => ($data['phone'] ?? null) ?: null,
                'address' => ($data['address'] ?? null) ?: null,
                'status' => 'active',
                'owner_id' => $owner->id,
            ]);

            BusinessUser::query()->create([
                'business_id' => $business->id,
                'user_id' => $owner->id,
                'role' => 'owner',
                'permissions' => BusinessUser::OWNER_PERMISSIONS,
                'status' => 'active',
                'full_name' => $data['owner_name'],
                'username' => $data['owner_username'] ?? null,
            ]);

            return $business;
        });

        return redirect()->route('admin.dashboard')->with('status', "Business \"{$business->name}\" created. The owner can now sign in with {$email}.");
    }

    public function updateStatus(Request $request, Business $business): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(Business::STATUSES)]]);
        $business->update(['status' => $data['status']]);

        if ($data['status'] !== 'active') {
            // Sign out everyone of a suspended business straight away.
            DB::table('sessions')->whereIn('user_id', $business->members()->pluck('user_id'))->delete();
        }

        return back()->with('status', "{$business->name} is now {$data['status']}.");
    }

    public function resetOwnerPassword(Request $request, Business $business): RedirectResponse
    {
        $data = $request->validate(['password' => ['required', Password::min(8)]]);

        $owner = $business->owner ?? abort(404);
        $owner->forceFill(['password' => $data['password'], 'remember_token' => null])->save();
        DB::table('sessions')->where('user_id', $owner->id)->delete();

        return back()->with('status', "Password updated for {$owner->email}.");
    }

    public function updateInquiry(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:new,contacted,closed']]);
        $inquiry->update($data);

        return back()->with('status', 'Inquiry updated.');
    }
}
