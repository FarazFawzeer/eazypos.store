<?php

namespace App\Models;

class PurchaseReturn extends BaseModel
{
    protected $table = 'purchase_returns';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'return_date' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'other_charge' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }
}
