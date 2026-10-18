<?php

namespace App\Models;

class Invoice extends BaseModel
{
    protected $table = 'invoices';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'invoice_date' => 'datetime',
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
