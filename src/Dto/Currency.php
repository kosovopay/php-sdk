<?php

declare(strict_types=1);

namespace KosovoPay\Dto;

use KosovoPay\Enums\CurrencyCode;
use KosovoPay\Internal\Cast;

final readonly class Currency
{
    public function __construct(
        public CurrencyCode $code,
        public ?string $name,
        public ?string $symbol,
        public int $decimals,
        public bool $isDefault,
    ) {}

    /** @param array<array-key, mixed> $d */
    public static function fromArray(array $d): self
    {
        return new self(
            code: CurrencyCode::tryFromWire(Cast::nullableString($d['code'] ?? null)),
            name: Cast::nullableString($d['name'] ?? null),
            symbol: Cast::nullableString($d['symbol'] ?? null),
            decimals: Cast::int($d['decimals'] ?? null, 2),
            isDefault: Cast::bool($d['is_default'] ?? null),
        );
    }
}
