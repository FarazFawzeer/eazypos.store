<?php

namespace App\Models;

class PurchaseReturnItem extends BaseModel
{
    protected $table = 'purchase_return_items';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }
}
