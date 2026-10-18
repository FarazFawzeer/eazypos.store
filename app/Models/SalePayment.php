<?php

namespace App\Models;

class SalePayment extends BaseModel
{
    protected $table = 'sale_payments';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }
}
