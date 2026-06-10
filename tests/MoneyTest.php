<?php

declare(strict_types=1);

use KosovoPay\AmountValidator;
use KosovoPay\Dto\Bank;
use KosovoPay\Enums\CurrencyCode;
use KosovoPay\Money;

it('formats minor units', function () {
    expect(Money::format(4990, 2, '€'))->toBe('€49.90');
    expect(Money::format(5, 2, '€'))->toBe('€0.05');
    expect(Money::format(-1250, 2, '$'))->toBe('-$12.50');
    expect(Money::format(500, 0, '¥'))->toBe('¥500');
});

it('converts by a decimal rate without float drift', function () {
    expect(Money::convert(4990, '0.9234'))->toBe(4608);
    expect(Money::convert(10000, '1.0'))->toBe(10000);
});

it('validates an amount against a bank capability locally', function () {
    $bank = Bank::fromArray([
        'code' => 'onefor', 'display_name' => 'Onefor', 'logo_url' => null, 'enabled' => true, 'modes' => ['test'],
        'capabilities' => ['currencies' => ['EUR'], 'min_amount' => 150, 'amount_step' => 50, 'refunds' => ['supported' => true, 'partial' => false]],
    ]);

    expect(AmountValidator::validate($bank, 200, CurrencyCode::EUR)->valid)->toBeTrue();

    $belowMin = AmountValidator::validate($bank, 100, CurrencyCode::EUR);
    expect($belowMin->valid)->toBeFalse()->and($belowMin->code)->toBe('amount_below_minimum');

    $badStep = AmountValidator::validate($bank, 173, CurrencyCode::EUR);
    expect($badStep->valid)->toBeFalse()
        ->and($badStep->code)->toBe('amount_step_invalid')
        ->and($badStep->nearestValid)->toBe([150, 200]);

    $badCurrency = AmountValidator::validate($bank, 200, CurrencyCode::USD);
    expect($badCurrency->code)->toBe('currency_not_supported');
});
