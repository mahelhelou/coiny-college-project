<?php

namespace App\Support;

/**
 * Formats database-computed amounts as 2-decimal strings for the API. Performs no arithmetic.
 */
final class Money
{
    public static function format(int|float|string|null $value): string
    {
        // MySQL returns DECIMAL aggregates as exact strings; keep them untouched.
        if (is_string($value) && preg_match('/^-?\d+\.\d{2}$/', $value)) {
            return $value === '-0.00' ? '0.00' : $value;
        }

        $formatted = number_format((float) ($value ?? 0), 2, '.', '');

        return $formatted === '-0.00' ? '0.00' : $formatted;
    }
}
