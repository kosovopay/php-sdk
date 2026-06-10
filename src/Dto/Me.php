<?php

declare(strict_types=1);

namespace KosovoPay\Dto;

use KosovoPay\Enums\BankCode;
use KosovoPay\Enums\BankMode;
use KosovoPay\Enums\CurrencyCode;
use KosovoPay\Internal\Cast;

final readonly class Me
{
    /** @param list<BankCode> $enabledBanks */
    public function __construct(
        public Team $team,
        public BankMode $mode,
        public string $keyPrefix,
        public array $enabledBanks,
        public ?CurrencyCode $defaultCurrency,
    ) {}

    /** @param array<array-key, mixed> $d */
    public static function fromArray(array $d): self
    {
        $defaultCurrency = Cast::nullableString($d['default_currency'] ?? null);

        return new self(
            team: Team::fromArray(Cast::map($d, 'team')),
            mode: BankMode::tryFromWire(Cast::nullableString($d['mode'] ?? null)),
            keyPrefix: Cast::string($d['key_prefix'] ?? null),
            enabledBanks: array_map(
                static fn (string $b): BankCode => BankCode::tryFromWire($b),
                Cast::stringList($d, 'enabled_banks'),
            ),
            defaultCurrency: $defaultCurrency === null ? null : CurrencyCode::tryFromWire($defaultCurrency),
        );
    }
}
