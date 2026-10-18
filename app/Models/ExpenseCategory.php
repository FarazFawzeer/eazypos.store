<?php

namespace App\Models;

class ExpenseCategory extends BaseModel
{
    protected $table = 'expense_categories';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
