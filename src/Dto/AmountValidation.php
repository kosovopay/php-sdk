<?php

declare(strict_types=1);

namespace KosovoPay\Dto;

/**
 * Result of a client-side amount pre-check against a bank's capabilities.
 */
final readonly class AmountValidation
{
    /** @param array{0: int, 1: int}|null $nearestValid */
    public function __construct(
        public bool $valid,
        public ?string $code = null,
        public ?string $message = null,
        public ?array $nearestValid = null,
    ) {}
}
