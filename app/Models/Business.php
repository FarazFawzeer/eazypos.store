<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends BaseModel
{
    protected $table = 'businesses';

    public const STATUSES = ['active', 'suspended', 'closed'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(BusinessUser::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
