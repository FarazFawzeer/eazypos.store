<?php

namespace App\Support;

/**
 * Exact money arithmetic. Amounts are handled as integer cents so totals,
 * balances and comparisons never drift the way floating point can.
 */
final class Money
{
    /**
     * Convert a user or database amount to integer cents.
     */
    public static function cents(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) round(((float) $value) * 100);
    }

    /**
     * Convert integer cents to a DECIMAL(14,2) string.
     */
    public static function decimal(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        return $sign.intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Normalise a quantity to the DECIMAL(14,3) precision used for stock.
     */
    public static function qty(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return round((float) $value, 3);
    }

    /**
     * Quantity × unit price, rounded to the nearest cent.
     */
    public static function line(float $quantity, int $unitCents): int
    {
        return (int) round($quantity * $unitCents);
    }

    /**
     * Scale an amount by part/whole (used for partial returns).
     */
    public static function proportion(int $cents, float $part, float $whole): int
    {
        if ($whole <= 0) {
            return 0;
        }

        return (int) round($cents * ($part / $whole));
    }

    /**
     * Display form used inside error messages, e.g. "12.5" or "3".
     */
    public static function plain(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 3, '.', ''), '0'), '.');
    }
}
