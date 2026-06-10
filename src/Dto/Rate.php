<?php

declare(strict_types=1);

namespace KosovoPay\Dto;

use KosovoPay\Enums\CurrencyCode;
use KosovoPay\Internal\Cast;

final readonly class Rate
{
    public function __construct(
        public CurrencyCode $from,
        public CurrencyCode $to,
        public string $rate,
        public ?string $syncedAt,
        public bool $stale,
    ) {}

    /** @param array<array-key, mixed> $d */
    public static function fromArray(array $d): self
    {
        return new self(
            from: CurrencyCode::tryFromWire(Cast::nullableString($d['from'] ?? null)),
            to: CurrencyCode::tryFromWire(Cast::nullableString($d['to'] ?? null)),
            rate: Cast::string($d['rate'] ?? null, '0'),
            syncedAt: Cast::nullableString($d['synced_at'] ?? null),
            stale: Cast::bool($d['stale'] ?? null),
        );
    }
}
