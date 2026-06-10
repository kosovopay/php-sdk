<?php

declare(strict_types=1);

namespace KosovoPay\Params;

use KosovoPay\Enums\CurrencyCode;

final readonly class RetrieveRateParams
{
    public function __construct(
        public CurrencyCode $from,
        public CurrencyCode $to,
    ) {}

    /** @return array<string, string> */
    public function toQuery(): array
    {
        return ['from' => $this->from->value, 'to' => $this->to->value];
    }
}
