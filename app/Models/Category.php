<?php

namespace App\Models;

class Category extends BaseModel
{
    protected $table = 'categories';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
