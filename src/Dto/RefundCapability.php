<?php

declare(strict_types=1);

namespace KosovoPay\Dto;

use KosovoPay\Internal\Cast;

final readonly class RefundCapability
{
    public function __construct(
        public bool $supported,
        public bool $partial,
    ) {}

    /** @param array<array-key, mixed> $d */
    public static function fromArray(array $d): self
    {
        return new self(
            supported: Cast::bool($d['supported'] ?? null),
            partial: Cast::bool($d['partial'] ?? null),
        );
    }
}
