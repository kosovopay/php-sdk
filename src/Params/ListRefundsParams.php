<?php

declare(strict_types=1);

namespace KosovoPay\Params;

final readonly class ListRefundsParams
{
    public function __construct(
        public ?string $payment = null,
        public ?int $limit = null,
        public ?string $startingAfter = null,
        public ?string $endingBefore = null,
    ) {}

    /** @return array<string, mixed> */
    public function toQuery(): array
    {
        return array_filter([
            'payment' => $this->payment,
            'limit' => $this->limit,
            'starting_after' => $this->startingAfter,
            'ending_before' => $this->endingBefore,
        ], static fn (mixed $v): bool => $v !== null);
    }
}
