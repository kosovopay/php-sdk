<?php

declare(strict_types=1);

namespace KosovoPay\Dto;

use KosovoPay\Enums\CurrencyCode;
use KosovoPay\Internal\Cast;

final readonly class BankCapabilities
{
    /** @param list<CurrencyCode> $currencies */
    public function __construct(
        public array $currencies,
        public int $minAmount,
        public int $amountStep,
        public RefundCapability $refunds,
    ) {}

    /** @param array<array-key, mixed> $d */
    public static function fromArray(array $d): self
    {
        return new self(
            currencies: array_map(
                static fn (string $c): CurrencyCode => CurrencyCode::tryFromWire($c),
                Cast::stringList($d, 'currencies'),
            ),
            minAmount: Cast::int($d['min_amount'] ?? null),
            amountStep: Cast::int($d['amount_step'] ?? null, 1),
            refunds: RefundCapability::fromArray(Cast::map($d, 'refunds')),
        );
    }
}
