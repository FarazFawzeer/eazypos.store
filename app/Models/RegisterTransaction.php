<?php

namespace App\Models;

class RegisterTransaction extends BaseModel
{
    protected $table = 'register_transactions';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }
}
