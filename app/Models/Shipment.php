<?php

namespace App\Models;

class Shipment extends BaseModel
{
    protected $table = 'shipments';

    protected function casts(): array
    {
        return [
            'shipping_date' => 'datetime',
            'expected_delivery_date' => 'datetime',
            'delivered_at' => 'datetime',
            'shipping_cost' => 'decimal:2',
        ];
    }
}
