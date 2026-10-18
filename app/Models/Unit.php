<?php

namespace App\Models;

class Unit extends BaseModel
{
    protected $table = 'units';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
