<?php

namespace App\Models;

class Brand extends BaseModel
{
    protected $table = 'brands';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
