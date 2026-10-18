<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's membership of a business, with role and module permissions.
 */
class BusinessUser extends BaseModel
{
    protected $table = 'business_users';

    const UPDATED_AT = null;

    public const ROLES = ['owner', 'admin', 'manager', 'cashier'];

    /** Module keys a staff member can be granted. */
    public const PERMISSION_KEYS = ['sales', 'purchases', 'products', 'reports', 'expenses', 'stock', 'users', 'shipments'];

    public const OWNER_PERMISSIONS = [
        'sales' => true, 'purchases' => true, 'products' => true, 'customers' => true, 'suppliers' => true,
        'expenses' => true, 'stock' => true, 'reports' => true, 'users' => true, 'settings' => true, 'shipments' => true,
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdminOrOwner(): bool
    {
        return in_array($this->role, ['owner', 'admin'], true);
    }

    /**
     * Same rule as Supabase has_business_permission(): owners have every
     * permission, everyone else needs the key set to true.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return $this->isOwner() || ($this->permissions[$permission] ?? false) === true;
    }

    /**
     * Keep only known permission keys, as booleans.
     *
     * @param  array<string, mixed>  $permissions
     * @return array<string, bool>
     */
    public static function cleanPermissions(array $permissions): array
    {
        $clean = [];
        foreach (self::PERMISSION_KEYS as $key) {
            $clean[$key] = filter_var($permissions[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        return $clean;
    }
}
