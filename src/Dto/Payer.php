<?php

declare(strict_types=1);

namespace KosovoPay\Dto;

use KosovoPay\Internal\Cast;

final readonly class Payer
{
    public function __construct(
        public ?string $name,
        public ?string $email,
    ) {}

    /** @param array<array-key, mixed> $d */
    public static function fromArray(array $d): self
    {
        return new self(
            name: Cast::nullableString($d['name'] ?? null),
            email: Cast::nullableString($d['email'] ?? null),
        );
    }
}
