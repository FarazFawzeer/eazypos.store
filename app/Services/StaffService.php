<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Mail\StaffInvitationMail;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\StaffInvitation;
use App\Models\User;
use App\Support\BusinessContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Staff invitations and business membership management.
 */
class StaffService
{
    public const USERNAME_PATTERN = '/^[a-z0-9][a-z0-9._-]{2,31}$/';

    public const INVITE_ROLES = ['admin', 'manager', 'cashier'];

    /**
     * Invite a staff member by email. Returns the invitation and its link.
     *
     * @return array{success: bool, invitation_id: string, email: string, role: string, status: string, expires_at: string, invite_url: string, email_sent: bool}
     */
    public function invite(BusinessContext $ctx, array $input): array
    {
        $this->authorizeManage($ctx, 'You do not have permission to invite users');

        $name = trim((string) ($input['name'] ?? ''));
        $email = mb_strtolower(trim((string) ($input['email'] ?? '')));
        $role = (string) ($input['role'] ?? 'cashier');
        $username = $this->cleanUsername($input['username'] ?? null);

        if ($name === '' || $email === '') {
            throw new BusinessRuleException('Full name and email are required');
        }
        if (mb_strlen($name) > 120) {
            throw new BusinessRuleException('Full name is too long');
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new BusinessRuleException('Please enter a valid email address');
        }
        if (! in_array($role, self::INVITE_ROLES, true)) {
            throw new BusinessRuleException('Invitation role must be admin, manager or cashier');
        }

        $permissions = BusinessUser::cleanPermissions(is_array($input['permissions'] ?? null) ? $input['permissions'] : []);
        $token = Str::random(64);

        $invitation = DB::transaction(function () use ($ctx, $name, $email, $role, $username, $permissions, $token) {
            $businessId = $ctx->businessId();

            $alreadyMember = BusinessUser::query()
                ->where('business_id', $businessId)
                ->where('status', 'active')
                ->whereHas('user', fn ($q) => $q->where('email', $email))
                ->exists();
            if ($alreadyMember) {
                throw new BusinessRuleException('This email is already an active member of this business');
            }

            if ($username && BusinessUser::query()->where('business_id', $businessId)->where('username', $username)->exists()) {
                throw new BusinessRuleException('That username is already in use in this business');
            }

            StaffInvitation::query()
                ->where('business_id', $businessId)
                ->where('email', $email)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            return StaffInvitation::query()->create([
                'business_id' => $businessId,
                'email' => $email,
                'full_name' => $name,
                'username' => $username,
                'role' => $role,
                'permissions' => $permissions,
                'invited_by' => $ctx->userId(),
                'status' => 'pending',
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDays(7),
            ]);
        });

        $url = route('invitation.show', $token);
        $sent = $this->sendInvitation($invitation, $ctx->business, $url);

        return [
            'success' => true,
            'invitation_id' => $invitation->id,
            'email' => $email,
            'role' => $role,
            'status' => 'pending',
            'expires_at' => $invitation->expires_at->toISOString(),
            'invite_url' => $url,
            'email_sent' => $sent,
        ];
    }

    public function findUsableInvitation(string $token): ?StaffInvitation
    {
        $invitation = StaffInvitation::query()->where('token_hash', hash('sha256', $token))->with('business')->first();

        if (! $invitation || ! $invitation->isUsable() || ! $invitation->business?->isActive()) {
            return null;
        }

        return $invitation;
    }

    /**
     * Complete an invitation: create (or verify) the account and join the business.
     * Port of accept_staff_invitation().
     */
    public function accept(string $token, array $input): User
    {
        $fullName = trim((string) ($input['full_name'] ?? ''));
        $username = $this->cleanUsername($input['username'] ?? null);
        $password = (string) ($input['password'] ?? '');

        if (mb_strlen($fullName) < 2) {
            throw new BusinessRuleException('Full name is required');
        }
        if (mb_strlen($fullName) > 120) {
            throw new BusinessRuleException('Full name is too long');
        }
        if (mb_strlen($password) < 8) {
            throw new BusinessRuleException('Password must be at least 8 characters.');
        }
        if ($password !== (string) ($input['password_confirmation'] ?? '')) {
            throw new BusinessRuleException('Passwords do not match.');
        }

        return DB::transaction(function () use ($token, $fullName, $username, $password) {
            $invitation = StaffInvitation::query()->where('token_hash', hash('sha256', $token))->lockForUpdate()->first();
            if (! $invitation || ! $invitation->isUsable()) {
                throw new BusinessRuleException('This invitation link has expired or has already been used. Please request a new invitation.');
            }

            $user = User::query()->where('email', $invitation->email)->first();
            if ($user) {
                // Never let an invitation link take over an existing account.
                if (! Hash::check($password, $user->password)) {
                    throw new BusinessRuleException('This email already has an EazyPOS account. Enter your existing password to join.');
                }
            } else {
                $user = User::query()->create([
                    'name' => $fullName,
                    'email' => $invitation->email,
                    'password' => $password,
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $member = BusinessUser::query()->where('business_id', $invitation->business_id)->where('user_id', $user->id)->first();

            if ($username && BusinessUser::query()
                ->where('business_id', $invitation->business_id)
                ->where('username', $username)
                ->where('user_id', '!=', $user->id)
                ->exists()) {
                throw new BusinessRuleException('That username is already in use in this business');
            }

            if (! $member || $member->status !== 'active') {
                $attributes = [
                    'role' => $invitation->role,
                    'permissions' => $invitation->permissions,
                    'status' => 'active',
                    'full_name' => $fullName,
                    'username' => $username,
                ];
                $member
                    ? $member->update($attributes)
                    : BusinessUser::query()->create($attributes + ['business_id' => $invitation->business_id, 'user_id' => $user->id]);
            }

            $invitation->update(['status' => 'accepted', 'accepted_at' => now()]);

            return $user;
        });
    }

    /**
     * Edit a member's name, role and permissions. Port of update_business_user().
     */
    public function update(BusinessContext $ctx, string $memberId, array $input): array
    {
        $this->authorizeManage($ctx, 'You do not have permission to manage users');

        $role = (string) ($input['role'] ?? 'cashier');
        if (! in_array($role, BusinessUser::ROLES, true)) {
            throw new BusinessRuleException('Invalid user role');
        }

        $member = BusinessUser::query()->where('business_id', $ctx->businessId())->whereKey($memberId)->lockForUpdate()->first()
            ?? throw new BusinessRuleException('Business user not found');

        $name = trim((string) ($input['full_name'] ?? ''));
        if ($name === '') {
            throw new BusinessRuleException('Name is required');
        }

        $isOwner = $member->user_id === $ctx->business->owner_id || $member->role === 'owner';
        if ($isOwner) {
            // The owner always keeps the owner role and full access.
            $role = 'owner';
            $permissions = BusinessUser::OWNER_PERMISSIONS;
        } else {
            if ($role === 'owner') {
                throw new BusinessRuleException('Invalid user role');
            }
            if ($member->user_id === $ctx->userId() && ! $ctx->membership->isOwner()) {
                throw new BusinessRuleException('You cannot change your own role or permissions.');
            }
            $permissions = BusinessUser::cleanPermissions(is_array($input['permissions'] ?? null) ? $input['permissions'] : []);
        }

        try {
            $member->update([
                'full_name' => mb_substr($name, 0, 120),
                'username' => array_key_exists('username', $input) ? $this->cleanUsername($input['username']) : $member->username,
                'role' => $role,
                'permissions' => $permissions,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new BusinessRuleException('That username is already used in this business');
        }

        return ['success' => true, 'business_user_id' => $member->id, 'user_id' => $member->user_id, 'role' => $role, 'username' => $member->username];
    }

    /**
     * Remove a staff member (by user id) or delete a pending invitation (by email).
     * The login account itself is deleted when it no longer belongs to any business.
     */
    public function remove(BusinessContext $ctx, ?string $userId, ?string $email): array
    {
        $this->authorizeManage($ctx, 'You do not have permission to manage users');

        if (! $userId && ! $email) {
            throw new BusinessRuleException('User information is missing.');
        }

        return DB::transaction(function () use ($ctx, $userId, $email) {
            $businessId = $ctx->businessId();

            if ($userId) {
                if ($userId === $ctx->userId()) {
                    throw new BusinessRuleException('You cannot delete your own account from here.');
                }
                $member = BusinessUser::query()->where('business_id', $businessId)->where('user_id', $userId)->lockForUpdate()->first()
                    ?? throw new BusinessRuleException('Business user not found');
                if ($member->role === 'owner' || $userId === $ctx->business->owner_id) {
                    throw new BusinessRuleException('The business owner cannot be removed');
                }

                $member->delete();

                $user = User::query()->find($userId);
                if ($user) {
                    StaffInvitation::query()->where('business_id', $businessId)->where('email', $user->email)->where('status', 'pending')->delete();
                    $stillUsed = $user->is_super_admin
                        || BusinessUser::query()->where('user_id', $user->id)->exists()
                        || Business::query()->where('owner_id', $user->id)->exists();
                    if (! $stillUsed) {
                        DB::table('sessions')->where('user_id', $user->id)->delete();
                        $user->delete();
                    }
                }

                return ['success' => true, 'removed' => true];
            }

            $deleted = StaffInvitation::query()
                ->where('business_id', $businessId)
                ->where('email', mb_strtolower(trim((string) $email)))
                ->where('status', 'pending')
                ->delete();
            if ($deleted === 0) {
                throw new BusinessRuleException('Invitation not found');
            }

            return ['success' => true, 'removed' => true];
        });
    }

    private function sendInvitation(StaffInvitation $invitation, Business $business, string $url): bool
    {
        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            Log::info('Staff invitation link (mail not configured)', ['email' => $invitation->email, 'url' => $url]);

            return false;
        }

        try {
            Mail::to($invitation->email)->send(new StaffInvitationMail($invitation, $business, $url));

            return true;
        } catch (\Throwable $e) {
            Log::error('Staff invitation email failed', ['email' => $invitation->email, 'error' => $e->getMessage()]);

            return false;
        }
    }

    private function authorizeManage(BusinessContext $ctx, string $message): void
    {
        if (! $ctx->isAdminOrOwner() && ! $ctx->can('users')) {
            throw new BusinessRuleException($message, 403);
        }
    }

    private function cleanUsername(mixed $username): ?string
    {
        $username = mb_strtolower(trim((string) ($username ?? '')));
        if ($username === '') {
            return null;
        }
        if (! preg_match(self::USERNAME_PATTERN, $username)) {
            throw new BusinessRuleException('Username must be 3-32 characters and use only letters, numbers, dot, underscore or hyphen');
        }

        return $username;
    }
}
