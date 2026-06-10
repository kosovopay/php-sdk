<?php

declare(strict_types=1);

namespace KosovoPay;

use KosovoPay\Dto\AmountValidation;
use KosovoPay\Dto\Bank;
use KosovoPay\Enums\CurrencyCode;

/**
 * Mirrors the server's bank min/step checks so callers can catch
 * amount_below_minimum / amount_step_invalid locally before a round-trip.
 * Always reads the bank's live capabilities — never hardcodes a min or step.
 */
final class AmountValidator
{
    public static function validate(Bank $bank, int $amount, CurrencyCode $currency): AmountValidation
    {
        $caps = $bank->capabilities;

        if ($caps->currencies !== [] && ! in_array($currency, $caps->currencies, true)) {
            return new AmountValidation(false, 'currency_not_supported', "{$bank->displayName} does not support {$currency->value}.");
        }

        if ($amount < $caps->minAmount) {
            return new AmountValidation(
                false,
                'amount_below_minimum',
                "Amount is below the {$bank->displayName} minimum of {$caps->minAmount}.",
            );
        }

        $step = max(1, $caps->amountStep);
        if ($amount % $step !== 0) {
            $lower = intdiv($amount, $step) * $step;
            $upper = $lower + $step;

            return new AmountValidation(
                false,
                'amount_step_invalid',
                "{$bank->displayName} requires amounts in steps of {$step}.",
                [$lower, $upper],
            );
        }

        return new AmountValidation(true);
    }
}
