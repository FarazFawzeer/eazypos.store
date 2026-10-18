<?php

namespace App\Models;

class Expense extends BaseModel
{
    protected $table = 'expenses';

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'datetime',
        ];
    }
}
