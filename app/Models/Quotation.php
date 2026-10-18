<?php

namespace App\Models;

class Quotation extends BaseModel
{
    protected $table = 'quotations';

    protected function casts(): array
    {
        return [
            'quotation_date' => 'datetime',
            'valid_until' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'shipping_charge' => 'decimal:2',
            'other_charge' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }
}
