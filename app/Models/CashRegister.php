<?php

namespace App\Models;

class CashRegister extends BaseModel
{
    protected $table = 'cash_registers';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cash' => 'decimal:2',
            'cash_sales' => 'decimal:2',
            'cash_refunds' => 'decimal:2',
            'cash_expenses' => 'decimal:2',
            'cash_inflows' => 'decimal:2',
            'cash_outflows' => 'decimal:2',
            'cash_adjustments' => 'decimal:2',
            'expected_closing_cash' => 'decimal:2',
            'actual_closing_cash' => 'decimal:2',
            'variance' => 'decimal:2',
        ];
    }
}
