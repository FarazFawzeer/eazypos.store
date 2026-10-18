<?php

namespace App\Models;

class Product extends BaseModel
{
    protected $table = 'products';

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'stock_quantity' => 'decimal:3',
            'low_stock_alert' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }
}
