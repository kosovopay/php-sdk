<?php

declare(strict_types=1);

namespace KosovoPay\Dto;

use KosovoPay\Internal\Cast;

final readonly class DeletedResource
{
    public function __construct(
        public string $id,
        public bool $deleted,
    ) {}

    /** @param array<array-key, mixed> $d */
    public static function fromArray(array $d): self
    {
        return new self(
            id: Cast::string($d['id'] ?? null),
            deleted: Cast::bool($d['deleted'] ?? null, true),
        );
    }
}
