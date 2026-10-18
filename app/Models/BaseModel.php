<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared base for every EazyPOS table: UUID keys, microsecond timestamps and
 * ISO-8601 serialisation (the same JSON shape the Supabase client returned).
 */
abstract class BaseModel extends Model
{
    use HasUuids;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * Attributes are only ever filled from validated service input.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];
}
