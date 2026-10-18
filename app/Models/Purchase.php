<?php

namespace App\Models;

class Purchase extends BaseModel
{
    protected $table = 'purchases';

    protected function casts(): array
    {
        return [
            'purchase_date' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'shipping_charge' => 'decimal:2',
            'other_charge' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'amount_due' => 'decimal:2',
        ];
    }
}
