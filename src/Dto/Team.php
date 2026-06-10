<?php

declare(strict_types=1);

namespace KosovoPay\Dto;

use KosovoPay\Internal\Cast;

final readonly class Team
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $logoUrl,
    ) {}

    /** @param array<array-key, mixed> $d */
    public static function fromArray(array $d): self
    {
        return new self(
            id: Cast::string($d['id'] ?? null),
            name: Cast::string($d['name'] ?? null),
            logoUrl: Cast::nullableString($d['logo_url'] ?? null),
        );
    }
}
