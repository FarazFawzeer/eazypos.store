<?php

namespace App\Models;

class StockMovement extends BaseModel
{
    protected $table = 'stock_movements';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }
}
