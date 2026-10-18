<?php

namespace App\Support;

use App\Exceptions\BusinessRuleException;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;

/**
 * The signed-in user's active business membership for the current request.
 * Every query and service call is scoped through this object, so a client
 * can never act on another business by sending a different ID.
 */
final class BusinessContext
{
    public function __construct(
        public readonly User $user,
        public readonly BusinessUser $membership,
        public readonly Business $business,
    ) {}

    public function businessId(): string
    {
        return $this->business->id;
    }

    public function userId(): string
    {
        return $this->user->id;
    }

    public function can(string $permission): bool
    {
        return $this->membership->hasPermission($permission);
    }

    public function isAdminOrOwner(): bool
    {
        return $this->membership->status === 'active' && $this->membership->isAdminOrOwner();
    }

    /**
     * Abort with the given message unless the member holds the permission.
     */
    public function authorize(string $permission, string $message): void
    {
        if (! $this->can($permission)) {
            throw new BusinessRuleException($message, 403);
        }
    }

    /**
     * Abort unless the member holds at least one of the permissions.
     *
     * @param  list<string>  $permissions
     */
    public function authorizeAny(array $permissions, string $message): void
    {
        foreach ($permissions as $permission) {
            if ($this->can($permission)) {
                return;
            }
        }

        throw new BusinessRuleException($message, 403);
    }
}
