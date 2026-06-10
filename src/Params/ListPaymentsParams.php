<?php

declare(strict_types=1);

namespace KosovoPay\Params;

use KosovoPay\Enums\BankCode;
use KosovoPay\Enums\CurrencyCode;
use KosovoPay\Enums\PaymentStatus;

final readonly class ListPaymentsParams
{
    public function __construct(
        public ?int $limit = null,
        public ?string $startingAfter = null,
        public ?string $endingBefore = null,
        public ?PaymentStatus $status = null,
        public ?BankCode $bankCode = null,
        public ?CurrencyCode $currency = null,
        public ?string $merchantReference = null,
        public ?int $createdGte = null,
        public ?int $createdLte = null,
    ) {}

    /** @return array<string, mixed> */
    public function toQuery(): array
    {
        $created = array_filter([
            'gte' => $this->createdGte,
            'lte' => $this->createdLte,
        ], static fn (mixed $v): bool => $v !== null);

        return array_filter([
            'limit' => $this->limit,
            'starting_after' => $this->startingAfter,
            'ending_before' => $this->endingBefore,
            'status' => $this->status?->value,
            'bank_code' => $this->bankCode?->value,
            'currency' => $this->currency?->value,
            'merchant_reference' => $this->merchantReference,
            'created' => $created === [] ? null : $created,
        ], static fn (mixed $v): bool => $v !== null);
    }
}
