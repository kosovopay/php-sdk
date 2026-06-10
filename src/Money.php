<?php

declare(strict_types=1);

namespace KosovoPay;

use KosovoPay\Dto\Currency;

/**
 * Integer-only money helpers. Amounts are always minor units; rates are decimal
 * strings. Nothing here uses floats for storage — only a final round to int.
 */
final class Money
{
    /** Format minor units as a human string, e.g. (4990, 2, '€') → "€49.90". */
    public static function format(int $amount, int $decimals, string $symbol = ''): string
    {
        $negative = $amount < 0;
        $abs = abs($amount);
        $divisor = 10 ** $decimals;
        $major = intdiv($abs, $divisor);
        $minor = $abs % $divisor;

        $formatted = $decimals > 0
            ? number_format($major, 0).'.'.str_pad((string) $minor, $decimals, '0', STR_PAD_LEFT)
            : number_format($major, 0);

        return ($negative ? '-' : '').$symbol.$formatted;
    }

    /** Format using a Currency DTO's symbol + decimals. */
    public static function formatCurrency(int $amount, Currency $currency): string
    {
        return self::format($amount, $currency->decimals, $currency->symbol ?? '');
    }

    /**
     * Convert minor units by a decimal rate string, returning minor units.
     * Uses bcmath when available to avoid float drift; rounds half-up to int.
     */
    public static function convert(int $amount, string $rate): int
    {
        if (function_exists('bcmul')) {
            $rateStr = is_numeric($rate) ? $rate : '0';

            return (int) round((float) bcmul((string) $amount, $rateStr, 8));
        }

        return (int) round($amount * (float) $rate);
    }
}
