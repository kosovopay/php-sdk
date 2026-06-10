<?php

declare(strict_types=1);

namespace KosovoPay\Dto;

use DateTimeImmutable;
use KosovoPay\Enums\RefundReason;
use KosovoPay\Enums\RefundStatus;
use KosovoPay\Internal\Cast;

final readonly class Refund
{
    public function __construct(
        public string $id,
        public string $payment,
        public int $amount,
        public RefundStatus $status,
        public ?RefundReason $reason,
        public ?string $failureReason,
        public ?int $created,
        public ?int $succeededAt,
    ) {}

    /** @param array<array-key, mixed> $d */
    public static function fromArray(array $d): self
    {
        $reason = Cast::nullableString($d['reason'] ?? null);

        return new self(
            id: Cast::string($d['id'] ?? null),
            payment: Cast::string($d['payment'] ?? null),
            amount: Cast::int($d['amount'] ?? null),
            status: RefundStatus::tryFromWire(Cast::nullableString($d['status'] ?? null)),
            reason: $reason === null ? null : RefundReason::tryFrom($reason),
            failureReason: Cast::nullableString($d['failure_reason'] ?? null),
            created: Cast::nullableInt($d['created'] ?? null),
            succeededAt: Cast::nullableInt($d['succeeded_at'] ?? null),
        );
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->created === null ? null : (new DateTimeImmutable)->setTimestamp($this->created);
    }
}
