<?php

declare(strict_types=1);

namespace KosovoPay\Params;

use InvalidArgumentException;
use KosovoPay\Enums\RefundReason;

final readonly class CreateRefundParams
{
    public function __construct(
        public string $payment,
        public ?int $amount = null,
        public ?RefundReason $reason = null,
    ) {
        if ($payment === '') {
            throw new InvalidArgumentException('payment id is required.');
        }
        if ($amount !== null && $amount <= 0) {
            throw new InvalidArgumentException('amount, when given, must be a positive integer in minor units.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'payment' => $this->payment,
            'amount' => $this->amount,
            'reason' => $this->reason?->value,
        ], static fn (mixed $v): bool => $v !== null);
    }
}
