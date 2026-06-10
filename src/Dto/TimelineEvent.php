<?php

declare(strict_types=1);

namespace KosovoPay\Dto;

use KosovoPay\Internal\Cast;

final readonly class TimelineEvent
{
    public function __construct(
        public string $type,
        public int $at,
    ) {}

    /** @param array<array-key, mixed> $d */
    public static function fromArray(array $d): self
    {
        return new self(
            type: Cast::string($d['type'] ?? null),
            at: Cast::int($d['at'] ?? null),
        );
    }
}
