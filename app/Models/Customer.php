<?php

namespace App\Models;

class Customer extends BaseModel
{
    protected $table = 'customers';

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'credit_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
